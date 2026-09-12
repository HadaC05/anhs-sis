<?php

use Illuminate\Support\Facades\Mail;

test('mail test command requires smtp credentials', function () {
    config([
        'mail.mailers.smtp.username' => null,
        'mail.mailers.smtp.password' => null,
    ]);

    $this->artisan('mail:test', ['email' => 'test@example.com'])
        ->expectsOutputToContain('SMTP is not configured yet.')
        ->assertFailed();
});

test('mail test command rejects a brevo api key used as the smtp password', function () {
    config([
        'mail.mailers.smtp.username' => 'anhs@example.com',
        'mail.mailers.smtp.password' => 'xkeysib-example-api-key',
        'mail.from.address' => 'anhs@example.com',
    ]);

    $this->artisan('mail:test', ['email' => 'test@example.com'])
        ->expectsOutputToContain('MAIL_PASSWORD is a Brevo API key, not an SMTP key.')
        ->assertFailed();
});

test('mail test command requires a verified from address', function () {
    config([
        'mail.mailers.smtp.host' => 'smtp-relay.brevo.com',
        'mail.mailers.smtp.username' => 'login@smtp-brevo.com',
        'mail.mailers.smtp.password' => 'smtp-key',
        'mail.from.address' => '',
    ]);

    $this->artisan('mail:test', ['email' => 'test@example.com'])
        ->expectsOutputToContain('MAIL_FROM_ADDRESS is missing.')
        ->assertFailed();
});

test('mail test command rejects an account email used as the brevo smtp login', function () {
    config([
        'mail.mailers.smtp.host' => 'smtp-relay.brevo.com',
        'mail.mailers.smtp.username' => 'teacher@school.com',
        'mail.mailers.smtp.password' => 'smtp-key',
        'mail.from.address' => 'teacher@school.com',
    ]);

    $this->artisan('mail:test', ['email' => 'test@example.com'])
        ->expectsOutputToContain('MAIL_USERNAME is not the Brevo SMTP login.')
        ->assertFailed();
});

test('mail test command rejects an invalid email address', function () {
    $this->artisan('mail:test', ['email' => 'not-an-email'])
        ->expectsOutputToContain('Please provide a valid email address.')
        ->assertFailed();
});

test('mail test command sends a test email when smtp is configured', function () {
    Mail::fake();

    config([
        'mail.default' => 'array',
        'mail.mailers.smtp.host' => 'smtp-relay.brevo.com',
        'mail.mailers.smtp.username' => 'login@smtp-brevo.com',
        'mail.mailers.smtp.password' => 'smtp-key',
        'mail.from.address' => 'anhs@example.com',
        'mail.from.name' => 'Agusan National High School',
    ]);

    $this->artisan('mail:test', ['email' => 'test@example.com'])
        ->expectsOutputToContain('Test email sent to test@example.com.')
        ->assertSuccessful();
});
