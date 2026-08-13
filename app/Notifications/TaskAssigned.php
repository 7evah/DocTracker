<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** "A task has been assigned to you." (§26, §27) */
class TaskAssigned extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Task $task) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'task.assigned',
            'icon' => 'clipboard-document-check',
            'color' => $this->task->priority->color(),
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'project_id' => $this->task->project_id,
            'priority' => $this->task->priority->value,
            'due_date' => $this->task->due_date?->toDateString(),
            'url' => route('tasks.index'),
            'message' => __('notifications.task_assigned', ['title' => $this->task->title]),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('notifications.mail.task_assigned_subject'))
            ->greeting(__('notifications.mail.greeting', ['name' => $notifiable->name]))
            ->line(__('notifications.task_assigned', ['title' => $this->task->title]))
            ->when(
                filled($this->task->description),
                fn (MailMessage $mail) => $mail->line($this->task->description),
            )
            ->when(
                $this->task->due_date !== null,
                fn (MailMessage $mail) => $mail->line(__('notifications.mail.deadline', [
                    'date' => $this->task->due_date->translatedFormat('d F Y'),
                ])),
            )
            ->action(__('tasks.actions.open'), route('tasks.index'))
            ->salutation(__('notifications.mail.salutation'));
    }
}
