<?php

namespace App\Livewire\Notifications;

use App\Livewire\Notifications\Concerns\ManagesNotifications;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Header notification bell (§26, §43 NotificationBell).
 *
 * Lives in the app shell, so it renders on every authenticated page: the
 * dropdown list is built only when opened rather than on every request.
 */
class Bell extends Component
{
    use ManagesNotifications;

    public bool $opened = false;

    public function toggle(): void
    {
        $this->opened = ! $this->opened;
    }

    public function render(): View
    {
        return view('livewire.notifications.bell', [
            'unread' => $this->unreadCount(),
            'recent' => $this->opened
                ? auth()->user()->notifications()->latest()->limit(6)->get()
                : collect(),
        ]);
    }
}
