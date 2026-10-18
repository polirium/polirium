<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Polirium\Core\Base\Http\Models\Branch\Branch;
use Polirium\Core\Base\Http\Models\User;
use Polirium\Core\Base\Traits\GetPermission;
use Polirium\Modules\Accounting\Http\Model\AccountingType;
use Polirium\Modules\Accounting\Http\Model\Payment as CashPayment;
use Polirium\Modules\Accounting\Http\Model\PayPerson;
use Polirium\Modules\Accounting\Http\Model\Receipt;
use Polirium\Modules\Accounting\Http\Model\Refund\Refund as SalesReturn;
use Polirium\Modules\Accounting\Http\Model\Refund\RefundProduct as SalesReturnLine;
use Polirium\Modules\Customer\Http\Model\Customer;
use Polirium\Modules\Customer\Http\Model\CustomerGroup;
use Polirium\Modules\Product\Http\Model\Category;
use Polirium\Modules\Product\Http\Model\Payment\Payment;
use Polirium\Modules\Product\Http\Model\Payment\PaymentDelivery;
use Polirium\Modules\Product\Http\Model\Payment\PaymentMethod;
use Polirium\Modules\Product\Http\Model\Payment\PaymentPartnerDelivery;
use Polirium\Modules\Product\Http\Model\Payment\PaymentProduct;
use Polirium\Modules\Product\Http\Model\Payment\SaleChannel;
use Polirium\Modules\Product\Http\Model\Product;
use Polirium\Modules\Product\Http\Model\ProductBranch;
use Polirium\Modules\Product\Http\Model\ProductElement;
use Polirium\Modules\Product\Http\Model\Shelve;
use Polirium\Modules\Product\Http\Model\Stock\Stock;
use Polirium\Modules\Product\Http\Model\Trademark;
use Polirium\Modules\Product\Http\Support\DocumentInventorySupport;
use Polirium\Modules\Product\Http\Support\PaymentInventorySupport;
use Polirium\Modules\Product\Http\Support\ProductInventorySupport;
use Polirium\Modules\Product\Http\Support\StockInventorySupport;
use Polirium\Modules\Task\Models\Project;
use Polirium\Modules\Task\Models\Task;
use Polirium\Modules\Vendor\Http\Model\Purchase\Purchase;
use Polirium\Modules\Vendor\Http\Model\Purchase\PurchaseProduct;
use Polirium\Modules\Vendor\Http\Model\Refund\Refund as PurchaseReturn;
use Polirium\Modules\Vendor\Http\Model\Refund\RefundProduct as PurchaseReturnLine;
use Polirium\Modules\Vendor\Http\Model\Transfer\Transfer;
use Polirium\Modules\Vendor\Http\Model\Transfer\TransferProduct;
use Polirium\Modules\Vendor\Http\Model\Vendor;
use Polirium\Modules\Vendor\Http\Model\VendorGroup;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Throwable;

/**
 * Sample data for a demo installation: several branches, staff and roles,
 * a product catalogue with brands, combos and services, customers, suppliers
 * and months of purchases, transfers, sales, returns, stock counts, cash book
 * entries and projects.
 *
 * Every stock movement goes through the same code the screens use
 * (DocumentInventorySupport, ProductInventorySupport, StockInventorySupport),
 * so stock levels, the stock card, supplier debts and the cash book agree.
 * All names, phone numbers and addresses are fictional.
 *
 * --fresh wipes business data first and is only allowed on an installation
 * marked as a demo (POLIRIUM_DEMO=true).
 */
class DemoDataCommand extends Command
{
    use GetPermission;

    protected $signature = 'demo:data
        {--fresh : Xoá dữ liệu kinh doanh hiện có rồi tạo lại (chỉ khi POLIRIUM_DEMO=true)}
        {--days=120 : Số ngày lịch sử giao dịch}
        {--seed=20261018 : Hạt ngẫu nhiên (cùng hạt cho cùng dữ liệu)}
        {--force : Không hỏi xác nhận}';

    protected $description = 'Tạo dữ liệu mẫu cho bản demo: nhiều chi nhánh, nhân viên, hàng hoá, giao dịch nhiều tháng';

    private const DEMO_EMAIL_DOMAIN = 'demo.polirium.test';

    private User $admin;

    /** @var array<int, Branch> keyed by branch id, in opening order */
    private array $branches = [];

    /** @var array<int, Carbon> branch id => opening day */
    private array $openedOn = [];

    /** @var array<int, array{manager: User, cashiers: list<User>}> */
    private array $staff = [];

    private ?User $warehouse = null;

    private ?User $accountant = null;

    /** @var Collection<int, Product> stock-managed products */
    private Collection $goods;

    /** @var Collection<int, Product> */
    private Collection $combos;

    /** @var Collection<int, Product> */
    private Collection $services;

    /** @var array<string, list<int>> category root => vendor ids */
    private array $vendorsByRoot = [];

    /** @var array<int, string> product id => category root */
    private array $rootOf = [];

    /** @var list<Customer> */
    private array $customers = [];

    /** @var list<Customer> */
    private array $loyalCustomers = [];

    /** @var Collection<int, PaymentMethod> */
    private Collection $methods;

    /** @var Collection<int, SaleChannel> */
    private Collection $channels;

    /** @var Collection<int, PaymentPartnerDelivery> */
    private Collection $partners;

    /** @var list<array{at: Carbon, kind: string, data: mixed}> */
    private array $scheduled = [];

    /** @var list<Purchase> */
    private array $purchases = [];

    private array $counts = [];

    /** Real current time (the clock is moved while generating history). */
    private Carbon $realNow;

    public function handle(): int
    {
        $fresh = (bool) $this->option('fresh');
        $days = max(30, min(365, (int) $this->option('days')));

        if ($fresh && ! config('app.demo')) {
            $this->error('Chỉ chạy --fresh trên bản demo: đặt POLIRIUM_DEMO=true trong .env (lệnh này xoá toàn bộ dữ liệu kinh doanh).');

            return self::FAILURE;
        }

        if (! $fresh && Product::query()->exists()) {
            $this->error('Đã có dữ liệu hàng hoá. Dùng --fresh trên bản demo để tạo lại từ đầu.');

            return self::FAILURE;
        }

        $admin = User::query()->where('super_admin', true)->orderBy('id')->first();

        if (! $admin) {
            $this->error('Cần ít nhất một tài khoản quản trị (super admin).');

            return self::FAILURE;
        }

        $this->admin = $admin;

        $database = DB::connection()->getDatabaseName();
        if ($fresh && ! $this->option('force') && ! $this->confirm("Xoá toàn bộ hàng hoá, khách hàng, nhà cung cấp, chứng từ, sổ quỹ và công việc trong CSDL \"{$database}\" rồi tạo dữ liệu mẫu?")) {
            return self::FAILURE;
        }

        mt_srand((int) $this->option('seed'));
        $this->realNow = Carbon::now();
        auth()->login($this->admin);
        $this->pauseActivityLog();

        try {
            if ($fresh) {
                $this->components->task('Xoá dữ liệu cũ', fn () => $this->wipe());
            }

            $start = now()->startOfDay()->subDays($days);
            Carbon::setTestNow($start->copy()->setTime(7, 30));

            $this->components->task('Chi nhánh, vai trò, nhân viên', fn () => $this->seedOrganisation($start, $days));
            $this->components->task('Danh mục, thương hiệu, hàng hoá, combo, dịch vụ', fn () => $this->seedCatalogue());
            $this->components->task('Khách hàng, nhà cung cấp, kênh bán, đối tác giao hàng', fn () => $this->seedPartners());
            $this->components->task("Giao dịch {$days} ngày (tồn đầu, nhập, chuyển, bán, trả, kiểm kho, sổ quỹ)", fn () => $this->simulate($start, $days));
            $this->components->task('Dự án và công việc', fn () => $this->seedProjects());
        } finally {
            Carbon::setTestNow();
        }

        $this->newLine();
        $this->table(['Dữ liệu', 'Số lượng'], collect($this->counts)->map(fn ($n, $k) => [$k, number_format($n)])->values()->all());

        if ($this->skips !== []) {
            arsort($this->skips);
            $this->newLine();
            $this->line('Bước bỏ qua (không đủ điều kiện tại thời điểm đó):');
            $this->table(['Lý do', 'Lần'], collect($this->skips)->take(8)->map(fn ($n, $reason) => [$reason, $n])->values()->all());
        }

        $mismatch = $this->stockMismatches();
        if ($mismatch > 0) {
            $this->warn("Có {$mismatch} dòng tồn kho lệch với thẻ kho. Kiểm tra lại bằng php artisan audit:inventory.");

            return self::FAILURE;
        }

        $this->info('Đã tạo dữ liệu demo. Tồn kho khớp thẻ kho ở mọi chi nhánh.');

        return self::SUCCESS;
    }

    // ---------------------------------------------------------------- wipe

