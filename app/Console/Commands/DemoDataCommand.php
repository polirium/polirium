<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Polirium\Core\Base\Http\Models\Branch\Branch;
use Polirium\Core\Base\Http\Models\User;
use Polirium\Modules\Accounting\Http\Model\AccountingType;
use Polirium\Modules\Accounting\Http\Model\Payment as AccountingPayment;
use Polirium\Modules\Accounting\Http\Model\PayPerson;
use Polirium\Modules\Accounting\Http\Model\Receipt;
use Polirium\Modules\Customer\Http\Model\Customer;
use Polirium\Modules\Customer\Http\Model\CustomerGroup;
use Polirium\Modules\PrintForms\Http\Model\Form;
use Polirium\Modules\Product\Http\Model\Category;
use Polirium\Modules\Product\Http\Model\Payment\Payment;
use Polirium\Modules\Product\Http\Model\Payment\PaymentDelivery;
use Polirium\Modules\Product\Http\Model\Payment\PaymentMethod;
use Polirium\Modules\Product\Http\Model\Payment\PaymentPartnerDelivery;
use Polirium\Modules\Product\Http\Model\Payment\PaymentProduct;
use Polirium\Modules\Product\Http\Model\Payment\SaleChannel;
use Polirium\Modules\Product\Http\Model\Product;
use Polirium\Modules\Product\Http\Model\ProductBranch;
use Polirium\Modules\Product\Http\Model\ProductUnit;
use Polirium\Modules\Product\Http\Model\Shelve;
use Polirium\Modules\Product\Http\Model\Trademark;
use Polirium\Modules\Task\Models\Project;
use Polirium\Modules\Task\Models\Task;
use Polirium\Modules\Vendor\Http\Model\Purchase\Purchase;
use Polirium\Modules\Vendor\Http\Model\Purchase\PurchaseProduct;
use Polirium\Modules\Vendor\Http\Model\Vendor;
use Polirium\Modules\Vendor\Http\Model\VendorGroup;

class DemoDataCommand extends Command
{
    protected $signature = 'demo:data
        {--fresh : Xoá dữ liệu cũ trước khi seed}
        {--module= : Chỉ seed module cụ thể (product, customer, vendor, sale, accounting, task, print-forms)}';

    protected $description = 'Tạo dữ liệu demo đầy đủ cho toàn bộ modules';

    private ?Branch $branch = null;

    private ?User $user = null;

    /** @var array<string, callable> */
    private array $modules = [];

