<?php

namespace App\Livewire\Notifications\Concerns;

use Illuminate\Notifications\DatabaseNotification;

/**
 * Shared read/open behaviour for the header bell and the notification centre
 * (§26), so the two cannot drift apart.
 *
 * Every lookup goes through the signed-in user's own relation, which is what
 * makes another user's notification id unreachable rather than merely
 * hidden (§39).
 */
trait ManagesNotifications
{
    protected function findOwnNotification(string $id): DatabaseNotification
    {
        return auth()->user()->notifications()->findOrFail($id);
    }

    public function markRead(string $id): void
    {
        $this->findOwnNotification($id)->markAsRead();
    }

    public function markAllRead(): void
    {
        auth()->user()->unreadNotifications->markAsRead();
    }

    /**
     * Mark a notification read and follow it to whatever it refers to.
     *
     * Reading and navigating are one action from the user's point of view, so
     * they are one method here rather than something the view has to
     * remember to pair up.
     */
    public function open(string $id)
    {
        $notification = $this->findOwnNotification($id);

        $notification->markAsRead();

        $url = $notification->data['url'] ?? null;

        // Fall back to the centre when a payload has no destination.
        return $this->redirect($url ?: route('notifications.index'), navigate: true);
    }

    public function unreadCount(): int
    {
        return auth()->user()->unreadNotifications()->count();
    }
}
