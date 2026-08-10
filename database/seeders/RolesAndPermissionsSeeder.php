<?php

namespace Database\Seeders;

use App\Support\Permissions;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Idempotent: safe to re-run after adding a permission to the registry
     * without wiping the database.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (Permissions::all() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        /*
        | The registrar caches the permission list on first lookup, which
        | happens inside findOrCreate above. Without flushing here, syncing a
        | role throws PermissionDoesNotExist for anything created in this run.
        */
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (Permissions::forRoles() as $roleName => $permissions) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
