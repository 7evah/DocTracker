<?php

namespace App\Notifications;

use App\Models\Approval;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent when an approval step becomes the active one (§26).
 *
 * Deliberately fired on activation rather than on instantiation: an approver
 * three steps down the circuit should not be pinged until it is actually
 * their turn.
 */
class ApprovalRequested extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Approval $approval) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $version = $this->approval->documentVersion;
        $document = $version->document;

        return [
            'type' => 'approval.requested',
            'icon' => 'check-badge',
            'color' => 'sky',
            'approval_id' => $this->approval->id,
            'document_id' => $document->id,
            'document_number' => $document->document_number,
            'document_title' => $document->title,
            'revision' => $version->revision,
            'step' => $this->approval->step,
            'deadline' => $this->approval->deadline?->toIso8601String(),
            'url' => route('documents.show', $document).'?tab=approvals',
            'message' => __('notifications.approval_requested', [
                'number' => $document->document_number,
                'revision' => $version->revision,
            ]),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $version = $this->approval->documentVersion;
        $document = $version->document;

        return (new MailMessage)
            ->subject(__('notifications.mail.approval_requested_subject', [
                'number' => $document->document_number,
            ]))
            ->greeting(__('notifications.mail.greeting', ['name' => $notifiable->name]))
            ->line(__('notifications.approval_requested', [
                'number' => $document->document_number,
                'revision' => $version->revision,
            ]))
            ->line($document->title)
            ->when(
                $this->approval->deadline !== null,
                fn (MailMessage $mail) => $mail->line(__('notifications.mail.deadline', [
                    'date' => $this->approval->deadline->translatedFormat('d F Y'),
                ])),
            )
            ->action(__('approvals.actions.open'), route('documents.show', $document).'?tab=approvals')
            ->salutation(__('notifications.mail.salutation'));
    }
}
