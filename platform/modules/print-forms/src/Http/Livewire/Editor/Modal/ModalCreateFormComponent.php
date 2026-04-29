<?php

namespace Polirium\Modules\PrintForms\Http\Livewire\Editor\Modal;

use Livewire\Attributes\On;
use Livewire\Component;
use Polirium\Modules\PrintForms\Http\Model\Form;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

class ModalCreateFormComponent extends Component
{
    public ?int $editor_id = null;
    public ?string $name = '';
    public ?string $content = '';
    public ?int $user_id = null;
    public ?string $type = 'invoice';
    public ?string $paperSize = 'a4';

    protected function rules()
    {
        return [
            'name' => 'required|string|max:191',
            'content' => 'required|string',
            'user_id' => 'required|numeric|integer',
            'type' => 'required|string',
            'paperSize' => 'required|string|in:a4,a5,k80,k57',
        ];
    }

    public function mount()
    {
        $this->resetInput();
        $this->user_id = auth()->id();
    }

    public function render()
    {
        return view('modules/print-forms::editor.modal.modal-create-form');
    }

    public function resetInput()
    {
        $this->reset(['name', 'content', 'type', 'paperSize']);
        $this->name = '';
        $this->content = '';
        $this->type = '';
        $this->paperSize = 'a4';
        $this->user_id = auth()->id();
    }

    #[On('show-modal-create-form')]
    public function showModal(string $type = null, ?int $id = null)
    {
        $this->authorize($id ? 'print-forms.forms.edit' : 'print-forms.forms.create');
        $this->editor_id = $id;
        $this->resetInput();

        if ($id) {
            $form = Form::findOrFail($id);
            $this->name = $form->name;
            $this->content = $form->content;
            $this->type = $form->type;
            $this->paperSize = $form->paper_size ?? 'a4';
            $this->user_id = $form->user_id;
        } else {
            $this->type = $type ?? 'invoice';
            $this->paperSize = 'a4'; // Default
        }

        if (empty($this->content)) {
            $this->loadDefaultContent();
        }

        $this->dispatch('editor_input_content', content: $this->content ?: '');

        $this->dispatch('modal', 'modal-create-form');
    }

    public function updatedPaperSize()
    {
        // Only auto-load content if creating new form or explicitly requested (future)
        // For now, if content is empty or looks like default template, we might switch
        // But to be safe, we only do this when creating a new form and content is untouched
        if (! $this->editor_id) {
            $this->loadDefaultContent();
            $this->dispatch('editor_input_content', content: $this->content ?: '');
        }
    }

    protected function loadDefaultContent()
    {
        // Choose template based on paper size
        $template = 'invoice';
        if (in_array($this->paperSize, ['k80', 'k57'])) {
            $template = 'k80';
        }
        $this->content = view("modules/print-forms::editor.import.invoice.{$template}")->render();
    }

    public function save()
    {
        $this->authorize($this->editor_id ? 'print-forms.forms.edit' : 'print-forms.forms.create');
        $this->validate();

        if ($this->editor_id) {
            $form = Form::findOrFail($this->editor_id);
            $form->update([
                'name' => $this->name,
                'content' => $this->content,
                'type' => $this->type,
                'paper_size' => $this->paperSize,
                'user_id' => $this->user_id,
            ]);
        } else {
            Form::create([
                'name' => $this->name,
                'content' => $this->content,
                'type' => $this->type,
                'paper_size' => $this->paperSize,
                'user_id' => $this->user_id,
            ]);
        }

        $this->resetInput();
        $this->dispatch('modal', 'modal-create-form', 'hide');
        $this->dispatch('setting-load-forms');
    }
    public bool $showPreview = false;
    public string $previewHTML = '';

    public function togglePreview()
    {
        $this->showPreview = ! $this->showPreview;

        if ($this->showPreview) {
            $this->generatePreview();
        }
    }

    protected function generatePreview()
    {
        try {
            // 1. Decode entities because WYSIWYG might store {{ as &#123;&#123;
            // We only want to decode the template tags, but decoding everything is usually safe for preview
            $templateString = html_entity_decode($this->content);

            // 2. Setup Twig
            // We use ArrayLoader to load the template from string
            $loader = new ArrayLoader([
                'index' => $templateString,
            ]);

            $twig = new Environment($loader, [
                'cache' => false,
                'autoescape' => false, // We trust the content for now, or let user control escaping
            ]);

            $twig->addFunction(new \Twig\TwigFunction('__', function ($string) {
                return trans($string);
            }));

            // 3. Render
            $content = $twig->render('index', $this->sampleData);
            $this->previewHTML = '<!DOCTYPE html><html><head><meta charset="utf-8"><style>body { margin: 0; padding: 10px; font-family: Arial, sans-serif; overflow-y: auto; }</style></head><body>' . $content . '</body></html>';

        } catch (\Exception $e) {
            $this->previewHTML = '<div class="alert alert-danger">Preview Error: ' . $e->getMessage() . '</div>';
        }
    }