    public function handle(): int
    {
        $this->modules = [
            'product' => fn () => $this->seedProduct(),
            'customer' => fn () => $this->seedCustomer(),
            'vendor' => fn () => $this->seedVendor(),
            'sale' => fn () => $this->seedSale(),
            'accounting' => fn () => $this->seedAccounting(),
            'task' => fn () => $this->seedTask(),
            'print-forms' => fn () => $this->seedPrintForms(),
        ];

        $this->branch = Branch::first();
        $this->user = User::first();

        if (! $this->branch) {
            $this->error('Không tìm thấy Branch nào. Hãy tạo Branch trước khi chạy demo:data.');

            return self::FAILURE;
        }

        if (! $this->user) {
            $this->error('Không tìm thấy User nào. Hãy tạo User trước khi chạy demo:data.');

            return self::FAILURE;
        }

        $targetModule = $this->option('module');

        if ($targetModule && ! isset($this->modules[$targetModule])) {
            $this->error("Module '{$targetModule}' không hợp lệ. Các module: " . implode(', ', array_keys($this->modules)));

            return self::FAILURE;
        }

        $modulesToSeed = $targetModule
            ? [$targetModule => $this->modules[$targetModule]]
            : $this->modules;

        if ($this->option('fresh')) {
            $this->warn('⚠️  Xoá dữ liệu cũ...');
            $this->truncateData($targetModule);
        }

        $this->info('🚀 Bắt đầu tạo dữ liệu demo...');
        $this->newLine();

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        foreach ($modulesToSeed as $name => $seeder) {
            $this->info("📦 Seeding module: {$name}");
            $seeder();
            $this->newLine();
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->info('✅ Hoàn tất tạo dữ liệu demo!');

        return self::SUCCESS;
    }

    /**
     * Xoá dữ liệu cũ.
     */
    private function truncateData(?string $module): void
    {
        $tables = [
            'product' => [
                'product_payment_products', 'product_payment_deliveries', 'product_payments',
                'product_elements', 'product_units', 'product_branches', 'product_logs',
                'product_stock_products', 'product_stocks',
                'product_refunds', 'product_refund_products',
                'products', 'categories', 'trademarks', 'shelves',
                'payment_methods', 'product_payment_sale_channels', 'product_payment_partner_deliveries',
            ],
            'customer' => ['customers_pivot_groups', 'customers', 'customer_groups'],
            'vendor' => [
                'vendor_purchase_products', 'vendor_purchases',
                'vendor_refund_products', 'vendor_refunds',
                'vendor_transfer_products', 'vendor_transfers',
                'vendors_groups_pivot', 'vendors', 'vendor_groups',
            ],
            'sale' => ['product_payment_products', 'product_payment_deliveries', 'product_payments'],
            'accounting' => ['accouting_payments', 'accounting_receipts', 'accounting_types', 'accounting_pay_persons'],
            'task' => ['task_comments', 'task_attachments', 'task_time_logs', 'task_dependencies', 'tasks', 'projects'],
            'print-forms' => ['forms'],
        ];

        $target = $module ? [$module => $tables[$module] ?? []] : $tables;

        foreach ($target as $mod => $tbls) {
            foreach ($tbls as $table) {
                try {
                    DB::table($table)->truncate();
                } catch (\Exception $e) {
                    // Table might not exist, skip
                }
            }
        }
    }

    // =========================================================================
    //  PRODUCT MODULE
    // =========================================================================

    private function seedProduct(): void
    {
        $this->seedCategories();
        $this->seedTrademarks();
        $this->seedShelves();
        $this->seedProducts();
        $this->seedPaymentMethods();
        $this->seedSaleChannels();
        $this->seedPartnerDeliveries();
    }

    private function seedCategories(): void
    {
        $tree = [
            'Thời trang' => ['Áo' => ['Áo thun', 'Áo sơ mi', 'Áo khoác'], 'Quần' => ['Quần jean', 'Quần kaki', 'Quần short']],
            'Phụ kiện' => ['Túi xách' => [], 'Giày dép' => [], 'Nón' => []],
            'Mỹ phẩm' => ['Chăm sóc da' => [], 'Trang điểm' => []],
        ];

        foreach ($tree as $parentName => $children) {
            $parent = Category::firstOrCreate(
                ['name' => $parentName],
                ['parent_id' => null, 'user_id' => $this->user->id]
            );

            foreach ($children as $childName => $grandChildren) {
                $child = Category::firstOrCreate(
                    ['name' => $childName],
                    ['parent_id' => $parent->id, 'user_id' => $this->user->id]
                );

                foreach ($grandChildren as $gcName) {
                    Category::firstOrCreate(
                        ['name' => $gcName],
                        ['parent_id' => $child->id, 'user_id' => $this->user->id]
                    );
                }
            }
        }

        $this->line('  ✓ Categories: ' . Category::count());
    }

    private function seedTrademarks(): void
    {
        $names = ['Nike', 'Adidas', 'Uniqlo', 'Zara', 'H&M'];
        foreach ($names as $name) {
            Trademark::firstOrCreate(['name' => $name], ['user_id' => $this->user->id]);
        }
        $this->line('  ✓ Trademarks: ' . count($names));
    }

    private function seedShelves(): void
    {
        $names = ['Kệ A1', 'Kệ A2', 'Kệ B1', 'Kệ B2', 'Kệ C1'];
        foreach ($names as $name) {
            Shelve::firstOrCreate(['name' => $name], ['user_id' => $this->user->id]);
        }
        $this->line('  ✓ Shelves: ' . count($names));
    }

    private function seedProducts(): void
    {
        $leafCategories = Category::whereDoesntHave('childs')->pluck('id')->toArray();
        $trademarkIds = Trademark::pluck('id')->toArray();
        $shelveIds = Shelve::pluck('id')->toArray();

        $productNames = [
            'Áo thun nam basic trắng', 'Áo thun nam basic đen', 'Áo thun nữ oversize',
            'Áo sơ mi nam dài tay', 'Áo sơ mi nữ công sở', 'Áo khoác bomber nam',
            'Áo khoác gió unisex', 'Áo hoodie nam', 'Áo polo nam', 'Áo croptop nữ',
            'Quần jean nam slim fit', 'Quần jean nữ ống rộng', 'Quần kaki nam',
            'Quần kaki nữ', 'Quần short nam thể thao', 'Quần short jean nữ',
            'Túi xách nữ da PU', 'Túi đeo chéo nam', 'Balo laptop 15.6 inch',
            'Giày thể thao nam', 'Giày thể thao nữ', 'Dép sandal nam',
            'Nón lưỡi trai', 'Nón bucket', 'Thắt lưng da nam',
            'Sữa rửa mặt CeraVe', 'Toner HA', 'Kem chống nắng SPF50',
            'Son môi Maybelline', 'Phấn phủ kiềm dầu',
            'Áo thun polo nữ', 'Quần jean baggy nam', 'Áo len cổ lọ',
            'Váy liền nữ', 'Chân váy chữ A', 'Áo khoác cardigan',
            'Bộ pyjama nam', 'Bộ đồ ngủ nữ', 'Áo tank top nam',
            'Quần legging nữ', 'Giày cao gót nữ', 'Giày lười nam',
            'Kính mát thời trang', 'Vòng tay phong thuỷ', 'Khăn quàng cổ',
            'Bao tay da nam', 'Tất cổ cao unisex', 'Mascara Loreal',
            'Kem dưỡng ẩm Nivea', 'Serum Vitamin C',
        ];

        $count = 0;
        foreach ($productNames as $i => $name) {
            $cost = rand(5, 50) * 10000; // 50k - 500k
            $price = $cost + rand(5, 30) * 10000; // markup 50k - 300k

            $product = Product::create([
                'name' => $name,
                'code' => 'SP' . str_pad($i + 1, 5, '0', STR_PAD_LEFT),
                'category_id' => $leafCategories ? $leafCategories[array_rand($leafCategories)] : null,
                'trademark_id' => $trademarkIds ? $trademarkIds[array_rand($trademarkIds)] : null,
                'shelve_id' => $shelveIds ? $shelveIds[array_rand($shelveIds)] : null,
                'cost' => $cost,
                'price' => $price,
                'qty' => rand(10, 200),
                'weight' => rand(100, 2000),
                'weight_type' => collect(['gram', 'kg'])->random(),
                'allows_sale' => 1,
                'unit' => collect(['Cái', 'Đôi', 'Chiếc', 'Bộ', 'Chai', 'Tuýp', 'Hộp'])->random(),
                'type' => collect(['product', 'product', 'product', 'service'])->random(),
                'user_id' => $this->user->id,
            ]);

            // Gắn product vào branch với tồn kho
            ProductBranch::create([
                'product_id' => $product->id,
                'branch_id' => $this->branch->id,
                'qty' => rand(10, 200),
            ]);

            // Tạo đơn vị tính phụ cho một số sản phẩm
            if ($i % 3 === 0) {
                ProductUnit::create([
                    'product_id' => $product->id,
                    'name' => 'Hộp ' . rand(6, 12) . ' cái',
                    'code' => 'HOP-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                    'conversion_value' => rand(6, 12),
                    'price' => $price * rand(6, 12) * 0.9,
                    'allows_sale' => 1,
                ]);
            }

            $count++;
        }

        $this->line("  ✓ Products: {$count} (with branches & units)");
    }

    private function seedPaymentMethods(): void
    {
        $methods = [
            ['name' => 'Tiền mặt', 'code' => 'cash', 'is_active' => true, 'is_default' => true, 'sort_order' => 1, 'target_payment_status' => 'completed'],
            ['name' => 'Chuyển khoản', 'code' => 'bank', 'is_active' => true, 'is_default' => false, 'sort_order' => 2, 'target_payment_status' => 'completed'],
            ['name' => 'Thẻ', 'code' => 'card', 'is_active' => true, 'is_default' => false, 'sort_order' => 3, 'target_payment_status' => 'completed'],
            ['name' => 'COD', 'code' => 'cod', 'is_active' => true, 'is_default' => false, 'sort_order' => 4, 'target_payment_status' => 'pending'],
            ['name' => 'Khác', 'code' => 'other', 'is_active' => true, 'is_default' => false, 'sort_order' => 5, 'target_payment_status' => 'pending'],
        ];

        foreach ($methods as $method) {
            PaymentMethod::updateOrCreate(['code' => $method['code']], $method);
        }
        $this->line('  ✓ Payment Methods: ' . count($methods));
    }

    private function seedSaleChannels(): void
    {
        $channels = [
            ['name' => 'Tại cửa hàng', 'sort_order' => 1, 'is_active' => true],
            ['name' => 'Website', 'sort_order' => 2, 'is_active' => true],
            ['name' => 'Shopee', 'sort_order' => 3, 'is_active' => true],
            ['name' => 'Lazada', 'sort_order' => 4, 'is_active' => true],
        ];

        foreach ($channels as $channel) {
            SaleChannel::create($channel);
        }
        $this->line('  ✓ Sale Channels: ' . count($channels));
    }

    private function seedPartnerDeliveries(): void
    {
        $partners = [
            ['type' => 'company', 'name' => 'Giao Hàng Nhanh', 'code' => 'GHN', 'phone' => '1900636677', 'sort_order' => 1, 'is_active' => true],
            ['type' => 'company', 'name' => 'Giao Hàng Tiết Kiệm', 'code' => 'GHTK', 'phone' => '1900545436', 'sort_order' => 2, 'is_active' => true],
            ['type' => 'person', 'name' => 'Anh Minh (Shipper)', 'code' => 'SHP01', 'phone' => '0901234567', 'sort_order' => 3, 'is_active' => true],
        ];

        foreach ($partners as $partner) {
            PaymentPartnerDelivery::create($partner);
        }
        $this->line('  ✓ Delivery Partners: ' . count($partners));
    }

    // =========================================================================
    //  CUSTOMER MODULE
    // =========================================================================

    private function seedCustomer(): void
    {
        $groups = ['Khách VIP', 'Khách sỉ', 'Khách lẻ', 'Đối tác', 'Nhân viên'];
        $groupIds = [];

        foreach ($groups as $index => $name) {
            $group = CustomerGroup::create([
                'name' => $name,
                'type' => ($index % 3) + 1,
                'user_id' => $this->user->id,
            ]);
            $groupIds[] = $group->id;
        }
        $this->line('  ✓ Customer Groups: ' . count($groups));

        $lastNames = ['Nguyễn', 'Trần', 'Lê', 'Phạm', 'Hoàng', 'Huỳnh', 'Phan', 'Vũ', 'Võ', 'Đặng'];
        $middleNames = ['Văn', 'Thị', 'Đức', 'Minh', 'Thanh', 'Hồng', 'Quốc', 'Ngọc'];
        $firstNames = ['An', 'Bình', 'Chi', 'Dung', 'Em', 'Giang', 'Hà', 'Khoa', 'Lan', 'Mai', 'Nga', 'Phúc', 'Quân', 'Sơn', 'Thảo', 'Uyên', 'Vinh', 'Xuân', 'Yến', 'Đạt'];

        $count = 0;
        for ($i = 0; $i < 30; $i++) {
            $name = $lastNames[array_rand($lastNames)] . ' ' .
                    $middleNames[array_rand($middleNames)] . ' ' .
                    $firstNames[array_rand($firstNames)];

            $phone = '09' . str_pad($i + 1, 8, '0', STR_PAD_LEFT);
            $email = 'demo.kh' . str_pad($i + 1, 3, '0', STR_PAD_LEFT) . '@gmail.com';

            $customer = Customer::create([
                'code' => 'KH' . str_pad($i + 1, 5, '0', STR_PAD_LEFT),
                'name' => $name,
                'phone' => $phone,
                'birthday' => now()->subYears(rand(18, 60))->subDays(rand(0, 365))->format('Y-m-d'),
                'sex' => rand(0, 1),
                'address' => 'Số ' . rand(1, 500) . ' đường ' . collect(['Nguyễn Huệ', 'Lê Lợi', 'Trần Phú', 'Hai Bà Trưng', 'Lý Thường Kiệt', 'Nguyễn Trãi', 'Điện Biên Phủ'])->random(),
                'type' => rand(0, 1),
                'email' => $email,
                'status' => 1,
                'user_id' => $this->user->id,
                'branch_id' => $this->branch->id,
            ]);

            // Gắn 1-2 nhóm khách hàng
            $customer->customerGroups()->attach(
                collect($groupIds)->random(rand(1, 2))->toArray()
            );

            $count++;
        }

        $this->line("  ✓ Customers: {$count}");
    }

    // =========================================================================
    //  VENDOR MODULE
    // =========================================================================

    private function seedVendor(): void
    {
        $groups = ['Nhà cung cấp trong nước', 'Nhà cung cấp Trung Quốc', 'Nhà cung cấp Hàn Quốc'];
        $vendorGroupIds = [];

        foreach ($groups as $name) {
            $group = VendorGroup::create([
                'name' => $name,
                'user_created_id' => $this->user->id,
            ]);
            $vendorGroupIds[] = $group->id;
        }
        $this->line('  ✓ Vendor Groups: ' . count($groups));

        $vendors = [
            ['name' => 'Công ty TNHH May mặc Việt Phú', 'company' => 'Việt Phú'],
            ['name' => 'Công ty CP Thời trang Toàn Cầu', 'company' => 'Toàn Cầu Fashion'],
            ['name' => 'NCC Giày dép Bình Dương', 'company' => 'BD Shoes'],
            ['name' => 'Công ty TNHH Phụ kiện Á Đông', 'company' => 'Á Đông Accessories'],
            ['name' => 'NCC Mỹ phẩm Hàn Quốc - K-Beauty', 'company' => 'K-Beauty VN'],
            ['name' => 'Xưởng sản xuất Đại Nam', 'company' => 'Đại Nam Textile'],
            ['name' => 'CTY TNHH TM DV Minh Anh', 'company' => 'Minh Anh Co.'],
            ['name' => 'Nhà phân phối Thịnh Vượng', 'company' => 'Thịnh Vượng Corp'],
            ['name' => 'CTY CP SX Bao bì Sài Gòn', 'company' => 'SG Packaging'],
            ['name' => 'NCC Nguyên liệu Phú Mỹ', 'company' => 'Phú Mỹ Materials'],
        ];

        $vendorIds = [];
        foreach ($vendors as $i => $v) {
            $vendor = Vendor::create([
                'branch_id' => $this->branch->id,
                'code' => 'NCC' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'name' => $v['name'],
                'company' => $v['company'],
                'vat' => '',
                'phone' => '028' . rand(10000000, 99999999),
                'email' => Str::slug($v['company'], '.') . '@company.vn',
                'address' => 'KCN ' . collect(['Tân Bình', 'Sóng Thần', 'Biên Hoà', 'Long An', 'Bình Dương'])->random(),
                'status' => 'active',
                'user_created_id' => $this->user->id,
                'total' => 0,
                'debt' => 0,
                'total_purchase' => 0,
            ]);
            $vendorIds[] = $vendor->id;
            $vendor->group()->attach($vendorGroupIds[array_rand($vendorGroupIds)]);
        }
        $this->line('  ✓ Vendors: ' . count($vendors));

        // Seed Purchases
        $this->seedPurchases($vendorIds);
    }

    private function seedPurchases(array $vendorIds): void
    {
        $products = Product::inRandomOrder()->limit(30)->get();
        if ($products->isEmpty()) {
            $this->warn('  ⚠ Không có sản phẩm, bỏ qua tạo đơn nhập hàng.');

            return;
        }

        $count = 0;
        for ($i = 0; $i < 15; $i++) {
            $purchaseProducts = $products->random(rand(2, 5));
            $total = 0;

            $purchase = Purchase::create([
                'code' => 'PN' . str_pad($i + 1, 5, '0', STR_PAD_LEFT),
                'branch_id' => $this->branch->id,
                'vendor_id' => $vendorIds[array_rand($vendorIds)],
                'discount_value' => 0,
                'discount_type' => 'number',
                'user_created_id' => $this->user->id,
                'status' => collect(['success', 'success', 'temp'])->random(),
                'total' => 0,
                'need_pay' => 0,
                'value' => 0,
            ]);

            foreach ($purchaseProducts as $p) {
                $amount = rand(10, 50);
                $price = $p->cost ?: rand(50000, 300000);
                $value = $amount * $price;
                $total += $value;

                PurchaseProduct::create([
                    'branch_id' => $this->branch->id,
                    'product_id' => $p->id,
                    'vendor_purchase_id' => $purchase->id,
                    'amount' => $amount,
                    'price' => $price,
                    'discount_value' => 0,
                    'discount_type' => 'number',
                    'value' => $value,
                ]);
            }

            $purchase->update([
                'total' => $total,
                'need_pay' => $total,
                'value' => $total,
            ]);

            $count++;
        }

        $this->line("  ✓ Purchases: {$count}");
    }

    // =========================================================================
    //  SALE MODULE (Product Payments / Đơn bán hàng)
    // =========================================================================

    private function seedSale(): void
    {
        $products = Product::inRandomOrder()->limit(40)->get();
        $customerIds = Customer::pluck('id')->toArray();
        $saleChannelIds = SaleChannel::withoutGlobalScopes()->pluck('id')->toArray();
        $partnerIds = PaymentPartnerDelivery::withoutGlobalScopes()->pluck('id')->toArray();

        if ($products->isEmpty()) {
            $this->warn('  ⚠ Không có sản phẩm, bỏ qua tạo đơn bán hàng.');

            return;
        }

        $count = 0;
        for ($i = 0; $i < 30; $i++) {
            $orderProducts = $products->random(rand(1, 4));
            $totalCost = 0;
            $totalAmount = 0;

            $isDelivery = rand(0, 1);
            $status = collect(['completed', 'completed', 'completed', 'pending', 'cancel'])->random();
            $typePayment = collect([['cash'], ['bank'], ['cash', 'bank'], ['card']])->random();

            $payment = Payment::create([
                'branch_id' => $this->branch->id,
                'customer_id' => $customerIds ? $customerIds[array_rand($customerIds)] : null,
                'code' => '', // Auto-generated by model boot
                'amount_products' => 0,
                'total_cost' => 0,
                'discount_value' => rand(0, 1) ? rand(5, 15) : 0,
                'discount_type' => rand(0, 1) ? 'percent' : 'number',
                'value' => 0,
                'value_payment' => 0,
                'type_payment' => $typePayment,
                'is_delivery' => $isDelivery,
                'sale_channel_id' => $saleChannelIds ? $saleChannelIds[array_rand($saleChannelIds)] : null,
                'is_cod' => $isDelivery ? rand(0, 1) : 0,
                'user_id' => $this->user->id,
                'author_id' => $this->user->id,
                'status' => $status,
                'completed_at' => $status === 'completed' ? now()->subDays(rand(0, 90)) : null,
                'note' => null,
            ]);

            foreach ($orderProducts as $p) {
                $amount = rand(1, 5);
                $value = $p->price ?: rand(100000, 500000);
                $total = $amount * $value;
                $totalCost += $total;
                $totalAmount += $amount;

                PaymentProduct::create([
                    'product_payment_id' => $payment->id,
                    'product_id' => $p->id,
                    'amount' => $amount,
                    'value' => $value,
                    'discount_value' => 0,
                    'discount_type' => 'number',
                    'total' => $total,
                ]);
            }

            // Tính value (số tiền khách cần trả)
            $discountAmount = 0;
            if ($payment->discount_value > 0) {
                if ($payment->discount_type === 'percent') {
                    $discountAmount = $totalCost * ($payment->discount_value / 100);
                } else {
                    $discountAmount = $payment->discount_value;
                }
            }
            $value = $totalCost - $discountAmount;

            $payment->updateQuietly([
                'amount_products' => $totalAmount,
                'total_cost' => $totalCost,
                'value' => $value,
                'value_payment' => $status === 'completed' ? $value : 0,
                'created_at' => now()->subDays(rand(0, 90)),
            ]);

            // Delivery info
            if ($isDelivery && $partnerIds) {
                PaymentDelivery::create([
                    'product_payment_id' => $payment->id,
                    'code' => 'DL' . str_pad($i + 1, 5, '0', STR_PAD_LEFT),
                    'partner_delivery_id' => $partnerIds[array_rand($partnerIds)],
                    'type' => collect(['normal', 'fast'])->random(),
                    'value' => rand(15000, 50000),
                    'date' => now()->subDays(rand(0, 30)),
                    'status' => $status === 'completed' ? 'success' : 'wait',
                ]);
            }

            $count++;
        }

        $this->line("  ✓ Sale Orders: {$count}");
    }

    // =========================================================================
    //  ACCOUNTING MODULE
    // =========================================================================

    private function seedAccounting(): void
    {
        // Accounting Types
        $types = [
            ['name' => 'Thu tiền bán hàng', 'type' => 'receipt'],
            ['name' => 'Thu tiền dịch vụ', 'type' => 'receipt'],
            ['name' => 'Thu khác', 'type' => 'receipt'],
            ['name' => 'Chi tiền hàng', 'type' => 'payment'],
            ['name' => 'Chi vận chuyển', 'type' => 'payment'],
            ['name' => 'Chi lương nhân viên', 'type' => 'payment'],
            ['name' => 'Chi tiền mặt bằng', 'type' => 'payment'],
        ];

        $typeIds = ['receipt' => [], 'payment' => []];
        foreach ($types as $t) {
            $at = AccountingType::create($t);
            $typeIds[$t['type']][] = $at->id;
        }
        $this->line('  ✓ Accounting Types: ' . count($types));

        // Pay Persons
        $persons = [
            ['name' => 'Nguyễn Văn Tài', 'phone' => '0901111111', 'address' => '12 Nguyễn Huệ, Q1, HCM'],
            ['name' => 'Trần Thị Phương', 'phone' => '0902222222', 'address' => '45 Lê Lợi, Q1, HCM'],
            ['name' => 'Lê Minh Đức', 'phone' => '0903333333', 'address' => '78 Trần Phú, Q5, HCM'],
            ['name' => 'Phạm Hồng Nhung', 'phone' => '0904444444', 'address' => '23 Hai Bà Trưng, Q3, HCM'],
            ['name' => 'Hoàng Quốc Bảo', 'phone' => '0905555555', 'address' => '56 Điện Biên Phủ, Bình Thạnh, HCM'],
        ];

        foreach ($persons as $p) {
            PayPerson::create($p);
        }
        $this->line('  ✓ Pay Persons: ' . count($persons));

        // Receipts (Phiếu thu)
        for ($i = 0; $i < 10; $i++) {
            Receipt::create([
                'branch_id' => $this->branch->id,
                'code' => 'PT' . str_pad($i + 1, 5, '0', STR_PAD_LEFT),
                'date' => now()->subDays(rand(0, 90))->format('Y-m-d'),
                'type_id' => $typeIds['receipt'] ? $typeIds['receipt'][array_rand($typeIds['receipt'])] : null,
                'value' => rand(1, 50) * 100000,
                'user_id' => $this->user->id,
                'user_created_id' => $this->user->id,
                'business_result' => rand(0, 1),
                'note' => 'Phiếu thu demo #' . ($i + 1),
            ]);
        }
        $this->line('  ✓ Receipts: 10');

        // Payments (Phiếu chi)
        for ($i = 0; $i < 10; $i++) {
            AccountingPayment::create([
                'branch_id' => $this->branch->id,
                'code' => 'PC' . str_pad($i + 1, 5, '0', STR_PAD_LEFT),
                'date' => now()->subDays(rand(0, 90))->format('Y-m-d'),
                'type_id' => $typeIds['payment'] ? $typeIds['payment'][array_rand($typeIds['payment'])] : null,
                'value' => rand(1, 30) * 100000,
                'user_id' => $this->user->id,
                'user_created_id' => $this->user->id,
                'business_result' => rand(0, 1),
                'note' => 'Phiếu chi demo #' . ($i + 1),
            ]);
        }
        $this->line('  ✓ Accounting Payments: 10');
    }

    // =========================================================================
    //  TASK MODULE
    // =========================================================================

    private function seedTask(): void
    {
        $projects = [
            ['name' => 'Phát triển Website bán hàng', 'status' => 'active', 'priority' => 'high', 'budget' => 50000000],
            ['name' => 'Chiến dịch Marketing Q2/2026', 'status' => 'planning', 'priority' => 'medium', 'budget' => 20000000],
            ['name' => 'Nâng cấp hệ thống kho', 'status' => 'active', 'priority' => 'urgent', 'budget' => 30000000],
        ];

        $projectIds = [];
        foreach ($projects as $p) {
            $project = Project::create([
                'name' => $p['name'],
                'description' => 'Dự án demo: ' . $p['name'],
                'status' => $p['status'],
                'priority' => $p['priority'],
                'planned_start_date' => now()->subDays(rand(10, 30)),
                'planned_end_date' => now()->addDays(rand(30, 90)),
                'budget' => $p['budget'],
                'progress_percentage' => rand(10, 80),
                'branch_id' => $this->branch->id,
                'created_by' => $this->user->id,
            ]);
            $projectIds[] = $project->id;
        }
        $this->line('  ✓ Projects: ' . count($projects));

        // Tasks
        $taskTemplates = [
            ['name' => 'Thiết kế giao diện trang chủ', 'status' => 'done', 'priority' => 'high'],
            ['name' => 'Code backend API sản phẩm', 'status' => 'in_progress', 'priority' => 'high'],
            ['name' => 'Tích hợp thanh toán online', 'status' => 'todo', 'priority' => 'urgent'],
            ['name' => 'SEO tối ưu hoá website', 'status' => 'backlog', 'priority' => 'medium'],
            ['name' => 'Viết nội dung landing page', 'status' => 'in_progress', 'priority' => 'medium'],
            ['name' => 'Setup email marketing', 'status' => 'todo', 'priority' => 'low'],
            ['name' => 'Chạy quảng cáo Facebook', 'status' => 'review', 'priority' => 'high'],
            ['name' => 'Kiểm kê tồn kho tháng 3', 'status' => 'done', 'priority' => 'urgent'],
            ['name' => 'Đào tạo nhân viên bán hàng', 'status' => 'in_progress', 'priority' => 'medium'],
            ['name' => 'Thiết kế banner khuyến mãi', 'status' => 'todo', 'priority' => 'low'],
            ['name' => 'Tối ưu tốc độ website', 'status' => 'backlog', 'priority' => 'medium'],
            ['name' => 'Test chức năng đặt hàng', 'status' => 'review', 'priority' => 'high'],
            ['name' => 'Cập nhật bảng giá mới', 'status' => 'done', 'priority' => 'medium'],
            ['name' => 'Họp review tiến độ sprint', 'status' => 'in_progress', 'priority' => 'low'],
            ['name' => 'Deploy version 2.0', 'status' => 'todo', 'priority' => 'urgent'],
        ];

        $taskCount = 0;
        foreach ($taskTemplates as $i => $t) {
            $projectId = $projectIds[$i % count($projectIds)];

            $task = Task::create([
                'project_id' => $projectId,
                'name' => $t['name'],
                'description' => 'Mô tả chi tiết cho task: ' . $t['name'],
                'status' => $t['status'],
                'priority' => $t['priority'],
                'assigned_to' => $this->user->id,
                'planned_start_date' => now()->subDays(rand(5, 20)),
                'planned_end_date' => now()->addDays(rand(5, 30)),
                'estimated_hours' => rand(4, 40),
                'actual_hours' => $t['status'] === 'done' ? rand(4, 40) : 0,
                'progress_percentage' => match ($t['status']) {
                    'done' => 100,
                    'review' => rand(80, 95),
                    'in_progress' => rand(20, 70),
                    default => 0,
                },
                'sort_order' => $i + 1,
                'branch_id' => $this->branch->id,
                'created_by' => $this->user->id,
            ]);

            // Comments cho một số tasks
            if ($i % 2 === 0) {
                DB::table('task_comments')->insert([
                    'task_id' => $task->id,
                    'user_id' => $this->user->id,
                    'content' => collect([
                        'Đã hoàn thành phần này, chuyển sang review.',
                        'Cần thêm thời gian để test kỹ hơn.',
                        'Đã cập nhật theo yêu cầu mới.',
                        'OK, đang xử lý tiếp.',
                        'Cần hỗ trợ từ team design.',
                    ])->random(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $taskCount++;
        }

        $this->line("  ✓ Tasks: {$taskCount} (with comments)");
    }

    // =========================================================================
    //  PRINT FORMS MODULE
    // =========================================================================

    private function seedPrintForms(): void
    {
        $forms = [
            ['name' => 'Hoá đơn bán hàng', 'type' => 'invoice', 'paper_size' => 'a4'],
            ['name' => 'Phiếu thu', 'type' => 'receipt', 'paper_size' => 'a5'],
            ['name' => 'Phiếu chi', 'type' => 'payment', 'paper_size' => 'a5'],
            ['name' => 'Phiếu nhập kho', 'type' => 'purchase', 'paper_size' => 'a4'],
            ['name' => 'Phiếu kiểm kho', 'type' => 'stock', 'paper_size' => 'a4'],
        ];

        foreach ($forms as $f) {
            Form::create([
                'name' => $f['name'],
                'type' => $f['type'],
                'paper_size' => $f['paper_size'],
                'content' => '<h1>' . $f['name'] . '</h1><p>Mẫu in demo</p>',
                'user_id' => $this->user->id,
                'active' => 1,
            ]);
        }

        $this->line('  ✓ Print Forms: ' . count($forms));
    }
}
