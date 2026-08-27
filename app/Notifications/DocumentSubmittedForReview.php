<?php

namespace App\Notifications;

use App\Models\DocumentVersion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * "PI-1023 revision B has been submitted for review." (§26)
 *
 * Sent to the project manager, who is the one who assigns reviewers. Without
 * it, submitting a revision was silent: the author pressed the button, the
 * status changed, and nobody was told the document was now waiting on
 * somebody to pick a reviewer.
 *
 * $reviewersCarriedForward says whether standing reviewers were re-assigned
 * automatically, because that decides whether the manager has anything to do.
 */
class DocumentSubmittedForReview extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly DocumentVersion $version,
        public readonly bool $reviewersCarriedForward = false,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    private function messageKey(): string
    {
        return $this->reviewersCarriedForward
            ? 'notifications.document_submitted_reviewers_kept'
            : 'notifications.document_submitted_needs_reviewer';
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $document = $this->version->document;

        return [
            'type' => 'document.submitted',
            'icon' => 'paper-airplane',
            // Amber when it needs someone to act, sky when it is just news.
            'color' => $this->reviewersCarriedForward ? 'sky' : 'amber',
            'document_id' => $document->id,
            'document_number' => $document->document_number,
            'document_title' => $document->title,
            'revision' => $this->version->revision,
            'reviewers_carried_forward' => $this->reviewersCarriedForward,
            'url' => route('documents.show', $document),
            'message' => __($this->messageKey(), [
                'number' => $document->document_number,
                'revision' => $this->version->revision,
            ]),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $document = $this->version->document;

        return (new MailMessage)
            ->subject(__('notifications.mail.document_submitted_subject', [
                'number' => $document->document_number,
                'revision' => $this->version->revision,
            ]))
            ->greeting(__('notifications.mail.greeting', ['name' => $notifiable->name]))
            ->line(__($this->messageKey(), [
                'number' => $document->document_number,
                'revision' => $this->version->revision,
            ]))
            ->line($document->title)
            ->action(__('documents.actions.open'), route('documents.show', $document))
            ->salutation(__('notifications.mail.salutation'));
    }
}
