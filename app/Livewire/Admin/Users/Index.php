<?php

namespace App\Livewire\Admin\Users;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $role = '';

    #[Url(except: '')]
    public string $status = '';

    public int $perPage = 15;

    public function mount(): void
    {
        $this->authorize('viewAny', User::class);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'role', 'status'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'role', 'status');
        $this->resetPage();
    }

    public function hasFilters(): bool
    {
        return filled($this->search) || filled($this->role) || filled($this->status);
    }

    #[On('user-saved')]
    public function refreshList(): void
    {
        $this->resetPage();
    }

    /**
     * Toggle between active and inactive.
     *
     * Both guards are integrity rules that hold for administrators too, so
     * they are checked here rather than left to the policy (§29).
     */
    public function toggleStatus(int $userId): void
    {
        $user = User::findOrFail($userId);

        $this->authorize('update', $user);

        if (! $user->canHaveStatusChangedBy(auth()->user())) {
            Flux::toast(
                text: $user->is(auth()->user())
                    ? __('admin.users.messages.cannot_edit_self_status')
                    : __('admin.users.messages.last_administrator'),
                variant: 'danger',
            );

            return;
        }

        $user->forceFill([
            'status' => $user->status === UserStatus::Active ? UserStatus::Inactive : UserStatus::Active,
        ])->save();

        activity('user')
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->event('status_changed')
            ->withProperties(['status' => $user->status->value])
            ->log('user.status_changed');

        Flux::toast(text: __('admin.users.messages.status_changed'), variant: 'success');
    }

    public function delete(int $userId): void
    {
        $user = User::findOrFail($userId);

        $this->authorize('delete', $user);

        if (! $user->canBeDeletedBy(auth()->user())) {
            Flux::toast(
                text: $user->is(auth()->user())
                    ? __('admin.users.messages.cannot_delete_self')
                    : __('admin.users.messages.has_history'),
                variant: 'danger',
            );

            return;
        }

        $user->delete();

        Flux::toast(text: __('admin.users.messages.deleted'), variant: 'success');
    }

    private function users(): LengthAwarePaginator
    {
        return User::query()
            ->with('roles')
            ->search($this->search)
            ->when($this->role, fn (Builder $q) => $q->whereHas(
                'roles',
                fn (Builder $r) => $r->where('name', $this->role),
            ))
            ->when($this->status, fn (Builder $q) => $q->where('status', $this->status))
            ->orderBy('name')
            ->paginate($this->perPage)
            ->withQueryString();
    }

    public function render(): View
    {
        return view('livewire.admin.users.index', [
            'users' => $this->users(),
            'roles' => UserRole::options(),
            'statuses' => UserStatus::options(),
        ])->title(__('admin.users.title'));
    }
}
