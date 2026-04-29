<?php

namespace Polirium\Modules\PrintForms\Http\Livewire\Editor;

use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Polirium\Core\UI\Facades\Assets;
use Polirium\Modules\PrintForms\Http\Model\Form;

class EditorComponent extends Component
{
    public string $render = '';

    public string $tab = 'invoice';

    public int $form_id = 0;

    public string $paperSize = 'a4';

    public string $search = '';

    public $forms = [];

    public function mount(): void
    {
        Assets::loadCss('print-forms-editor');
        Assets::loadJs('print-forms-editor');
        $this->loadForms();
        $this->selectActiveOrFirst();
    }

    #[Computed]
    public function form()
    {
        return Form::find($this->form_id);
    }

    public function updatedPaperSize(): void
    {
        if ($this->form_id) {
            $form = Form::find($this->form_id);
            if ($form) {
                $form->paper_size = $this->paperSize;
                $form->save();
                // Re-render content to inject new CSS
                $this->render = $this->injectPageSizeCss($form->content, $this->paperSize);
            }
        }
    }

    public function selectActiveOrFirst(): void
    {
        $form = Form::where('type', $this->tab)->where('active', 1)->first();
        if (! $form) {
            $form = Form::where('type', $this->tab)->first();
        }
        $this->form_id = $form?->id ?: 0;
        $this->paperSize = $form?->paper_size ?: 'a4';
        $this->render = $form ? $this->injectPageSizeCss($form->content, $this->paperSize) : '';
    }

    public function selectForm($id): void
    {
        $this->form_id = $id;
        $form = Form::find($id);
        $this->paperSize = $form?->paper_size ?: 'a4';
        $this->render = $form ? $this->injectPageSizeCss($form->content, $this->paperSize) : '';
    }

    protected function injectPageSizeCss(string $content, string $paperSize): string
    {
        $css = match($paperSize) {
            'a4' => '@page { size: A4; margin: 0; } body { margin: 0; width: 210mm; }',
            'a5' => '@page { size: A5; margin: 0; } body { margin: 0; width: 148mm; }',
            'k80' => '@page { size: 80mm auto; margin: 0; } body { margin: 0; width: 80mm; font-size: 12px; } .container, table { width: 100% !important; max-width: 100% !important; }',
            'k57' => '@page { size: 57mm auto; margin: 0; } body { margin: 0; width: 57mm; font-size: 10px; } .container, table { width: 100% !important; max-width: 100% !important; }',
            default => '@page { size: A4; margin: 0; } body { margin: 0; width: 210mm; }',
        };

        // Aggressive content fitting
        $css .= ' img { max-width: 100%; height: auto; }';
        $css .= ' * { box-sizing: border-box; word-wrap: break-word; }';

        // If content already has a <style> tag, append to it
        if (str_contains($content, '</style>')) {
            return str_replace('</style>', $css . '</style>', $content);
        }

        // Otherwise prepend a new style tag
        return "<style>{$css}</style>" . $content;
    }

    public function toggleActive($id): void
    {
        $this->authorize('print-forms.forms.edit');
        // Deactivate all forms of this type
        Form::where('type', $this->tab)->update(['active' => 0]);

        // Activate selected form
        $form = Form::find($id);
        if ($form) {
            $form->active = 1;
            $form->save();
        }

        $this->loadForms();
        $this->selectForm($id);
    }

    public function deleteForm($id): void
    {
        $this->authorize('print-forms.forms.edit');
        $form = Form::find($id);
        if ($form) {
            $form->delete();
        }

        $this->loadForms();
        $this->selectActiveOrFirst();
    }

    public function updatedTab(): void
    {
        $this->search = '';
        $this->loadForms();
        $this->selectActiveOrFirst();
    }

    public function updatedSearch(): void
    {
        $this->loadForms();
    }

    public function render()
    {
        return view('modules/print-forms::editor.editor');
    }

    #[On('setting-load-forms')]
    public function loadForms(): void
    {
        $query = Form::where('type', $this->tab)
            ->select(['id', 'name', 'active', 'updated_at']);

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        $this->forms = $query
            ->orderBy('active', 'desc')
            ->orderBy('updated_at', 'desc')
            ->get()
            ->toArray();
    }
}
