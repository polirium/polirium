<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

return new class () extends Migration {
    /**
     * Run the migrations.
     * Migrate payment/sale permissions from products.* to sales.*
     */
    public function up(): void
    {
        $guardName = 'web';

        // Mapping old permissions to new permissions
        $permissionMap = [
            'products.payment.index' => 'sales.payment.index',
            'products.print' => 'sales.print',
            // products.payment.refund and products.payment.cancel don't exist in DB, just create new ones
        ];

        // Get role_has_permissions and model_has_permissions for old permissions
        $oldPermissionNames = array_keys($permissionMap);

        // Get IDs of old permissions
        $oldPermissions = Permission::whereIn('name', $oldPermissionNames)
            ->where('guard_name', $guardName)
            ->get()
            ->keyBy('name');

        // Store permission relationships before deleting
        $rolePermissions = DB::table('role_has_permissions')
            ->whereIn('permission_id', $oldPermissions->pluck('id'))
            ->get()
            ->toArray();

        $userPermissions = DB::table('model_has_permissions')
            ->whereIn('permission_id', $oldPermissions->pluck('id'))
            ->get()
            ->toArray();

        DB::transaction(function () use ($permissionMap, $oldPermissions, $guardName, $rolePermissions, $userPermissions) {
            // Delete old permissions (cascade will delete role_has_permissions and model_has_permissions)
            Permission::whereIn('name', array_keys($permissionMap))
                ->where('guard_name', $guardName)
                ->delete();

            // Create new permissions
            $newPermissionIds = [];
            foreach ($permissionMap as $oldName => $newName) {
                $newPermission = Permission::create([
                    'name' => $newName,
                    'guard_name' => $guardName,
                ]);
                $newPermissionIds[$oldName] = $newPermission->id;
            }

            // Create additional new permissions that didn't exist before
            $additionalPermissions = [
                'sales.payment.refund',
                'sales.payment.cancel',
            ];

            foreach ($additionalPermissions as $name) {
                Permission::firstOrCreate(
                    ['name' => $name, 'guard_name' => $guardName],
                    ['name' => $name, 'guard_name' => $guardName]
                );
            }

            // Restore role_has_permissions with new permission IDs
            foreach ($rolePermissions as $rp) {
                $oldPermissionId = $rp->permission_id;

                // Find which old permission this ID belongs to
                $oldName = null;
                foreach ($oldPermissions as $perm) {
                    if ($perm->id == $oldPermissionId) {
                        $oldName = $perm->name;

                        break;
                    }
                }

                // Map to new permission ID
                if ($oldName && isset($newPermissionIds[$oldName])) {
                    $newPermissionId = $newPermissionIds[$oldName];

                    // Check if already exists (avoid duplicate)
                    $exists = DB::table('role_has_permissions')
                        ->where('role_id', $rp->role_id)
                        ->where('permission_id', $newPermissionId)
                        ->exists();

                    if (! $exists) {
                        DB::table('role_has_permissions')->insert([
                            'role_id' => $rp->role_id,
                            'permission_id' => $newPermissionId,
                        ]);
                    }
                }
            }

            // Restore model_has_permissions with new permission IDs
            foreach ($userPermissions as $up) {
                $oldPermissionId = $up->permission_id;

                // Find which old permission this ID belongs to
                $oldName = null;
                foreach ($oldPermissions as $perm) {
                    if ($perm->id == $oldPermissionId) {
                        $oldName = $perm->name;

                        break;
                    }
                }

                // Map to new permission ID
                if ($oldName && isset($newPermissionIds[$oldName])) {
                    $newPermissionId = $newPermissionIds[$oldName];

                    // Check if already exists (avoid duplicate)
                    $exists = DB::table('model_has_permissions')
                        ->where('model_type', $up->model_type)
                        ->where('model_id', $up->model_id)
                        ->where('permission_id', $newPermissionId)
                        ->exists();

                    if (! $exists) {
                        DB::table('model_has_permissions')->insert([
                            'permission_id' => $newPermissionId,
                            'model_type' => $up->model_type,
                            'model_id' => $up->model_id,
                        ]);
                    }
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     * Rollback: delete sales.payment.* permissions and restore products.payment.* permissions
     */
    public function down(): void
    {
        $guardName = 'web';

        // Reverse mapping
        $permissionMap = [
            'sales.payment.index' => 'products.payment.index',
            'sales.print' => 'products.print',
        ];

        DB::transaction(function () use ($permissionMap, $guardName) {
            // Get sales permissions to be migrated back
            $oldPermissions = Permission::whereIn('name', array_keys($permissionMap))
                ->where('guard_name', $guardName)
                ->get()
                ->keyBy('name');

            // Store relationships before deleting
            $rolePermissions = DB::table('role_has_permissions')
                ->whereIn('permission_id', $oldPermissions->pluck('id'))
                ->get()
                ->toArray();

            $userPermissions = DB::table('model_has_permissions')
                ->whereIn('permission_id', $oldPermissions->pluck('id'))
                ->get()
                ->toArray();

            // Delete sales.payment.* permissions (including refund, cancel)
            $salesPermissions = [
                'sales.payment.index',
                'sales.print',
                'sales.payment.refund',
                'sales.payment.cancel',
            ];
            Permission::whereIn('name', $salesPermissions)
                ->where('guard_name', $guardName)
                ->delete();

            // Recreate products.* permissions
            $newPermissionIds = [];
            foreach ($permissionMap as $salesName => $productsName) {
                $newPermission = Permission::create([
                    'name' => $productsName,
                    'guard_name' => $guardName,
                ]);
                $newPermissionIds[$salesName] = $newPermission->id;
            }

            // Restore role_has_permissions
            foreach ($rolePermissions as $rp) {
                $oldPermissionId = $rp->permission_id;

                // Find which sales permission this ID belongs to
                $salesName = null;
                foreach ($oldPermissions as $perm) {
                    if ($perm->id == $oldPermissionId) {
                        $salesName = $perm->name;

                        break;
                    }
                }

                // Map to products permission ID
                if ($salesName && isset($newPermissionIds[$salesName])) {
                    $newPermissionId = $newPermissionIds[$salesName];

                    $exists = DB::table('role_has_permissions')
                        ->where('role_id', $rp->role_id)
                        ->where('permission_id', $newPermissionId)
                        ->exists();

                    if (! $exists) {
                        DB::table('role_has_permissions')->insert([
                            'role_id' => $rp->role_id,
                            'permission_id' => $newPermissionId,
                        ]);
                    }
                }
            }

            // Restore model_has_permissions
            foreach ($userPermissions as $up) {
                $oldPermissionId = $up->permission_id;

                // Find which sales permission this ID belongs to
                $salesName = null;
                foreach ($oldPermissions as $perm) {
                    if ($perm->id == $oldPermissionId) {
                        $salesName = $perm->name;

                        break;
                    }
                }

                // Map to products permission ID
                if ($salesName && isset($newPermissionIds[$salesName])) {
                    $newPermissionId = $newPermissionIds[$salesName];

                    $exists = DB::table('model_has_permissions')
                        ->where('model_type', $up->model_type)
                        ->where('model_id', $up->model_id)
                        ->where('permission_id', $newPermissionId)
                        ->exists();

                    if (! $exists) {
                        DB::table('model_has_permissions')->insert([
                            'permission_id' => $newPermissionId,
                            'model_type' => $up->model_type,
                            'model_id' => $up->model_id,
                        ]);
                    }
                }
            }
        });
    }
};
