<?php

declare(strict_types=1);

namespace App\Installer\Steps;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Polirium\Core\Base\Http\Models\User;
use RelayerCore\LaravelInstaller\Contracts\InstallerStep;
use RuntimeException;

class CreateAdministrator implements InstallerStep
{
    public function id(): string
    {
        return 'admin';
    }

    public function label(): string
    {
        return __('installer::installer.step_admin');
    }

    public function view(): string
    {
        return 'installer::steps.admin';
    }

    public function isSkipped(): bool
    {
        return session()->get(ChooseDatabaseAction::SESSION_MODE) === ChooseDatabaseAction::MODE_UPGRADE;
    }

    public function validate(array $data = []): bool
    {
        $validator = Validator::make($data, [
            'first_name' => ['required', 'string', 'min:2', 'max:60'],
            'last_name' => ['required', 'string', 'min:2', 'max:60'],
            'username' => ['required', 'string', 'min:4', 'max:60', Rule::unique('users', 'username')],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'max:72', 'confirmed'],
        ]);

        if ($validator->fails()) {
            throw new RuntimeException($validator->errors()->first());
        }

        return true;
    }

    public function process(array $data = []): void
    {
        DB::transaction(function () use ($data): void {
            $user = new User([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => $data['password'],
                'status' => 'active',
                'super_admin' => true,
            ]);
            $user->email_verified_at = now();
            $user->save();
        });
    }
}