    public function getSampleDataProperty()
    {
        return [
            'store_logo' => '<div style="background:#eee; width:100px; height:50px; display:flex; align-items:center; justify-content:center; margin:0 auto;">LOGO</div>',
            'store_name' => 'Cửa Hàng Demo Poilrium',
            'store_address' => '123 Đường ABC, Quận 1, TP.HCM',
            'store_phone' => '0909.123.456',

            'code' => 'HD00123',
            'day' => '17',
            'month' => '01',
            'year' => '2026',
            'full_date' => '17/01/2026',
            'note' => 'Giao hàng trong giờ hành chính',

            'customer_name' => 'Nguyễn Văn A',
            'customer_phone' => '0987.654.321',
            'customer_address' => '456 Đường XYZ, Quận 3',
            'customer_ward' => 'Phường Đa Kao',
            'customer_district' => 'Quận 3, TP.HCM',

            // Payment/Receipt specific
            'partner_name' => 'Nguyễn Văn B',
            'partner_phone' => '0912.345.678',
            'partner_address' => '789 Đường DEF, Quận 5',
            'reason' => 'Thanh toán tiền hàng đợt 1',
            'value' => '10.000.000',
            'value_in_words' => 'Mười triệu đồng chẵn',

            'items_table' => '<table style="width:100%; border-collapse: collapse; margin: 10px 0;">
                <tr style="border-bottom: 1px dotted #ccc;">
                    <th style="text-align:left;">Tên hàng</th>
                    <th style="text-align:center;">SL</th>
                    <th style="text-align:right;">Đ.Giá</th>
                    <th style="text-align:right;">T.Tiền</th>
                </tr>
                <tr>
                    <td>Cafe Đen Đá</td>
                    <td style="text-align:center;">2</td>
                    <td style="text-align:right;">25.000</td>
                    <td style="text-align:right;">50.000</td>
                </tr>
                <tr>
                    <td>Bạc Xỉu</td>
                    <td style="text-align:center;">1</td>
                    <td style="text-align:right;">35.000</td>
                    <td style="text-align:right;">35.000</td>
                </tr>
            </table>',
            'items' => [
                ['name' => 'Cafe Đen Đá', 'quantity' => 2, 'price' => '25.000', 'total' => '50.000'],
                ['name' => 'Bạc Xỉu', 'quantity' => 1, 'price' => '35.000', 'total' => '35.000'],
            ],
            'total_amount' => '85.000',
            'discount_amount' => '5.000',
            'discount_percent' => '5%',
            'grand_total' => '80.000',
            'customer_paid' => '100.000',
            'change_amount' => '20.000',
        ];
    }

    public function getVariablesProperty()
    {
        return [
            ['key' => '{{ store_logo|raw }}', 'desc' => trans('modules/print-forms::editor.var_store_logo'), 'group' => 'store_info'],
            ['key' => '{{ store_name }}', 'desc' => trans('modules/print-forms::editor.var_store_name'), 'group' => 'store_info'],
            ['key' => '{{ store_address }}', 'desc' => trans('modules/print-forms::editor.var_store_address'), 'group' => 'store_info'],
            ['key' => '{{ store_phone }}', 'desc' => trans('modules/print-forms::editor.var_store_phone'), 'group' => 'store_info'],

            ['key' => '{{ code }}', 'desc' => trans('modules/print-forms::editor.var_order_code'), 'group' => 'order_info'],
            ['key' => '{{ day }}', 'desc' => trans('modules/print-forms::editor.var_created_at'), 'group' => 'order_info'],
            ['key' => '{{ month }}', 'desc' => trans('modules/print-forms::editor.var_created_month'), 'group' => 'order_info'],
            ['key' => '{{ year }}', 'desc' => trans('modules/print-forms::editor.var_created_year'), 'group' => 'order_info'],
            ['key' => '{{ note }}', 'desc' => trans('modules/print-forms::editor.var_note'), 'group' => 'order_info'],

            ['key' => '{{ customer_name }}', 'desc' => trans('modules/print-forms::editor.var_customer_name'), 'group' => 'customer_info'],
            ['key' => '{{ customer_phone }}', 'desc' => trans('modules/print-forms::editor.var_customer_phone'), 'group' => 'customer_info'],
            ['key' => '{{ customer_address }}', 'desc' => trans('modules/print-forms::editor.var_customer_address'), 'group' => 'customer_info'],
            ['key' => '{{ customer_district }}', 'desc' => trans('modules/print-forms::editor.var_customer_district'), 'group' => 'customer_info'],

            ['key' => '{{ items_table|raw }}', 'desc' => trans('modules/print-forms::editor.var_items_table'), 'group' => 'items_info'],
            ['key' => '{{ total_amount }}', 'desc' => trans('modules/print-forms::editor.var_total_amount'), 'group' => 'payment_info'],
            ['key' => '{{ discount_amount }}', 'desc' => trans('modules/print-forms::editor.var_discount_amount'), 'group' => 'payment_info'],
            ['key' => '{{ discount_percent }}', 'desc' => trans('modules/print-forms::editor.var_discount_percent'), 'group' => 'payment_info'],
            ['key' => '{{ grand_total }}', 'desc' => trans('modules/print-forms::editor.var_grand_total'), 'group' => 'payment_info'],
            ['key' => '{{ customer_paid }}', 'desc' => trans('modules/print-forms::editor.var_customer_paid'), 'group' => 'payment_info'],
            ['key' => '{{ change_amount }}', 'desc' => trans('modules/print-forms::editor.var_change_amount'), 'group' => 'payment_info'],
        ];
    }
}
