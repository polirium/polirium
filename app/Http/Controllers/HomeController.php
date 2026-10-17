<?php

namespace App\Http\Controllers;

use App\Support\SystemSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Some shops publish a website on the domain root, others only use the
     * admin; the choice lives in Settings → System.
     */
    public function __invoke(): View|RedirectResponse
    {
        if (SystemSettings::homepageMode() === SystemSettings::HOMEPAGE_ADMIN) {
            return redirect()->to(url(admin_prefix()));
        }

        return view('welcome');
    }
}
