<?php

namespace App\Console\Commands;

use App\Notifications\TemporaryPasswordIssued;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Send one message, right now, and say plainly what happened.
 *
 * Exists because the normal path hides exactly the information you need while
 * setting mail up: notifications are queued, so a bad password surfaces as a
 * job quietly failing in a worker window rather than as an error in front of
 * you. This sends synchronously and prints the transport's own complaint.
 */
class MailTest extends Command
{
    protected $signature = 'mail:test
        {recipient : Address to send to}
        {--design : Send the branded notification instead of a plain line}';

    protected $description = 'Send a test e-mail synchronously and report the result';

    public function handle(): int
    {
        $recipient = $this->argument('recipient');

        $this->line('  mailer   : '.config('mail.default'));
        $this->line('  host     : '.config('mail.mailers.smtp.host').':'.config('mail.mailers.smtp.port'));
        $this->line('  username : '.(config('mail.mailers.smtp.username') ?: '<empty>'));
        $this->line('  from     : '.config('mail.from.address'));
        $this->newLine();

        if (config('mail.default') === 'log') {
            $this->warn('MAIL_MAILER=log — this will be written to storage/logs/laravel.log, not sent.');
        }

        if (config('mail.default') === 'smtp' && blank(config('mail.mailers.smtp.username'))) {
            $this->error('MAIL_USERNAME is empty. Fill in your provider credentials in .env first.');

            return self::FAILURE;
        }

        try {
            if ($this->option('design')) {
                // Notification::route sends on demand, so no account is touched
                // and nothing depends on a queue worker being current.
                Notification::route('mail', $recipient)
                    ->notify((new TemporaryPasswordIssued('EXEMPLE12345'))->onConnection('sync'));
            } else {
                Mail::raw(
                    'DocFlow test message. If you are reading this, SMTP is configured correctly.',
                    fn ($message) => $message->to($recipient)->subject('DocFlow — test'),
                );
            }
        } catch (Throwable $e) {
            $this->error('Sending failed:');
            $this->line('  '.$e->getMessage());
            $this->newLine();
            $this->line($this->hintFor($e->getMessage()));

            return self::FAILURE;
        }

        $this->info('Sent to '.$recipient.'.');

        if (str_ends_with($recipient, '@yopmail.com')) {
            $this->line('Read it at https://yopmail.com — no signup, just type the address.');
        }

        return self::SUCCESS;
    }

    /** Turn the provider's wording into the thing that actually needs doing. */
    private function hintFor(string $message): string
    {
        return match (true) {
            str_contains($message, '535') || stripos($message, 'authentication') !== false => 'The provider rejected the credentials. For Brevo, MAIL_PASSWORD is the SMTP key from '
                    .'SMTP & API → SMTP, not your account password, and MAIL_USERNAME is the login shown beside it.',

            stripos($message, 'sender') !== false || str_contains($message, '550') => 'The provider refused the sender address. MAIL_FROM_ADDRESS has to be one the provider has '
                    .'verified — add and confirm it under Senders, or use an address you already verified.',

            stripos($message, 'could not be established') !== false || stripos($message, 'timed out') !== false => 'Could not reach the SMTP host. Check MAIL_HOST and MAIL_PORT, and whether a firewall is '
                    .'blocking outbound 587.',

            default => 'Full details are in storage/logs/laravel.log.',
        };
    }
}