    private function wipe(): void
    {
        $tables = [
            'product_payment_refund_products', 'product_payment_refunds',
            'product_refund_products', 'product_refunds',
            'product_payment_products', 'product_payment_deliveries', 'product_payments',
            'product_stock_products', 'product_stocks', 'product_logs', 'product_branches',
            'product_elements', 'product_units', 'products', 'categories', 'trademarks', 'shelves',
            'customers_pivot_groups', 'customers', 'customer_groups',
            'vendor_purchase_refund_products', 'vendor_purchase_refunds',
            'vendor_transfer_products', 'vendor_transfers',
            'vendor_purchase_products', 'vendor_purchases',
            'vendors_groups_pivot', 'vendors', 'vendor_groups',
            'accouting_payments', 'accounting_receipts', 'accounting_pay_persons',
            'task_comments', 'task_attachments', 'task_time_logs', 'task_dependencies', 'tasks', 'projects',
            'branch_taking_addresses', 'activity_log',
        ];

        Schema::disableForeignKeyConstraints();

        try {
            foreach ($tables as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->truncate();
                }
            }

            // Staff created by an earlier run, and every branch but the first.
            $demoUsers = User::query()->where('email', 'like', '%@' . self::DEMO_EMAIL_DOMAIN)->pluck('id');
            DB::table('user_branches')->whereIn('user_id', $demoUsers)->delete();
            DB::table('model_has_roles')->where('model_type', User::class)->whereIn('model_id', $demoUsers)->delete();
            DB::table('user_dashboard_layouts')->whereIn('user_id', $demoUsers)->delete();
            User::query()->whereIn('id', $demoUsers)->forceDelete();

            $first = Branch::query()->orderBy('id')->value('id');
            DB::table('user_branches')->where('branch_id', '!=', $first)->delete();
            Branch::query()->where('id', '!=', $first)->forceDelete();
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    // ------------------------------------------------------------ organisation

    private function seedOrganisation(Carbon $start, int $days): void
    {
        $definitions = [
            ['name' => 'Cửa hàng Quận 1', 'phone' => '028 3000 0101', 'address' => '12 Đường Số 3, Phường Bến Nghé, Quận 1, TP. Hồ Chí Minh', 'email' => 'q1@' . self::DEMO_EMAIL_DOMAIN, 'opens' => 0, 'size' => 10],
            ['name' => 'Cửa hàng Thủ Đức', 'phone' => '028 3000 0202', 'address' => '45 Đường Số 12, Phường Linh Trung, TP. Thủ Đức', 'email' => 'thuduc@' . self::DEMO_EMAIL_DOMAIN, 'opens' => 0, 'size' => 6],
            ['name' => 'Cửa hàng Hoàn Kiếm', 'phone' => '024 3000 0303', 'address' => '8 Phố Mẫu Số 5, Phường Hàng Bài, Quận Hoàn Kiếm, Hà Nội', 'email' => 'hoankiem@' . self::DEMO_EMAIL_DOMAIN, 'opens' => 0, 'size' => 7],
            ['name' => 'Cửa hàng Hải Châu', 'phone' => '0236 300 0404', 'address' => '21 Đường Số 7, Phường Thạch Thang, Quận Hải Châu, Đà Nẵng', 'email' => 'haichau@' . self::DEMO_EMAIL_DOMAIN, 'opens' => (int) round($days * 0.55), 'size' => 4],
        ];

        $first = Branch::query()->orderBy('id')->first();

        foreach ($definitions as $index => $definition) {
            $attributes = ['name' => $definition['name'], 'phone' => $definition['phone'], 'email' => $definition['email'], 'address' => $definition['address'], 'status' => 1, 'user_id' => $this->admin->id];
            $branch = $index === 0 && $first ? tap($first)->update($attributes) : Branch::create($attributes);
            $this->branches[$branch->id] = $branch;
            $this->openedOn[$branch->id] = $start->copy()->addDays($definition['opens']);
            $this->branchSize[$branch->id] = $definition['size'];
        }

        $this->admin->branches()->syncWithoutDetaching(array_keys($this->branches));

        $roles = $this->seedRoles();

        $people = ['Nguyễn Minh Anh', 'Trần Quốc Bảo', 'Lê Thu Hà', 'Phạm Gia Huy', 'Võ Ngọc Lan', 'Đặng Hoàng Nam', 'Bùi Khánh Linh', 'Hồ Thanh Tùng', 'Ngô Bảo Châu', 'Dương Mỹ Duyên', 'Lý Đức Thịnh', 'Phan Thảo Vy', 'Đỗ Hải Đăng', 'Mai Phương Thảo', 'Vũ Tiến Đạt'];
        $next = 0;
        $make = function (string $role, array $branchIds) use (&$next, $people, $roles): User {
            $name = $people[$next++ % count($people)];
            [$last, $first] = [Str::beforeLast($name, ' '), Str::afterLast($name, ' ')];
            $user = User::create([
                'username' => 'demo.' . Str::slug($name, '.'),
                'first_name' => $last,
                'last_name' => $first,
                'email' => Str::slug($name, '.') . '@' . self::DEMO_EMAIL_DOMAIN,
                'password' => Str::random(40),
                'phone' => '0900' . sprintf('%06d', 900000 + $next),
                'status' => 'active',
                'super_admin' => false,
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();
            $user->syncRoles([$roles[$role]]);
            $user->branches()->sync($branchIds);
            $this->count('Nhân viên');

            return $user;
        };

        foreach ($this->branches as $branchId => $branch) {
            $this->staff[$branchId] = [
                'manager' => $make('manager', [$branchId]),
                'cashiers' => [$make('cashier', [$branchId]), $make('cashier', [$branchId])],
            ];
        }

        $mainId = array_key_first($this->branches);
        $this->warehouse = $make('warehouse', [$mainId]);
        $this->accountant = $make('accountant', array_keys($this->branches));
        $this->count('Chi nhánh', count($this->branches));
    }

    /** @var array<int, int> */
    private array $branchSize = [];

    /**
     * @return array<string, Role>
     */
    private function seedRoles(): array
    {
        $flags = array_keys($this->getAvailablePermissions());
        $match = fn (array $patterns, array $except = []) => array_values(array_filter($flags, fn ($flag) => Str::is($patterns, $flag) && ! Str::is($except, $flag)));

        $definitions = [
            'manager' => ['Quản lý cửa hàng', $match(['dashboard*', 'widgets*', 'products*', 'sales*', 'customers*', 'vendors*', 'accountings*', 'projects*', 'tasks*', 'print-forms*'], ['*.destroy', '*.delete', 'widgets.manage'])],
            'cashier' => ['Thu ngân', $match(['dashboard.index', 'widgets.sales', 'widgets.revenue', 'widgets.stats', 'sales', 'sales.payment.index', 'sales.print', 'sales.payment.refund', 'sales.orders.*', 'products', 'products.index', 'customers', 'customers.index', 'customers.create', 'customers.edit', 'accountings', 'accountings.payments', 'tasks', 'tasks.index', 'tasks.show', 'tasks.kanban'])],
            'warehouse' => ['Nhân viên kho', $match(['dashboard.index', 'products', 'products.index', 'products.create', 'products.edit', 'products.view-cost', 'products.stock*', 'vendors', 'vendors.index', 'vendors.purchases.*', 'vendors.transfers.*', 'vendors.refunds.*', 'tasks', 'tasks.index', 'tasks.show'], ['*.delete'])],
            'accountant' => ['Kế toán', $match(['dashboard.index', 'widgets.*', 'accountings*', 'vendors', 'vendors.index', 'vendors.purchases.index', 'vendors.purchases.view', 'vendors.purchases.view-price', 'customers', 'customers.index', 'products', 'products.index', 'products.view-cost', 'sales', 'sales.reports'], ['widgets.manage'])],
        ];

        $roles = [];
        foreach ($definitions as $key => [$name, $permissions]) {
            foreach ($permissions as $permission) {
                Permission::findOrCreate($permission, 'web');
            }
            $role = Role::findOrCreate($name, 'web');
            $role->syncPermissions($permissions);
            $roles[$key] = $role;
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $this->count('Vai trò', count($roles));

        return $roles;
    }

    // --------------------------------------------------------------- catalogue

    private function seedCatalogue(): void
    {
        $tree = [
            'Thời trang' => ['Áo nam', 'Quần nam', 'Thời trang nữ', 'Giày dép', 'Túi & phụ kiện'],
            'Mỹ phẩm' => ['Chăm sóc da', 'Trang điểm', 'Chăm sóc tóc'],
            'Điện tử' => ['Tai nghe & loa', 'Sạc & cáp', 'Đồng hồ thông minh'],
            'Gia dụng' => ['Nhà bếp', 'Điện gia dụng'],
            'Thực phẩm' => ['Cà phê & trà', 'Bánh kẹo', 'Sữa & đồ uống'],
        ];

        $categories = [];
        foreach ($tree as $root => $children) {
            $parent = Category::create(['name' => $root, 'user_id' => $this->admin->id]);
            foreach ($children as $child) {
                $categories[$child] = [Category::create(['name' => $child, 'parent_id' => $parent->id, 'user_id' => $this->admin->id]), $root];
            }
        }
        $this->count('Nhóm hàng', Category::count());

        $shelves = [];
        foreach (['Kệ A - Thời trang', 'Kệ B - Mỹ phẩm', 'Kệ C - Điện tử', 'Kệ D - Gia dụng', 'Kệ E - Thực phẩm', 'Tủ kính quầy thu ngân', 'Kho sau'] as $name) {
            $shelves[] = Shelve::create(['name' => $name, 'user_id' => $this->admin->id]);
        }
        $shelfOf = ['Thời trang' => 0, 'Mỹ phẩm' => 1, 'Điện tử' => 2, 'Gia dụng' => 3, 'Thực phẩm' => 4];

        // [category, brand, name, unit, cost in thousands]
        $items = [
            ['Áo nam', 'Uniqlo', 'Áo thun cổ tròn Airism', 'Cái', 149], ['Áo nam', 'Uniqlo', 'Áo sơ mi Oxford dài tay', 'Cái', 389],
            ['Áo nam', 'Owen', 'Áo polo nam Regular fit', 'Cái', 259], ['Áo nam', 'Owen', 'Áo sơ mi công sở trắng', 'Cái', 329],
            ['Áo nam', 'Nike', 'Áo thun thể thao Dri-FIT', 'Cái', 420], ['Áo nam', 'Adidas', 'Áo khoác gió 3 sọc', 'Cái', 890],
            ['Quần nam', 'Uniqlo', 'Quần jean Slim fit', 'Cái', 590], ['Quần nam', 'Owen', 'Quần âu nam ống đứng', 'Cái', 410],
            ['Quần nam', 'Adidas', 'Quần short thể thao', 'Cái', 380], ['Quần nam', 'Uniqlo', 'Quần kaki Chino', 'Cái', 450],
            ['Thời trang nữ', 'Elise', 'Đầm suông công sở', 'Cái', 690], ['Thời trang nữ', 'Elise', 'Áo kiểu cổ nơ', 'Cái', 450],
            ['Thời trang nữ', 'Zara', 'Chân váy chữ A', 'Cái', 520], ['Thời trang nữ', 'Zara', 'Áo blazer nữ', 'Cái', 1150],
            ['Thời trang nữ', 'Uniqlo', 'Áo len cổ lọ nữ', 'Cái', 480],
            ['Giày dép', 'Biti\'s', 'Giày thể thao Hunter Street', 'Đôi', 620], ['Giày dép', 'Biti\'s', 'Dép quai ngang', 'Đôi', 145],
            ['Giày dép', 'Nike', 'Giày chạy bộ Air Zoom', 'Đôi', 2150], ['Giày dép', 'Adidas', 'Giày Stan Smith', 'Đôi', 1690],
            ['Túi & phụ kiện', 'Charles & Keith', 'Túi đeo vai mini', 'Cái', 980], ['Túi & phụ kiện', 'Charles & Keith', 'Ví nữ cầm tay', 'Cái', 640],
            ['Túi & phụ kiện', 'Owen', 'Thắt lưng da nam', 'Cái', 280], ['Túi & phụ kiện', 'Nike', 'Balo thể thao Brasilia', 'Cái', 750],
            ['Chăm sóc da', 'Cocoon', 'Nước tẩy trang bí đao 500ml', 'Chai', 195], ['Chăm sóc da', 'Cocoon', 'Gel rửa mặt bí đao', 'Tuýp', 165],
            ['Chăm sóc da', 'Innisfree', 'Sữa rửa mặt trà xanh', 'Tuýp', 180], ['Chăm sóc da', 'Innisfree', 'Kem dưỡng ẩm trà xanh', 'Hộp', 420],
            ['Chăm sóc da', 'Laneige', 'Mặt nạ ngủ môi', 'Hộp', 310], ['Chăm sóc da', 'Laneige', 'Kem dưỡng nước Water Bank', 'Hộp', 690],
            ['Chăm sóc da', 'The Ordinary', 'Serum Niacinamide 10%', 'Chai', 230], ['Chăm sóc da', 'The Ordinary', 'Serum Hyaluronic Acid', 'Chai', 250],
            ['Chăm sóc da', 'L\'Oréal', 'Kem chống nắng UV Defender', 'Tuýp', 260], ['Chăm sóc da', 'L\'Oréal', 'Nước hoa hồng Revitalift', 'Chai', 240],
            ['Trang điểm', 'L\'Oréal', 'Son kem lì Rouge Signature', 'Cây', 220], ['Trang điểm', 'L\'Oréal', 'Phấn nước Infallible', 'Hộp', 380],
            ['Trang điểm', 'Innisfree', 'Phấn phủ kiềm dầu', 'Hộp', 150], ['Trang điểm', 'Laneige', 'Cushion Neo', 'Hộp', 640],
            ['Chăm sóc tóc', 'Cocoon', 'Nước dưỡng tóc tinh dầu bưởi', 'Chai', 145], ['Chăm sóc tóc', 'L\'Oréal', 'Dầu gội Elseve phục hồi', 'Chai', 135],
            ['Tai nghe & loa', 'Sony', 'Tai nghe không dây WF-C700N', 'Cái', 1690], ['Tai nghe & loa', 'Sony', 'Loa bluetooth SRS-XB100', 'Cái', 990],
            ['Tai nghe & loa', 'Xiaomi', 'Tai nghe Redmi Buds 5', 'Cái', 590], ['Tai nghe & loa', 'Samsung', 'Tai nghe Galaxy Buds FE', 'Cái', 1390],
            ['Sạc & cáp', 'Anker', 'Củ sạc nhanh 20W', 'Cái', 220], ['Sạc & cáp', 'Anker', 'Cáp USB-C 1m bện dù', 'Sợi', 120],
            ['Sạc & cáp', 'Anker', 'Pin dự phòng 10000mAh', 'Cái', 450], ['Sạc & cáp', 'Xiaomi', 'Pin dự phòng 20000mAh', 'Cái', 390],
            ['Sạc & cáp', 'Samsung', 'Sạc không dây 15W', 'Cái', 520],
            ['Đồng hồ thông minh', 'Xiaomi', 'Vòng đeo tay Smart Band 9', 'Cái', 690], ['Đồng hồ thông minh', 'Samsung', 'Galaxy Watch FE', 'Cái', 3290],
            ['Nhà bếp', 'Sunhouse', 'Chảo chống dính 26cm', 'Cái', 230], ['Nhà bếp', 'Sunhouse', 'Bộ nồi inox 3 chiếc', 'Bộ', 690],
            ['Nhà bếp', 'Lock&Lock', 'Bộ hộp thuỷ tinh 3 hộp', 'Bộ', 320], ['Nhà bếp', 'Lock&Lock', 'Bình giữ nhiệt 500ml', 'Cái', 290],
            ['Nhà bếp', 'Lock&Lock', 'Hộp cơm giữ nhiệt', 'Cái', 360],
            ['Điện gia dụng', 'Philips', 'Nồi chiên không dầu 4.1L', 'Cái', 1890], ['Điện gia dụng', 'Philips', 'Máy sấy tóc 1600W', 'Cái', 450],
            ['Điện gia dụng', 'Sunhouse', 'Ấm siêu tốc 1.8L', 'Cái', 260], ['Điện gia dụng', 'Xiaomi', 'Máy lọc không khí 4 Lite', 'Cái', 2390],
            ['Cà phê & trà', 'Trung Nguyên', 'Cà phê G7 3in1 hộp 21 gói', 'Hộp', 58], ['Cà phê & trà', 'Trung Nguyên', 'Cà phê rang xay Sáng tạo 340g', 'Gói', 98],
            ['Cà phê & trà', 'Highlands', 'Cà phê phin truyền thống 200g', 'Gói', 85], ['Cà phê & trà', 'Phúc Long', 'Trà ô long túi lọc', 'Hộp', 72],
            ['Bánh kẹo', 'Kinh Đô', 'Bánh quy bơ hộp thiếc', 'Hộp', 125], ['Bánh kẹo', 'Kinh Đô', 'Bánh Cosy Marie', 'Gói', 28],
            ['Bánh kẹo', 'Orion', 'Bánh Chocopie hộp 12', 'Hộp', 52],
            ['Sữa & đồ uống', 'Vinamilk', 'Sữa tươi tiệt trùng ít đường 1L', 'Hộp', 32], ['Sữa & đồ uống', 'Vinamilk', 'Sữa chua uống Probi lốc 5', 'Lốc', 26],
            ['Sữa & đồ uống', 'TH true milk', 'Sữa hạt óc chó 180ml lốc 4', 'Lốc', 38],
        ];

        $trademarks = [];
        foreach (array_unique(array_column($items, 1)) as $brand) {
            $trademarks[$brand] = Trademark::create(['name' => $brand, 'user_id' => $this->admin->id]);
        }
        $this->count('Thương hiệu', count($trademarks));

        $branchIds = array_keys($this->branches);
        foreach ($items as $index => [$categoryName, $brand, $name, $unit, $cost]) {
            [$category, $root] = $categories[$categoryName];
            $costValue = $cost * 1000;
            $price = (int) (round($costValue * (mt_rand(135, 175) / 100) / 1000) * 1000);
            $cheap = $costValue < 200000;
            $product = Product::create([
                'code' => code_generate('HH', $index),
                'name' => "{$brand} {$name}",
                'category_id' => $category->id,
                'trademark_id' => $trademarks[$brand]->id,
                'shelve_id' => $shelves[$shelfOf[$root]]->id,
                'cost' => $costValue,
                'price' => $price,
                'unit' => $unit,
                'weight' => mt_rand(1, 20) * 50,
                'weight_type' => 'gram',
                'min_quantity' => $cheap ? mt_rand(10, 20) : mt_rand(3, 8),
                'max_quantity' => $cheap ? mt_rand(80, 150) : mt_rand(20, 50),
                'allows_sale' => 1,
                'type' => 'product',
                'user_id' => $this->admin->id,
            ]);
            $product->branches()->sync(array_fill_keys($branchIds, ['qty' => 0]));
            $this->rootOf[$product->id] = $root;
        }

        $this->goods = Product::query()->where('type', 'product')->get()->keyBy('id');

        $services = [
            ['Gói quà tặng cao cấp', 15, 30], ['Bảo hành mở rộng 12 tháng (điện tử)', 0, 199], ['Giao hàng nhanh nội thành', 0, 30],
            ['Sửa đồ, lên lai quần', 10, 40], ['Khắc tên lên sản phẩm', 5, 50],
        ];
        foreach ($services as $i => [$name, $cost, $price]) {
            $product = Product::create(['code' => code_generate('DV', $i), 'name' => $name, 'cost' => $cost * 1000, 'price' => $price * 1000, 'unit' => 'Lần', 'weight_type' => 'gram', 'allows_sale' => 1, 'type' => 'service', 'user_id' => $this->admin->id]);
            $product->branches()->sync(array_fill_keys($branchIds, ['qty' => 0]));
        }
        $this->services = Product::query()->where('type', 'service')->get();

        $byName = fn (string $needle) => $this->goods->first(fn ($p) => Str::contains($p->name, $needle));
        $combos = [
            ['Combo làm sạch da Cocoon', [['Nước tẩy trang bí đao', 1], ['Gel rửa mặt bí đao', 1]], 0.9],
            ['Combo dưỡng ẩm Laneige', [['Water Bank', 1], ['Mặt nạ ngủ môi', 1]], 0.88],
            ['Combo cà phê buổi sáng', [['G7 3in1', 2], ['Cosy Marie', 2]], 0.92],
            ['Combo bếp tiện lợi', [['Chảo chống dính', 1], ['Bộ hộp thuỷ tinh', 1]], 0.9],
            ['Combo sạc nhanh Anker', [['Củ sạc nhanh 20W', 1], ['Cáp USB-C', 1]], 0.88],
            ['Combo quà tặng nam', [['Thắt lưng da nam', 1], ['Áo polo nam', 1]], 0.9],
        ];
        foreach ($combos as $i => [$name, $parts, $ratio]) {
            $components = collect($parts)->map(fn ($part) => [$byName($part[0]), $part[1]])->filter(fn ($part) => $part[0]);
            $cost = $components->sum(fn ($part) => $part[0]->cost * $part[1]);
            $price = (int) (round($components->sum(fn ($part) => $part[0]->price * $part[1]) * $ratio / 1000) * 1000);
            $combo = Product::create(['code' => code_generate('CB', $i), 'name' => $name, 'cost' => $cost, 'price' => $price, 'unit' => 'Bộ', 'weight_type' => 'gram', 'allows_sale' => 1, 'type' => 'combo', 'user_id' => $this->admin->id]);
            $combo->branches()->sync(array_fill_keys($branchIds, ['qty' => 0]));
            foreach ($components as [$component, $qty]) {
                ProductElement::create(['product_id' => $combo->id, 'element_id' => $component->id, 'qty' => $qty, 'price' => $component->cost]);
            }
        }
        $this->combos = Product::query()->where('type', 'combo')->with('elements')->get();

        $this->count('Hàng hoá', $this->goods->count());
        $this->count('Combo', $this->combos->count());
        $this->count('Dịch vụ', $this->services->count());
    }

    // ---------------------------------------------------------------- partners

    private function seedPartners(): void
    {
        $groups = [];
        foreach ([['Khách lẻ', 'Mua lẻ tại cửa hàng'], ['Thành viên', 'Đã đăng ký thẻ thành viên'], ['Khách VIP', 'Doanh số trên 10 triệu'], ['Khách sỉ', 'Mua số lượng lớn'], ['Nhân viên', 'Mua hàng nội bộ']] as [$name, $note]) {
            $groups[$name] = CustomerGroup::create(['name' => $name, 'type' => 1, 'note' => $note, 'user_id' => $this->admin->id]);
        }

        $family = ['Nguyễn', 'Trần', 'Lê', 'Phạm', 'Hoàng', 'Huỳnh', 'Phan', 'Vũ', 'Võ', 'Đặng', 'Bùi', 'Đỗ', 'Hồ', 'Ngô', 'Dương', 'Lý'];
        $middle = ['Văn', 'Thị', 'Minh', 'Ngọc', 'Hoàng', 'Thanh', 'Gia', 'Bảo', 'Khánh', 'Thu', 'Hữu', 'Quốc', 'Mỹ', 'Đức'];
        $given = ['An', 'Bình', 'Châu', 'Dũng', 'Giang', 'Hà', 'Hải', 'Hạnh', 'Hiếu', 'Hoa', 'Huy', 'Khang', 'Khoa', 'Lan', 'Linh', 'Long', 'Mai', 'My', 'Nam', 'Ngân', 'Nhi', 'Phong', 'Phúc', 'Quân', 'Quỳnh', 'Sơn', 'Tâm', 'Thảo', 'Trang', 'Trí', 'Tuấn', 'Vy', 'Yến'];
        $streets = ['Đường Số 1', 'Đường Số 5', 'Đường Số 9', 'Đường Số 14', 'Đường Số 21', 'Phố Mẫu Số 2', 'Phố Mẫu Số 8'];
        $areas = ['Quận 1, TP. Hồ Chí Minh', 'Quận 3, TP. Hồ Chí Minh', 'TP. Thủ Đức', 'Quận Bình Thạnh, TP. Hồ Chí Minh', 'Quận Hoàn Kiếm, Hà Nội', 'Quận Cầu Giấy, Hà Nội', 'Quận Hải Châu, Đà Nẵng', 'Quận Sơn Trà, Đà Nẵng'];

        $branchIds = array_keys($this->branches);
        for ($i = 1; $i <= 180; $i++) {
            $name = $this->pick($family) . ' ' . $this->pick($middle) . ' ' . $this->pick($given);
            $female = in_array(Str::of($name)->explode(' ')[1], ['Thị', 'Ngọc', 'Thu', 'Mỹ'], true);
            $customer = Customer::create([
                'code' => code_generate('KH', $i - 1),
                'name' => $name,
                'phone' => '0900' . sprintf('%06d', $i),
                'email' => $this->chance(0.4) ? 'khach' . $i . '@example.test' : null,
                'sex' => $female ? 1 : 0,
                'birthday' => now()->subYears(mt_rand(18, 60))->subDays(mt_rand(0, 364))->toDateString(),
                'address' => mt_rand(1, 199) . ' ' . $this->pick($streets) . ', ' . $this->pick($areas),
                'type' => $this->chance(0.08) ? 1 : 0,
                'company' => null,
                'status' => 1,
                'branch_id' => $this->pick($branchIds),
                'user_id' => $this->admin->id,
            ]);
            $group = match (true) {
                $i <= 12 => 'Khách VIP',
                $i <= 20 => 'Khách sỉ',
                $i <= 24 => 'Nhân viên',
                $i <= 110 => 'Thành viên',
                default => 'Khách lẻ',
            };
            $customer->customerGroups()->attach($groups[$group]->id);
            $this->customers[] = $customer;
            if ($i <= 40) {
                $this->loyalCustomers[] = $customer;
            }
        }
        $this->count('Khách hàng', count($this->customers));

        $vendorGroups = [];
        foreach (['Thời trang', 'Mỹ phẩm', 'Điện tử', 'Gia dụng', 'Thực phẩm'] as $name) {
            $vendorGroups[$name] = VendorGroup::create(['name' => "Nhà cung cấp {$name}", 'note' => null, 'user_created_id' => $this->admin->id]);
        }

        $vendors = [
            ['Công ty TNHH May mặc Sao Phương Nam', 'Thời trang'], ['Công ty CP Thời trang Đông Hải', 'Thời trang'], ['Tổng kho Giày dép Miền Tây', 'Thời trang'],
            ['Công ty TNHH Mỹ phẩm Hoa Sen Xanh', 'Mỹ phẩm'], ['Nhà phân phối Mỹ phẩm Ánh Dương', 'Mỹ phẩm'],
            ['Công ty CP Điện tử Minh Long Digital', 'Điện tử'], ['Công ty TNHH Phụ kiện số Bắc Nam', 'Điện tử'],
            ['Tổng kho Gia dụng Thành Đạt', 'Gia dụng'], ['Công ty TNHH Điện gia dụng An Phú', 'Gia dụng'],
            ['Công ty TNHH Thực phẩm Xanh Việt', 'Thực phẩm'], ['Nhà phân phối Đồ uống Sông Hàn', 'Thực phẩm'],
        ];
        $mainId = array_key_first($this->branches);
        foreach ($vendors as $i => [$name, $root]) {
            $vendor = Vendor::create([
                'branch_id' => $mainId,
                'code' => code_generate('NCC', $i),
                'name' => $name,
                'phone' => '0280' . sprintf('%06d', 100 + $i),
                'email' => 'ncc' . ($i + 1) . '@example.test',
                'address' => mt_rand(10, 300) . ' Đường Số ' . mt_rand(1, 30) . ', Khu công nghiệp Mẫu ' . chr(65 + $i),
                'company' => $name,
                'vat' => '0399' . sprintf('%06d', $i + 1),
                'status' => 'active',
                'total' => 0,
                'debt' => 0,
                'total_purchase' => 0,
            ]);
            $vendor->group()->attach($vendorGroups[$root]->id);
            $this->vendorsByRoot[$root][] = $vendor->id;
        }
        $this->count('Nhà cung cấp', count($vendors));

        $this->ensurePaymentSetup();
    }

    private function ensurePaymentSetup(): void
    {
        $methods = [
            ['code' => 'cash', 'name' => 'Tiền mặt', 'target_payment_status' => PaymentMethod::STATUS_COMPLETED],
            ['code' => 'bank', 'name' => 'Chuyển khoản', 'target_payment_status' => PaymentMethod::STATUS_COMPLETED],
            ['code' => 'card', 'name' => 'Quẹt thẻ', 'target_payment_status' => PaymentMethod::STATUS_COMPLETED],
            ['code' => 'cod', 'name' => 'Thu hộ (COD)', 'target_payment_status' => PaymentMethod::STATUS_PENDING],
        ];
        foreach ($methods as $method) {
            if (! PaymentMethod::query()->where('code', $method['code'])->exists()) {
                PaymentMethod::create($method + ['is_active' => 1]);
            }
        }
        $this->methods = PaymentMethod::query()->get()->keyBy('code');

        foreach ([['Tại cửa hàng', true], ['Website', false], ['Shopee', false], ['Facebook', false]] as $i => [$name, $default]) {
            SaleChannel::query()->firstOrCreate(['name' => $name], ['is_active' => 1, 'is_default' => $default, 'sort_order' => $i]);
        }
        // Most sales happen at the counter: make sure the in-store channel is the default.
        if (! SaleChannel::query()->where('is_default', true)->exists()) {
            SaleChannel::query()->where('name', 'Tại cửa hàng')->update(['is_default' => true]);
        }
        $this->channels = SaleChannel::query()->where('is_active', 1)->get();

        foreach ([['Giao Hàng Mẫu Nhanh', 'company', 'GHMN'], ['Viettel Post (demo)', 'company', 'VTPD'], ['Shipper nội bộ', 'person', 'NOIBO']] as [$name, $type, $code]) {
            PaymentPartnerDelivery::query()->firstOrCreate(['code' => $code], ['name' => $name, 'type' => $type]);
        }
        $this->partners = PaymentPartnerDelivery::query()->get();
    }

    // ---------------------------------------------------------------- simulate

    private function simulate(Carbon $start, int $days): void
    {
        $mainId = array_key_first($this->branches);
        $today = $this->realNow->copy()->startOfDay();

        foreach ($this->branches as $branchId => $branch) {
            $this->at($this->openedOn[$branchId]->copy()->setTime(7, 45));
            if ($branchId === $mainId || $this->openedOn[$branchId]->equalTo($start)) {
                $this->openingCount($branchId, $branchId === $mainId ? 2.0 : 1.0);
            }
        }

        for ($day = $start->copy(); $day->lte($today); $day->addDay()) {
            $events = [];

            foreach ($this->branches as $branchId => $branch) {
                if ($day->lt($this->openedOn[$branchId])) {
                    continue;
                }

                if ($day->equalTo($this->openedOn[$branchId]) && ! $day->equalTo($start)) {
                    $events[] = [$day->copy()->setTime(7, 50), 'opening', $branchId];
                }

                $base = $this->branchSize[$branchId];
                $weekend = $day->isWeekend() ? 1.4 : 1.0;
                $sales = max(1, (int) round($base * $weekend * mt_rand(65, 135) / 100));
                for ($i = 0; $i < $sales; $i++) {
                    $events[] = [$day->copy()->setTime(mt_rand(8, 21), mt_rand(0, 59), mt_rand(0, 59)), 'sale', $branchId];
                }

                if ($day->isMonday()) {
                    $events[] = [$day->copy()->setTime(9, mt_rand(0, 30)), $branchId === $mainId || $this->branches[$branchId]->name === 'Cửa hàng Hoàn Kiếm' ? 'restock' : 'transfer', $branchId];
                }
                if ($day->isThursday() && $branchId === $mainId) {
                    $events[] = [$day->copy()->setTime(9, 15), 'restock', $branchId];
                }
                if ($day->day === 1) {
                    $events[] = [$day->copy()->setTime(8, 5), 'salary', $branchId];
                }
                if ($day->day === 5) {
                    $events[] = [$day->copy()->setTime(10, 10), 'rent', $branchId];
                }
                if ($day->day === 12) {
                    $events[] = [$day->copy()->setTime(14, 20), 'utilities', $branchId];
                }
                if ($day->isLastOfMonth() || $day->equalTo($today)) {
                    $events[] = [$day->copy()->setTime(21, 45), 'count', $branchId];
                }
                if ($this->chance(0.04)) {
                    $events[] = [$day->copy()->setTime(mt_rand(9, 18), mt_rand(0, 59)), 'misc', $branchId];
                }
            }

            foreach ($this->scheduled as $key => $job) {
                if ($job['at']->isSameDay($day)) {
                    $events[] = [$job['at'], $job['kind'], $job['data']];
                    unset($this->scheduled[$key]);
                }
            }

            usort($events, fn ($a, $b) => $a[0] <=> $b[0]);

            foreach ($events as [$at, $kind, $data]) {
                if ($at->gt($this->realNow)) {
                    continue;
                }
                $this->at($at);

                try {
                    match ($kind) {
                        'opening' => $this->openNewBranch($data),
                        'sale' => $this->sale($data),
                        'restock' => $this->restock($data),
                        'transfer' => $this->transferTo($data),
                        'count' => $this->monthlyCount($data),
                        'salary', 'rent', 'utilities', 'misc' => $this->cashEntry($kind, $data),
                        'return' => $this->salesReturn($data),
                        'cancel' => $this->cancelSale($data),
                        'cod' => $this->completeCod($data),
                        'purchase-return' => $this->purchaseReturn($data),
                    };
                } catch (Throwable $exception) {
                    // Not possible at this moment (e.g. stock already gone): skip it and
                    // reload the stock mirror, which may hold a rolled-back reservation.
                    $this->mirror = [];
                    $this->skip($kind, $exception);
                }
            }
        }
    }

    private function openingCount(int $branchId, float $factor): void
    {
        $lines = [];
        foreach ($this->goods as $product) {
            $target = (int) round(mt_rand($product->min_quantity * 2, $product->max_quantity) * $factor / 2);
            $lines[$product->id] = ['amount' => $this->qty($branchId, $product->id), 'actual_stock' => max(0, $target), 'note' => 'Tồn đầu kỳ'];
        }

        $stock = new Stock([
            'code' => code_generate('KK', Stock::withTrashed()->max('id') ?? 0),
            'branch_id' => $branchId,
            'balance_date' => now(),
            'user_id' => $this->staff[$branchId]['manager']->id,
            'user_created_id' => $this->staff[$branchId]['manager']->id,
            'status' => 'completed',
            'note' => 'Kiểm kê tồn đầu kỳ',
        ]);
        StockInventorySupport::save($stock, $lines);
        $this->count('Phiếu kiểm kho');
    }

    private function openNewBranch(int $branchId): void
    {
        $mainId = array_key_first($this->branches);
        $lines = [];
        foreach ($this->goods->shuffle()->take(45) as $product) {
            $available = $this->qty($mainId, $product->id);
            $amount = min((int) floor($available * 0.4), (int) round($product->max_quantity * 0.6));
            if ($amount > 0) {
                $lines[] = [$product, $amount];
            }
        }
        $this->postTransfer($mainId, $branchId, $lines, 'Hàng khai trương chi nhánh mới');
        $this->restock($branchId);
    }

    // ------------------------------------------------------------------ sales

    private function sale(int $branchId): void
    {
        $lines = [];
        $wanted = $this->weighted([1 => 45, 2 => 30, 3 => 17, 4 => 8]);
        $used = [];

        for ($i = 0; $i < $wanted * 3 && count($lines) < $wanted; $i++) {
            $roll = mt_rand(1, 100);
            $product = match (true) {
                $roll <= 7 => $this->combos->random(),
                $roll <= 11 => $this->services->random(),
                default => $this->goods->random(),
            };

            if (isset($used[$product->id])) {
                continue;
            }

            $qty = $product->price < 100000 ? $this->weighted([1 => 50, 2 => 30, 3 => 15, 5 => 5]) : $this->weighted([1 => 85, 2 => 15]);

            if ($product->type !== 'service' && $this->sellable($branchId, $product) < $qty) {
                continue;
            }

            $used[$product->id] = true;
            $discount = $this->chance(0.12) ? [(int) (round($product->price * $qty * mt_rand(5, 15) / 100 / 1000) * 1000), 'number'] : [0, 'number'];
            $lines[] = [$product, $qty, $product->price, $discount];
            $this->reserve($branchId, $product, $qty);
        }

        if ($lines === []) {
            return;
        }

        $cashier = $this->pick($this->staff[$branchId]['cashiers']);
        $customer = match (true) {
            $this->chance(0.35) => $this->pick($this->loyalCustomers),
            $this->chance(0.45) => $this->pick($this->customers),
            default => null,
        };
        $channel = $this->weightedItem($this->channels->all(), fn ($channel) => $channel->is_default ? 70 : 10);
        $online = ! $channel->is_default;
        $delivery = $online && $customer !== null;
        $method = $delivery && $this->chance(0.7) ? 'cod' : $this->weighted(['cash' => 50, 'bank' => 35, 'card' => 15]);
        $method = $this->methods->has($method) ? $method : $this->methods->keys()->first();

        $payment = $this->postSale($branchId, $cashier, $customer, $lines, $method, $channel, $delivery);

        if ($payment->status === 'success' && $customer && $this->chance(0.03)) {
            $this->schedule(now()->copy()->addDays(mt_rand(1, 6))->setTime(mt_rand(9, 19), mt_rand(0, 59)), 'return', $payment->id);
        } elseif ($payment->status === 'success' && $this->chance(0.006)) {
            $this->schedule(now()->copy()->addMinutes(mt_rand(10, 90)), 'cancel', $payment->id);
        }

        if ($method === 'cod') {
            $this->schedule(now()->copy()->addDays(mt_rand(1, 4))->setTime(mt_rand(10, 18), mt_rand(0, 59)), 'cod', $payment->id);
        }
    }

    /**
     * @param list<array{0: Product, 1: int, 2: int, 3: array{0: int, 1: string}}> $lines
     */
    private function postSale(int $branchId, User $cashier, ?Customer $customer, array $lines, string $method, SaleChannel $channel, bool $delivery): Payment
    {
        return DB::transaction(function () use ($branchId, $cashier, $customer, $lines, $method, $channel, $delivery) {
            $items = collect($lines)->map(fn ($line) => [
                'product' => $line[0], 'product_id' => $line[0]->id, 'amount' => $line[1], 'value' => $line[2],
                'discount_value' => $line[3][0], 'discount_type' => $line[3][1],
                'total' => discount_value($line[1] * $line[2], $line[3][0], $line[3][1]),
            ]);
            $totalCost = (int) $items->sum('total');
            [$discountValue, $discountType] = $customer && $this->chance(0.1) ? [mt_rand(5, 10), 'percent'] : [0, 'number'];
            $value = (int) round(discount_value($totalCost, $discountValue, $discountType));
            $collected = $this->methods[$method]->target_payment_status === PaymentMethod::STATUS_COMPLETED;
            $label = $this->methods[$method]->name;

            $payment = Payment::create([
                'branch_id' => $branchId,
                'customer_id' => $customer?->id,
                'code' => '',
                'amount_products' => (int) $items->sum('amount'),
                'total_cost' => $totalCost,
                'discount_value' => $discountValue,
                'discount_type' => $discountType,
                'value' => $value,
                'value_payment' => $collected ? $value : 0,
                'type_payment' => [['method' => $method, 'value' => $collected ? $value : 0, 'label' => $label]],
                'is_delivery' => $delivery ? 1 : 0,
                'sale_channel_id' => $channel->id,
                'is_cod' => $method === 'cod' ? 1 : 0,
                'user_id' => $cashier->id,
                'author_id' => $cashier->id,
                'status' => $collected ? 'success' : 'pending',
                'completed_at' => $collected ? now() : null,
            ]);

            $saved = collect();
            foreach ($items as $item) {
                $product = $item['product'];
                unset($item['product']);
                $saved->push(PaymentProduct::create($item + ['product_payment_id' => $payment->id])->setRelation('product', $product));
            }
            ProductInventorySupport::exportPaymentItems($saved, $payment);

            if ($delivery) {
                PaymentDelivery::create([
                    'product_payment_id' => $payment->id,
                    'code' => 'VD' . sprintf('%07d', $payment->id),
                    'partner_delivery_id' => $this->partners->random()->id,
                    'type' => 'normal',
                    'value' => $this->pick([0, 15000, 20000, 25000, 30000]),
                    'date' => now()->toDateString(),
                    'status' => $collected ? 'success' : 'delivery',
                ]);
            }

            if ($collected) {
                $typeName = match ($method) {
                    'cash' => 'Thu tiền mặt',
                    'bank', 'transfer' => 'Thu chuyển khoản',
                    default => 'Thu qua ' . $label,
                };
                Receipt::create([
                    'code' => code_generate('PT', Receipt::max('id') ?? 0),
                    'branch_id' => $branchId,
                    'date' => now(),
                    'type_id' => $this->accountingType($typeName, 'receipt'),
                    'value' => $value,
                    'user_id' => $cashier->id,
                    'user_created_id' => $cashier->id,
                    'finance_type' => Payment::class,
                    'finance_id' => $payment->id,
                    'note' => 'Thu tiền bán hàng đơn ' . $payment->code,
                    'business_result' => 1,
                ]);
            }

            $this->count('Hoá đơn bán');

            return $payment;
        });
    }

    private function completeCod(int $paymentId): void
    {
        $payment = Payment::find($paymentId);
        if (! $payment || $payment->status !== 'pending') {
            return;
        }

        DB::transaction(function () use ($payment) {
            $typePayment = $payment->type_payment ?? [];
            if (isset($typePayment[0])) {
                $typePayment[0]['value'] = $payment->value;
            }
            $payment->forceFill(['status' => 'success', 'value_payment' => $payment->value, 'completed_at' => now(), 'type_payment' => $typePayment])->save();
            PaymentDelivery::query()->where('product_payment_id', $payment->id)->update(['status' => 'success']);
        });
    }

    private function cancelSale(int $paymentId): void
    {
        $payment = Payment::with('products')->find($paymentId);
        if (! $payment || in_array($payment->status, ['cancel', 'cancelled'], true)) {
            return;
        }

        DB::transaction(function () use ($payment) {
            PaymentInventorySupport::restoreExportedStock($payment);
            $payment->status = 'cancel';
            $payment->note = 'Khách đổi ý, huỷ ngay tại quầy';
            $payment->save();
        });
        $this->syncMirror($payment->branch_id);
        $this->count('Hoá đơn huỷ');
    }

    private function salesReturn(int $paymentId): void
    {
        $payment = Payment::with('products.product')->find($paymentId);
        if (! $payment || $payment->status !== 'success' || ! $payment->customer_id) {
            return;
        }

        $line = $payment->products->first(fn ($line) => $line->product?->type === 'product');
        if (! $line) {
            return;
        }

        DB::transaction(function () use ($payment, $line) {
            $amount = 1;
            $value = (int) round($line->total / max(1, $line->amount));
            $refund = SalesReturn::create([
                'code' => code_generate('THX', SalesReturn::max('id') ?? 0),
                'branch_id' => $payment->branch_id,
                'customer_id' => $payment->customer_id,
                'product_payment_id' => $payment->id,
                'total' => $value * $amount,
                'value' => $value * $amount,
                'status' => 'success',
                'note' => $this->pick(['Lỗi đường may', 'Không vừa size', 'Khách đổi ý trong 7 ngày', 'Sản phẩm trầy xước khi giao']),
                'user_created_id' => $this->staff[$payment->branch_id]['manager']->id,
            ]);
            DocumentInventorySupport::amendReceipt($refund, [], [['product_id' => $line->product_id, 'branch_id' => $payment->branch_id, 'amount' => $amount]]);
            SalesReturnLine::create([
                'product_payment_refund_id' => $refund->id,
                'product_payment_id' => $payment->id,
                'product_id' => $line->product_id,
                'amount' => $amount,
                'price' => $value,
                'value' => $value * $amount,
            ]);
        });
        $this->syncMirror($payment->branch_id);
        $this->count('Phiếu trả hàng bán');
    }

    // --------------------------------------------------------------- purchases

    private function restock(int $branchId): void
    {
        $low = $this->goods->filter(fn ($p) => $this->qty($branchId, $p->id) <= $p->min_quantity * 2)->values();
        if ($low->isEmpty()) {
            $low = $this->goods->random(min(6, $this->goods->count()));
        }

        foreach ($low->groupBy(fn ($p) => $this->rootOf[$p->id]) as $root => $products) {
            $lines = $products->take(10)->map(fn ($p) => [$p, max(1, $p->max_quantity - $this->qty($branchId, $p->id))])->all();
            $this->postPurchase($branchId, $this->pick($this->vendorsByRoot[$root]), $lines);
        }
    }

    /**
     * @param list<array{0: Product, 1: int}> $lines
     */
    private function postPurchase(int $branchId, int $vendorId, array $lines): void
    {
        $user = $branchId === array_key_first($this->branches) && $this->warehouse ? $this->warehouse : $this->staff[$branchId]['manager'];

        $purchase = DB::transaction(function () use ($branchId, $vendorId, $lines, $user) {
            $rows = collect($lines)->map(function ($line) {
                [$product, $amount] = $line;
                $price = (int) (round($product->cost * mt_rand(95, 103) / 100 / 100) * 100);

                return ['product_id' => $product->id, 'amount' => $amount, 'price' => $price, 'value' => $price * $amount];
            });
            $total = (int) $rows->sum('value');
            [$discountValue, $discountType] = $this->chance(0.3) ? [$this->pick([2, 3, 5]), 'percent'] : [0, 'percent'];
            $needPay = (int) round(discount_value($total, $discountValue, $discountType));
            $paid = match ($this->weighted(['full' => 60, 'half' => 25, 'none' => 15])) {
                'full' => $needPay,
                'half' => (int) (round($needPay / 2 / 1000) * 1000),
                default => 0,
            };

            $purchase = Purchase::create([
                'code' => code_generate('NH', Purchase::max('id') ?? 0),
                'branch_id' => $branchId,
                'vendor_id' => $vendorId,
                'discount_value' => $discountValue,
                'discount_type' => $discountType,
                'user_created_id' => $user->id,
                'status' => 'success',
                'total' => $total,
                'need_pay' => $needPay,
                'value' => $paid,
                'note' => null,
            ]);

            DocumentInventorySupport::amendReceipt($purchase, [], $rows->map(fn ($row) => ['product_id' => $row['product_id'], 'branch_id' => $branchId, 'amount' => $row['amount']])->all());

            foreach ($rows as $row) {
                PurchaseProduct::create($row + ['vendor_purchase_id' => $purchase->id, 'branch_id' => $branchId]);
            }

            $vendor = Vendor::findOrFail($vendorId);
            $vendor->increment('debt', $needPay - $paid);
            $vendor->increment('total', $needPay);
            $vendor->increment('total_purchase', $needPay);

            if ($paid > 0) {
                CashPayment::create([
                    'code' => code_generate('PC', CashPayment::max('id') ?? 0),
                    'branch_id' => $branchId,
                    'date' => now(),
                    'type_id' => $this->accountingType('Chi nhập hàng', 'payment'),
                    'value' => $paid,
                    'user_id' => $user->id,
                    'user_created_id' => $user->id,
                    'finance_type' => Purchase::class,
                    'finance_id' => $purchase->id,
                    'note' => 'Chi tiền nhập hàng đơn ' . $purchase->code,
                    'business_result' => 1,
                ]);
            }

            return $purchase;
        });

        $this->purchases[] = $purchase;
        $this->syncMirror($branchId);
        $this->count('Phiếu nhập hàng');

        // Now and then some goods turn out defective and go back to the supplier.
        if (count($this->purchases) % 13 === 4) {
            $this->schedule(now()->copy()->addDays(3)->setTime(15, 0), 'purchase-return', $purchase->id);
        }
    }

    private function purchaseReturn(int $purchaseId): void
    {
        $purchase = Purchase::with('products')->find($purchaseId);
        if (! $purchase || $purchase->status !== 'success') {
            return;
        }

        $line = $purchase->products->first(fn ($line) => $this->qty($purchase->branch_id, $line->product_id) >= 2);
        if (! $line) {
            return;
        }

        DB::transaction(function () use ($purchase, $line) {
            $amount = min(2, (int) $line->amount);
            $value = (int) $line->price * $amount;
            $refund = PurchaseReturn::create([
                'code' => code_generate('THN', PurchaseReturn::max('id') ?? 0),
                'branch_id' => $purchase->branch_id,
                'vendor_id' => $purchase->vendor_id,
                'purchase_id' => $purchase->id,
                'total' => $value,
                'value' => $value,
                'status' => 'success',
                'note' => 'Trả hàng lỗi nhà sản xuất',
                'user_created_id' => $purchase->user_created_id,
            ]);
            DocumentInventorySupport::amendReceipt($refund, [], [['product_id' => $line->product_id, 'branch_id' => $purchase->branch_id, 'amount' => $amount, 'increase' => false]]);
            PurchaseReturnLine::create(['vendor_purchase_refund_id' => $refund->id, 'product_id' => $line->product_id, 'amount' => $amount, 'price' => $line->price, 'value' => $value]);

            if ($purchase->vendor_id) {
                $vendor = Vendor::findOrFail($purchase->vendor_id);
                $vendor->decrement('debt', $value);
                $vendor->decrement('total', $value);
            }
            $purchase->update(['status' => 'refund']);
        });
        $this->syncMirror($purchase->branch_id);
        $this->count('Phiếu trả hàng nhập');
    }

    // --------------------------------------------------------------- transfers

    private function transferTo(int $branchId): void
    {
        $mainId = array_key_first($this->branches);
        $lines = [];
        foreach ($this->goods as $product) {
            $have = $this->qty($branchId, $product->id);
            if ($have > $product->min_quantity * 2) {
                continue;
            }
            $want = (int) round($product->max_quantity * 0.6) - $have;
            $spare = $this->qty($mainId, $product->id) - $product->min_quantity * 2;
            $amount = min($want, $spare);
            if ($amount > 0) {
                $lines[] = [$product, $amount];
            }
        }

        if ($lines === []) {
            return;
        }

        $this->postTransfer($mainId, $branchId, array_slice($lines, 0, 25), 'Bổ sung hàng định kỳ');
    }

    /**
     * @param list<array{0: Product, 1: int}> $lines
     */
    private function postTransfer(int $from, int $to, array $lines, string $note): void
    {
        if ($lines === []) {
            return;
        }

        DB::transaction(function () use ($from, $to, $lines, $note) {
            $transfer = Transfer::create([
                'code' => code_generate('CH', Transfer::max('id') ?? 0),
                'status' => 'success',
                'branch_id' => $from,
                'form_branch_id' => $from,
                'to_branch_id' => $to,
                'user_created_id' => $this->warehouse?->id ?? $this->admin->id,
                'date_send' => now()->copy()->subHours(4),
                'date_take' => now(),
                'note' => $note,
            ]);
            $movements = [];
            foreach ($lines as [$product, $amount]) {
                $movements[] = ['product_id' => $product->id, 'branch_id' => $from, 'amount' => $amount, 'increase' => false];
                $movements[] = ['product_id' => $product->id, 'branch_id' => $to, 'amount' => $amount, 'increase' => true];
            }
            DocumentInventorySupport::amendReceipt($transfer, [], $movements);
            foreach ($lines as [$product, $amount]) {
                TransferProduct::create(['vendor_transfer_id' => $transfer->id, 'product_id' => $product->id, 'amount' => $amount, 'price' => $product->cost, 'value' => $product->cost * $amount]);
            }
        });
        $this->syncMirror($from);
        $this->syncMirror($to);
        $this->count('Phiếu chuyển hàng');
    }

    // ------------------------------------------------------------ stock counts

    private function monthlyCount(int $branchId): void
    {
        $this->syncMirror($branchId);
        $lines = [];
        foreach ($this->goods->random(min(10, $this->goods->count())) as $product) {
            $current = $this->qty($branchId, $product->id);
            $difference = $this->weighted([0 => 70, -1 => 18, -2 => 6, 1 => 6]);
            $lines[$product->id] = ['amount' => $current, 'actual_stock' => max(0, $current + $difference), 'note' => $difference < 0 ? 'Hư hỏng / thất thoát' : null];
        }

        $stock = new Stock([
            'code' => code_generate('KK', Stock::withTrashed()->max('id') ?? 0),
            'branch_id' => $branchId,
            'balance_date' => now(),
            'user_id' => $this->staff[$branchId]['manager']->id,
            'user_created_id' => $this->staff[$branchId]['manager']->id,
            'status' => 'completed',
            'note' => 'Kiểm kê cuối tháng ' . now()->format('m/Y'),
        ]);
        StockInventorySupport::save($stock, $lines);
        $this->syncMirror($branchId);
        $this->count('Phiếu kiểm kho');
    }

    // --------------------------------------------------------------- cash book

    private function cashEntry(string $kind, int $branchId): void
    {
        $manager = $this->staff[$branchId]['manager'];
        $size = $this->branchSize[$branchId];

        [$model, $prefix, $type, $kindName, $value, $note] = match ($kind) {
            'salary' => [CashPayment::class, 'PC', 'payment', 'Chi lương nhân viên', $size * mt_rand(2600, 3000) * 1000, 'Lương tháng ' . now()->copy()->subMonth()->format('m/Y')],
            'rent' => [CashPayment::class, 'PC', 'payment', 'Chi thuê mặt bằng', $size * 2500000, 'Tiền thuê mặt bằng tháng ' . now()->format('m/Y')],
            'utilities' => [CashPayment::class, 'PC', 'payment', 'Chi điện nước', $size * mt_rand(320, 480) * 1000, 'Điện nước tháng ' . now()->copy()->subMonth()->format('m/Y')],
            default => $this->chance(0.5)
                ? [Receipt::class, 'PT', 'receipt', 'Thu khác', mt_rand(2, 30) * 50000, $this->pick(['Thu tiền thanh lý kệ trưng bày', 'Thu phí gửi đồ', 'Hoàn tiền đặt cọc vật tư'])]
                : [CashPayment::class, 'PC', 'payment', 'Chi khác', mt_rand(2, 40) * 50000, $this->pick(['Mua văn phòng phẩm', 'Sửa máy lạnh', 'In tờ rơi khuyến mãi', 'Chi tiếp khách'])],
        };

        $model::create([
            'code' => code_generate($prefix, $model::max('id') ?? 0),
            'branch_id' => $branchId,
            'date' => now(),
            'type_id' => $this->accountingType($kindName, $type),
            'value' => $value,
            'user_id' => $manager->id,
            'user_created_id' => $this->accountant?->id ?? $manager->id,
            'finance_type' => $kind === 'rent' ? PayPerson::class : User::class,
            'finance_id' => $kind === 'rent' ? $this->landlord($branchId) : $manager->id,
            'note' => $note,
            'business_result' => 1,
        ]);
        $this->count($type === 'receipt' ? 'Phiếu thu khác' : 'Phiếu chi');
    }

    /** @var array<int, int> */
    private array $landlords = [];

    private function landlord(int $branchId): int
    {
        return $this->landlords[$branchId] ??= PayPerson::create([
            'name' => 'Chủ mặt bằng ' . $this->branches[$branchId]->name,
            'phone' => '0900' . sprintf('%06d', 800000 + $branchId),
            'address' => $this->branches[$branchId]->address,
            'note' => 'Hợp đồng thuê 3 năm',
        ])->id;
    }

    /** @var array<string, int> */
    private array $accountingTypes = [];

    private function accountingType(string $name, string $type): int
    {
        return $this->accountingTypes[$name] ??= AccountingType::query()->firstOrCreate(['name' => $name], ['type' => $type])->id;
    }

    // ---------------------------------------------------------------- projects

    private function seedProjects(): void
    {
        $this->at(now());
        $branchIds = array_keys($this->branches);
        $lastBranch = end($branchIds);
        $staff = collect($this->staff)->flatMap(fn ($s) => [$s['manager'], ...$s['cashiers']])->push($this->warehouse, $this->accountant)->filter()->values();

        $projects = [
            ['Khai trương Cửa hàng Hải Châu', $lastBranch, 'completed', 'high', -70, -40, [
                ['Ký hợp đồng thuê mặt bằng', 'done'], ['Thi công, trang trí cửa hàng', 'done'], ['Tuyển và đào tạo nhân viên', 'done'],
                ['Chuyển hàng khai trương từ kho Quận 1', 'done'], ['Chạy quảng cáo khai trương', 'done'], ['Tổng kết doanh thu tuần đầu', 'done'],
            ]],
            ['Chiến dịch khuyến mãi cuối năm', $branchIds[0], 'active', 'urgent', -10, 45, [
                ['Chốt danh sách sản phẩm giảm giá', 'done'], ['Thiết kế banner, poster', 'review'], ['Thiết lập combo quà tặng', 'in_progress'],
                ['Đặt hàng bổ sung nhóm Mỹ phẩm', 'in_progress'], ['Đào tạo thu ngân về chương trình', 'todo'], ['Đăng bài trên Facebook, Shopee', 'todo'], ['Đo hiệu quả theo tuần', 'backlog'],
            ]],
            ['Kiểm kê và sắp xếp lại kho Quận 1', $branchIds[0], 'active', 'medium', -20, 15, [
                ['Phân khu lại kệ hàng theo nhóm', 'done'], ['Dán nhãn mã vạch kệ', 'in_progress'], ['Kiểm kê toàn bộ nhóm Điện tử', 'todo'], ['Xử lý hàng tồn lâu', 'todo'],
            ]],
            ['Chương trình khách hàng thân thiết', $branchIds[1], 'planning', 'medium', 10, 70, [
                ['Xây dựng quy chế tích điểm', 'todo'], ['Phân nhóm khách VIP', 'backlog'], ['In thẻ thành viên', 'backlog'],
            ]],
            ['Mở rộng kênh bán online', $branchIds[2], 'on_hold', 'low', -30, 60, [
                ['Chụp ảnh sản phẩm', 'done'], ['Mở gian hàng Shopee', 'in_progress'], ['Kết nối đơn vị giao hàng', 'todo'],
            ]],
        ];

        foreach ($projects as [$name, $branchId, $status, $priority, $startOffset, $endOffset, $tasks]) {
            $project = Project::create([
                'name' => $name,
                'description' => 'Dự án mẫu cho bản demo.',
                'status' => $status,
                'priority' => $priority,
                'planned_start_date' => now()->copy()->addDays($startOffset)->toDateString(),
                'planned_end_date' => now()->copy()->addDays($endOffset)->toDateString(),
                'actual_start_date' => $startOffset <= 0 ? now()->copy()->addDays($startOffset)->toDateString() : null,
                'actual_end_date' => $status === 'completed' ? now()->copy()->addDays($endOffset)->toDateString() : null,
                'budget' => mt_rand(10, 120) * 1000000,
                'progress_percentage' => 0,
                'branch_id' => $branchId,
                'created_by' => $this->admin->id,
                'updated_by' => $this->admin->id,
            ]);

            foreach ($tasks as $order => [$taskName, $taskStatus]) {
                $plannedStart = now()->copy()->addDays($startOffset + $order * 3);
                $estimated = mt_rand(4, 24);
                Task::create([
                    'project_id' => $project->id,
                    'name' => $taskName,
                    'description' => null,
                    'status' => $taskStatus,
                    'priority' => $this->pick(['low', 'medium', 'medium', 'high', 'urgent']),
                    'assigned_to' => $staff->random()->id,
                    'planned_start_date' => $plannedStart->toDateString(),
                    'planned_end_date' => $plannedStart->copy()->addDays(mt_rand(2, 6))->toDateString(),
                    'actual_start_date' => in_array($taskStatus, ['in_progress', 'review', 'done'], true) ? $plannedStart->toDateString() : null,
                    'actual_end_date' => $taskStatus === 'done' ? $plannedStart->copy()->addDays(mt_rand(2, 5))->toDateString() : null,
                    'estimated_hours' => $estimated,
                    'actual_hours' => $taskStatus === 'done' ? $estimated + mt_rand(-2, 4) : ($taskStatus === 'in_progress' ? (int) round($estimated / 2) : 0),
                    'progress_percentage' => match ($taskStatus) { 'done' => 100, 'review' => 90, 'in_progress' => mt_rand(20, 70), default => 0 },
                    'sort_order' => $order,
                    'branch_id' => $branchId,
                    'created_by' => $this->admin->id,
                    'updated_by' => $this->admin->id,
                ]);
            }

            $project->update(['progress_percentage' => (int) round(Task::query()->where('project_id', $project->id)->avg('progress_percentage'))]);
        }

        $this->count('Dự án', count($projects));
        $this->count('Công việc', Task::count());
    }

    // ----------------------------------------------------------------- helpers

    /** @var array<int, array<int, int>> branch id => product id => qty (mirror of product_branches) */
    private array $mirror = [];

    private function qty(int $branchId, int $productId): int
    {
        if (! isset($this->mirror[$branchId])) {
            $this->syncMirror($branchId);
        }

        return $this->mirror[$branchId][$productId] ?? 0;
    }

    private function syncMirror(int $branchId): void
    {
        $this->mirror[$branchId] = ProductBranch::query()->where('branch_id', $branchId)->pluck('qty', 'product_id')->map(fn ($qty) => (int) $qty)->all();
    }

    private function sellable(int $branchId, Product $product): int
    {
        if ($product->type === 'service') {
            return PHP_INT_MAX;
        }

        if ($product->type === 'combo') {
            $available = PHP_INT_MAX;
            foreach ($product->elements as $element) {
                $available = min($available, intdiv($this->qty($branchId, $element->element_id), max(1, (int) $element->qty)));
            }

            return $available === PHP_INT_MAX ? 0 : $available;
        }

        return $this->qty($branchId, $product->id);
    }

    private function reserve(int $branchId, Product $product, int $qty): void
    {
        if ($product->type === 'product') {
            $this->mirror[$branchId][$product->id] = $this->qty($branchId, $product->id) - $qty;
        } elseif ($product->type === 'combo') {
            foreach ($product->elements as $element) {
                $this->mirror[$branchId][$element->element_id] = $this->qty($branchId, $element->element_id) - $qty * (int) $element->qty;
            }
        }
    }

    /**
     * Run something on a later day (today's events are already planned, so a
     * same-day job moves to the next morning).
     */
    private function schedule(Carbon $at, string $kind, mixed $data): void
    {
        if ($at->isSameDay(now())) {
            $at = now()->copy()->addDay()->setTime(8, mt_rand(0, 45));
        }

        $this->scheduled[] = ['at' => $at, 'kind' => $kind, 'data' => $data];
    }

    private function at(Carbon $moment): void
    {
        Carbon::setTestNow($moment);
    }

    /** @var array<string, int> "kind: reason" => times */
    private array $skips = [];

    private function skip(string $kind, Throwable $exception): void
    {
        $reason = $kind . ': ' . Str::limit(preg_replace('/\s+/', ' ', $exception->getMessage()), 160);
        $this->skips[$reason] = ($this->skips[$reason] ?? 0) + 1;
    }

    private function count(string $label, int $n = 1): void
    {
        $this->counts[$label] = ($this->counts[$label] ?? 0) + $n;
    }

    private function chance(float $probability): bool
    {
        return mt_rand(1, 10000) <= (int) round($probability * 10000);
    }

    private function pick(array $items): mixed
    {
        return $items[array_rand($items)];
    }

    /**
     * @param array<int|string, int> $weights value => weight
     */
    private function weighted(array $weights): int|string
    {
        $roll = mt_rand(1, array_sum($weights));
        foreach ($weights as $value => $weight) {
            if (($roll -= $weight) <= 0) {
                return $value;
            }
        }

        return array_key_first($weights);
    }

    private function weightedItem(array $items, callable $weight): mixed
    {
        $weights = array_map($weight, $items);
        $roll = mt_rand(1, max(1, array_sum($weights)));
        foreach ($items as $i => $item) {
            if (($roll -= $weights[$i]) <= 0) {
                return $item;
            }
        }

        return reset($items);
    }

    private function pauseActivityLog(): void
    {
        if (class_exists(\Spatie\Activitylog\ActivityLogStatus::class)) {
            app(\Spatie\Activitylog\ActivityLogStatus::class)->disable();
        }
    }

    /**
     * Branch stock rows whose quantity differs from the last stock card line.
     */
    private function stockMismatches(): int
    {
        return (int) DB::table('product_branches as pb')
            ->join('products as p', 'p.id', '=', 'pb.product_id')
            ->where('p.type', 'product')
            ->whereRaw('pb.qty <> COALESCE((select pl.amount_after from product_logs pl where pl.product_id = pb.product_id and pl.branch_id = pb.branch_id order by pl.created_at desc, pl.id desc limit 1), 0)')
            ->count();
    }
}
