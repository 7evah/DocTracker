<?php

namespace App\Livewire\Notifications;

use App\Livewire\Notifications\Concerns\ManagesNotifications;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The notification centre (§26).
 *
 * No policy is involved: notifications are read through the signed-in user's
 * own relation, so there is nothing to authorise beyond being logged in.
 */
class Index extends Component
{
    use ManagesNotifications, WithPagination;

    /** all | unread | read */
    #[Url(except: 'all')]
    public string $filter = 'all';

    public int $perPage = 20;

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    public function markReadWithToast(string $id): void
    {
        $this->markRead($id);

        Flux::toast(text: __('notifications.messages.marked_read'), variant: 'success');
    }

    public function markUnread(string $id): void
    {
        $this->findOwnNotification($id)->update(['read_at' => null]);
    }

    public function markAllReadWithToast(): void
    {
        $this->markAllRead();

        Flux::toast(text: __('notifications.messages.all_marked_read'), variant: 'success');
    }

    public function delete(string $id): void
    {
        $this->findOwnNotification($id)->delete();

        Flux::toast(text: __('notifications.messages.deleted'), variant: 'success');
    }

    /** Housekeeping: clears everything already dealt with. */
    public function deleteRead(): void
    {
        auth()->user()->readNotifications()->delete();

        $this->resetPage();

        Flux::toast(text: __('notifications.messages.read_deleted'), variant: 'success');
    }

    private function notifications(): LengthAwarePaginator
    {
        return auth()->user()->notifications()
            ->when($this->filter === 'unread', fn (Builder $q) => $q->whereNull('read_at'))
            ->when($this->filter === 'read', fn (Builder $q) => $q->whereNotNull('read_at'))
            ->latest()
            ->paginate($this->perPage);
    }

    public function render(): View
    {
        return view('livewire.notifications.index', [
            'notifications' => $this->notifications(),
            'unread' => $this->unreadCount(),
            'hasRead' => auth()->user()->readNotifications()->exists(),
        ])->title(__('notifications.title'));
    }
}
