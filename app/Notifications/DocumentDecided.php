<?php

namespace App\Notifications;

use App\Models\Document;
use App\Models\DocumentVersion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The final outcome of the approval circuit, sent to the document's author
 * (§26): "Revision B of ME-1023 has been approved."
 */
class DocumentDecided extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Document $document,
        public readonly DocumentVersion $version,
        public readonly bool $approved,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->approved ? 'document.approved' : 'document.rejected',
            'icon' => $this->approved ? 'check-circle' : 'x-circle',
            'color' => $this->approved ? 'green' : 'red',
            'document_id' => $this->document->id,
            'document_number' => $this->document->document_number,
            'document_title' => $this->document->title,
            'revision' => $this->version->revision,
            'url' => route('documents.show', $this->document),
            'message' => $this->message(),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('notifications.mail.document_decided_subject', [
                'number' => $this->document->document_number,
            ]))
            ->greeting(__('notifications.mail.greeting', ['name' => $notifiable->name]))
            ->line($this->message())
            ->line($this->document->title)
            ->action(__('documents.singular'), route('documents.show', $this->document))
            ->salutation(__('notifications.mail.salutation'));
    }

    private function message(): string
    {
        return __($this->approved ? 'notifications.document_approved' : 'notifications.document_rejected', [
            'number' => $this->document->document_number,
            'revision' => $this->version->revision,
        ]);
    }
}
