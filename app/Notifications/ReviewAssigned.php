<?php

namespace App\Notifications;

use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * "Document ME-1023 has been assigned to you for review." (§26)
 *
 * Queued so a bulk assignment does not block the request while mail is sent.
 */
class ReviewAssigned extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Review $review) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $document = $this->review->documentVersion->document;

        return [
            'type' => 'review.assigned',
            'icon' => 'eye',
            'color' => 'sky',
            'review_id' => $this->review->id,
            'document_id' => $document->id,
            'document_number' => $document->document_number,
            'document_title' => $document->title,
            'revision' => $this->review->documentVersion->revision,
            'priority' => $this->review->priority->value,
            'deadline' => $this->review->deadline?->toIso8601String(),
            'url' => route('reviews.show', $this->review),
            'message' => __('notifications.review_assigned', [
                'number' => $document->document_number,
                'revision' => $this->review->documentVersion->revision,
            ]),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $document = $this->review->documentVersion->document;

        return (new MailMessage)
            ->subject(__('notifications.mail.review_assigned_subject', [
                'number' => $document->document_number,
            ]))
            ->greeting(__('notifications.mail.greeting', ['name' => $notifiable->name]))
            ->line(__('notifications.review_assigned', [
                'number' => $document->document_number,
                'revision' => $this->review->documentVersion->revision,
            ]))
            ->line($document->title)
            ->when(
                $this->review->deadline !== null,
                fn (MailMessage $mail) => $mail->line(__('notifications.mail.deadline', [
                    'date' => $this->review->deadline->translatedFormat('d F Y'),
                ])),
            )
            ->action(__('reviews.actions.open'), route('reviews.show', $this->review))
            ->salutation(__('notifications.mail.salutation'));
    }
}
