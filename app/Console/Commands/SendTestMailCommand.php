<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendTestMailCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'mail:test {email : The address that should receive the test email}';

    /**
     * @var string
     */
    protected $description = 'Send a test email through the configured free SMTP mailer';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email');

        if (! is_string($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Please provide a valid email address.');

            return self::FAILURE;
        }

        if (! $this->smtpCredentialsAreSet()) {
            $this->error('SMTP is not configured yet.');
            $this->line('Create a free Brevo account, then add MAIL_USERNAME and MAIL_PASSWORD to your .env file.');

            return self::FAILURE;
        }

        if ($this->passwordLooksLikeApiKey()) {
            $this->error('MAIL_PASSWORD is a Brevo API key, not an SMTP key.');
            $this->line('In Brevo, open Settings > SMTP & API > SMTP. Copy the SMTP login and SMTP key. Do not use an API key (it starts with xkeysib-).');

            return self::FAILURE;
        }

        if ($this->usernameLooksLikeBrevoAccountEmail()) {
            $this->error('MAIL_USERNAME is not the Brevo SMTP login.');
            $this->line('In Brevo, open Settings > SMTP & API > SMTP and copy the Login value. It looks like xxx@smtp-brevo.com, not your inbox or school email.');
            $this->line('Keep MAIL_FROM_ADDRESS as the sender email you verified in Brevo.');

            return self::FAILURE;
        }

        $from = $this->fromAddress();

        if ($from === null) {
            $this->error('MAIL_FROM_ADDRESS is missing.');
            $this->line('Set MAIL_FROM_ADDRESS to the sender email you verified in Brevo.');

            return self::FAILURE;
        }

        try {
            Mail::raw('This is a test email from the Agusan National High School enrollment system. If you received this, password reset emails can be delivered.', function ($message) use ($email, $from): void {
                $message
                    ->from($from, (string) config('mail.from.name'))
                    ->to($email)
                    ->subject('ANHS SIS test email');
            });
        } catch (Throwable $exception) {
            $this->error('Sending failed: '.$exception->getMessage());

            if (str_contains($exception->getMessage(), 'Authentication failed')) {
                $this->line('Brevo rejected the SMTP login or SMTP key. Copy both from Settings > SMTP & API > SMTP. The login looks like xxx@smtp-brevo.com.');
            }

            return self::FAILURE;
        }

        $this->info("Test email sent to {$email}.");

        return self::SUCCESS;
    }

    private function smtpCredentialsAreSet(): bool
    {
        $username = config('mail.mailers.smtp.username');
        $password = config('mail.mailers.smtp.password');

        return is_string($username) && $username !== ''
            && is_string($password) && $password !== '';
    }

    private function passwordLooksLikeApiKey(): bool
    {
        $password = config('mail.mailers.smtp.password');

        return is_string($password) && str_starts_with($password, 'xkeysib-');
    }

    private function usernameLooksLikeBrevoAccountEmail(): bool
    {
        $host = config('mail.mailers.smtp.host');
        $username = config('mail.mailers.smtp.username');

        if (! is_string($host) || ! str_contains($host, 'brevo.com')) {
            return false;
        }

        return is_string($username) && ! str_ends_with(strtolower($username), '@smtp-brevo.com');
    }

    private function fromAddress(): ?string
    {
        $from = config('mail.from.address');

        if (! is_string($from) || ! filter_var($from, FILTER_VALIDATE_EMAIL) || $from === 'hello@example.com') {
            return null;
        }

        return $from;
    }
}
