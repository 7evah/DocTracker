<?php

namespace App\Livewire\Admin;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\Permissions;
use Flux\Flux;
use Illuminate\View\View;
use Livewire\Component;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * The role/permission matrix (§29).
 *
 * Roles themselves are fixed — they are modelled as a UserRole enum the code
 * branches on, so letting an administrator invent a seventh role would
 * produce something no policy understands. What is editable is which
 * permissions each role carries.
 */
class Roles extends Component
{
    /** @var array<string, list<string>> role name => permission names */
    public array $matrix = [];

    public function mount(): void
    {
        $this->authorize('manageRoles', User::class);

        $this->loadMatrix();
    }

    private function loadMatrix(): void
    {
        $this->matrix = Role::with('permissions')
            ->get()
            ->mapWithKeys(fn (Role $role) => [
                $role->name => $role->permissions->pluck('name')->all(),
            ])
            ->all();
    }

    public function save(): void
    {
        $this->authorize('manageRoles', User::class);

        $known = Permissions::all();

        foreach ($this->matrix as $roleName => $permissions) {
            // The Administrator role is the Gate::before bypass; restricting
            // it here would be a lie, since the hook grants everything anyway.
            if ($roleName === UserRole::Administrator->value) {
                continue;
            }

            $role = Role::where('name', $roleName)->first();

            if (! $role) {
                continue;
            }

            // Intersect with the registry so a crafted payload cannot grant
            // a permission the application does not define (§39).
            $role->syncPermissions(array_values(array_intersect($permissions ?? [], $known)));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->loadMatrix();

        Flux::toast(text: __('admin.roles.messages.updated'), variant: 'success');
    }

    public function render(): View
    {
        return view('livewire.admin.roles', [
            'groups' => Permissions::grouped(),
            'roles' => Role::withCount('users')->get(),
        ])->title(__('admin.roles.title'));
    }
}
