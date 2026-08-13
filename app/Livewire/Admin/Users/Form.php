<?php

namespace App\Livewire\Admin\Users;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    public ?User $user = null;

    public string $name = '';

    public string $email = '';

    public string $department = '';

    public string $job_title = '';

    public string $phone = '';

    public string $password = '';

    public string $status = UserStatus::Active->value;

    /** @var array<int, string> */
    public array $roles = [];

    #[On('new-user')]
    public function startNew(): void
    {
        $this->authorize('create', User::class);

        $this->reset('user', 'name', 'email', 'department', 'job_title', 'phone', 'password', 'roles');
        $this->status = UserStatus::Active->value;
        $this->resetValidation();

        $this->modal('user-form')->show();
    }

    #[On('edit-user')]
    public function edit(int $userId): void
    {
        $user = User::with('roles')->findOrFail($userId);

        $this->authorize('update', $user);

        $this->user = $user;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->department = $user->department ?? '';
        $this->job_title = $user->job_title ?? '';
        $this->phone = $user->phone ?? '';
        $this->password = '';
        $this->status = $user->status->value;
        $this->roles = $user->roles->pluck('name')->all();
        $this->resetValidation();

        $this->modal('user-form')->show();
    }

    public function isEditing(): bool
    {
        return $this->user !== null;
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'lowercase', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($this->user?->id),
            ],
            'department' => ['nullable', 'string', 'max:255'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            // Required when creating, optional when editing.
            'password' => [
                $this->isEditing() ? 'nullable' : 'required',
                'string', Password::defaults(),
            ],
            'status' => ['required', Rule::enum(UserStatus::class)],
            'roles' => ['array'],
            'roles.*' => [Rule::in(array_column(UserRole::cases(), 'value'))],
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return [
            'name' => __('common.labels.name'),
            'email' => __('common.labels.email'),
            'department' => __('common.labels.department'),
            'job_title' => __('Fonction'),
            'phone' => __('common.labels.phone'),
            'password' => __('auth.login.password'),
            'status' => __('common.labels.status'),
            'roles' => __('admin.users.roles'),
        ];
    }

    public function save(): void
    {
        $this->isEditing()
            ? $this->authorize('update', $this->user)
            : $this->authorize('create', User::class);

        $validated = $this->validate();

        $actor = auth()->user();
        $editingSelf = $this->isEditing() && $this->user->is($actor);

        /*
        | Two lockouts to prevent, both of which Gate::before would happily
        | allow an administrator to walk into: demoting yourself out of the
        | admin role, and disabling the last active administrator.
        */
        if ($editingSelf && $actor->isAdministrator()
            && ! in_array(UserRole::Administrator->value, $validated['roles'], true)) {
            $this->addError('roles', __('admin.users.messages.cannot_remove_own_admin'));

            return;
        }

        if ($this->isEditing()
            && $validated['status'] !== UserStatus::Active->value
            && $this->user->isLastActiveAdministrator()) {
            $this->addError('status', __('admin.users.messages.last_administrator'));

            return;
        }

        if ($editingSelf && $validated['status'] !== $this->user->status->value) {
            $this->addError('status', __('admin.users.messages.cannot_edit_self_status'));

            return;
        }

        $attributes = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'department' => $validated['department'] ?: null,
            'job_title' => $validated['job_title'] ?: null,
            'phone' => $validated['phone'] ?: null,
        ];

        if ($this->isEditing()) {
            $this->user->update($attributes);
            $user = $this->user;
        } else {
            $user = User::create($attributes + [
                'password' => Hash::make($validated['password']),
                'locale' => config('app.locale'),
            ]);

            // email_verified_at is outside $fillable, so it is set explicitly.
            // Admin-created accounts are pre-verified: the address came from
            // an administrator, not from self-registration (§12).
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        if ($this->isEditing() && filled($validated['password'])) {
            $user->forceFill(['password' => Hash::make($validated['password'])])->save();
        }

        // `status` is guarded against mass assignment (§39), so it is set
        // explicitly and only after the lockout checks above.
        $user->forceFill(['status' => UserStatus::from($validated['status'])])->save();

        $user->syncRoles($validated['roles']);

        activity('user')
            ->performedOn($user)
            ->causedBy($actor)
            ->event($this->isEditing() ? 'updated' : 'created')
            ->log($this->isEditing() ? 'user.updated' : 'user.created');

        $this->modal('user-form')->close();
        $this->dispatch('user-saved');

        Flux::toast(
            text: $this->isEditing()
                ? __('admin.users.messages.updated')
                : __('admin.users.messages.created'),
            variant: 'success',
        );
    }

    public function render(): View
    {
        return view('livewire.admin.users.form', [
            'availableRoles' => UserRole::cases(),
            'statuses' => UserStatus::options(),
        ]);
    }
}
