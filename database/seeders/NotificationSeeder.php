<?php

namespace Database\Seeders;

use App\Enums\ApprovalStatus;
use App\Enums\ReviewStatus;
use App\Enums\TaskStatus;
use App\Models\Approval;
use App\Models\Review;
use App\Models\Task;
use App\Notifications\ApprovalRequested;
use App\Notifications\ReviewAssigned;
use App\Notifications\TaskAssigned;
use Illuminate\Database\Seeder;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class NotificationSeeder extends Seeder
{
    /**
     * Populates the notification centre for the demo (§26).
     *
     * The other seeders write rows directly rather than going through the
     * services, so no notification is ever dispatched and the centre would
     * otherwise be empty.
     *
     * Payloads are produced by calling the real Notification classes'
     * toArray(), then inserted directly. That keeps them byte-identical to
     * what the running application produces — a hand-written payload here
     * would drift the moment a notification class changed — while avoiding
     * the mail channel, which would otherwise fill the log during seeding.
     */
    public function run(): void
    {
        $this->seedFor(
            Review::with(['documentVersion.document', 'reviewer'])
                ->whereIn('status', ReviewStatus::openValues())
                ->get(),
            fn (Review $review) => [
                'notifiable' => $review->reviewer,
                'notification' => new ReviewAssigned($review),
                'at' => $review->assigned_at,
            ],
        );

        $this->seedFor(
            Approval::with(['documentVersion.document', 'approver'])
                ->where('status', ApprovalStatus::InProgress)
                ->get(),
            fn (Approval $approval) => [
                'notifiable' => $approval->approver,
                'notification' => new ApprovalRequested($approval),
                'at' => $approval->assigned_at,
            ],
        );

        $this->seedFor(
            Task::with('assignee')
                ->whereIn('status', TaskStatus::openValues())
                ->get(),
            fn (Task $task) => [
                'notifiable' => $task->assignee,
                'notification' => new TaskAssigned($task),
                'at' => $task->created_at,
            ],
        );
    }

    /**
     * @param  Collection<int, mixed>  $sources
     * @param  callable(mixed): array{notifiable: mixed, notification: Notification, at: mixed}  $resolve
     */
    private function seedFor($sources, callable $resolve): void
    {
        foreach ($sources as $index => $source) {
            ['notifiable' => $notifiable, 'notification' => $notification, 'at' => $at] = $resolve($source);

            if (! $notifiable) {
                continue;
            }

            $at ??= now()->subDays(2);

            // Leave the newest few unread so the bell badge is non-zero and
            // the read/unread filters both have something to show.
            $read = $index % 3 !== 0;

            $notifiable->notifications()->create([
                'id' => (string) Str::uuid(),
                'type' => $notification::class,
                'data' => $notification->toArray($notifiable),
                'read_at' => $read ? $at->copy()->addHours(6) : null,
                'created_at' => $at,
                'updated_at' => $at,
            ]);
        }
    }
}
