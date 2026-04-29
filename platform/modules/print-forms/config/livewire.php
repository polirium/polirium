<?php

use Polirium\Modules\PrintForms\Http\Livewire\Editor\EditorComponent;
use Polirium\Modules\PrintForms\Http\Livewire\Editor\Modal\ModalCreateFormComponent;

return [
    'editor-content' => [
        'class' => EditorComponent::class,
        'alias' => 'modules/print-forms::editor.editor',
        'description' => 'Editor print',
    ],
    'modal-create-form' => [
        'class' => ModalCreateFormComponent::class,
        'alias' => 'modules/print-forms::editor.modal.modal-create-form',
        'description' => 'Modal create form',
    ],
];
