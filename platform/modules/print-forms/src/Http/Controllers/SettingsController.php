<?php

namespace Polirium\Modules\PrintForms\Http\Controllers;

use Polirium\Core\Base\Http\Controllers\BaseController;

class SettingsController extends BaseController
{
    public function editor()
    {
        $this->authorize('print-forms.forms.index');

        return view('modules/print-forms::editor.index');
    }
}
