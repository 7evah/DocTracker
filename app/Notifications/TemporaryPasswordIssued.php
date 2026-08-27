<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

/**
 * Mails the one-off password issued by the forgot-password form (§4).
 *
 * Mail only, deliberately: a database notification would put the password in
 * the bell dropdown of a session that, by definition, the person cannot open.
 */
class TemporaryPasswordIssued extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $temporaryPassword) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('notifications.mail.temporary_password_subject'))
            ->greeting(__('notifications.mail.greeting', ['name' => $notifiable->name]))
            ->line(__('passwords.temporary.intro'))
            /*
            | Raw HTML rather than markdown bold: this is a string somebody has
            | to copy accurately, so it gets a monospaced box with letter
            | spacing instead of sitting in the prose. Inline styles, because
            | mail clients drop <style> blocks.
            */
            ->line(new HtmlString(
                '<div style="background-color:#eef4fa;border:1px solid #b0cbe5;border-radius:8px;'
                .'padding:16px;margin:8px 0;text-align:center;font-family:ui-monospace,SFMono-Regular,'
                .'Menlo,Consolas,monospace;font-size:22px;font-weight:700;letter-spacing:3px;'
                .'color:#003a70;">'.e($this->temporaryPassword).'</div>'
            ))
            ->line(__('passwords.temporary.expires', [
                'minutes' => User::TEMPORARY_PASSWORD_TTL_MINUTES,
            ]))
            ->action(__('auth.login.submit'), route('login'))
            ->line(__('passwords.temporary.then'))
            // Says plainly that the old password still works, because the
            // whole point of keeping both is that a stray request costs
            // nobody their access.
            ->line(__('passwords.temporary.unaffected'))
            ->salutation(__('notifications.mail.salutation'));
    }
}
