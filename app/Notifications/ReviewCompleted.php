<?php

namespace App\Notifications;

use App\Enums\ReviewStatus;
use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells the document author the outcome of a review (§26):
 * "Revision B of ME-1023 has been approved." / "…requires revision."
 */
class ReviewCompleted extends Notification implements ShouldQueue
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
            'type' => 'review.'.$this->review->status->value,
            'icon' => $this->review->status->icon(),
            'color' => $this->review->status->color(),
            'review_id' => $this->review->id,
            'document_id' => $document->id,
            'document_number' => $document->document_number,
            'document_title' => $document->title,
            'revision' => $this->review->documentVersion->revision,
            'url' => route('documents.show', $document),
            'message' => $this->message(),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $document = $this->review->documentVersion->document;

        $mail = (new MailMessage)
            ->subject(__('notifications.mail.review_completed_subject', [
                'number' => $document->document_number,
            ]))
            ->greeting(__('notifications.mail.greeting', ['name' => $notifiable->name]))
            ->line($this->message());

        if (filled($this->review->summary)) {
            $mail->line(__('reviews.fields.summary').' : '.$this->review->summary);
        }

        return $mail
            ->action(__('documents.singular'), route('documents.show', $document))
            ->salutation(__('notifications.mail.salutation'));
    }

    private function message(): string
    {
        $document = $this->review->documentVersion->document;

        $key = match ($this->review->status) {
            ReviewStatus::Approved => 'notifications.review_approved',
            ReviewStatus::RevisionRequested => 'notifications.review_revision_requested',
            ReviewStatus::Rejected => 'notifications.review_rejected',
            default => 'notifications.review_updated',
        };

        return __($key, [
            'number' => $document->document_number,
            'revision' => $this->review->documentVersion->revision,
            'reviewer' => $this->review->reviewer?->name ?? '',
        ]);
    }
}
