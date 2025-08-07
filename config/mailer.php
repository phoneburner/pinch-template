<?php

declare(strict_types=1);

use PhoneBurner\Pinch\Component\EmailAddress\EmailAddress;
use PhoneBurner\Pinch\Framework\Mailer\Config\MailerConfigStruct;
use PhoneBurner\Pinch\Framework\Mailer\Config\SendgridDriverConfigStruct;
use PhoneBurner\Pinch\Framework\Mailer\Config\SmtpDriverConfigStruct;
use PhoneBurner\Pinch\Framework\Mailer\Transport\TransportDriver;

use function PhoneBurner\Pinch\Framework\env;

return [
    'mailer' => new MailerConfigStruct(
        default_from_address: new EmailAddress(env('PINCH_MAILER_DEFAULT_FROM_ADDRESS', 'donotreply@example.com')),
        default_driver: env('PINCH_MAILER_DRIVER', TransportDriver::None, TransportDriver::Smtp),
        async: (bool)env('PINCH_MAILER_ASYNC', true),
        drivers: [
            TransportDriver::Smtp->value => new SmtpDriverConfigStruct(
                host: (string)env('PINCH_SMTP_HOST', development: 'mailhog'),
                port: (int)env('PINCH_SMTP_PORT', development: 1025),
                user: (string)env('PINCH_SMTP_USER', development: 'foo'),
                password: (string)env('PINCH_SMTP_PASS', development: 'bar'),
                encryption: (bool)env('PINCH_SMTP_SECURITY', true, false),
            ),
            TransportDriver::SendGrid->value => new SendgridDriverConfigStruct(env('PINCH_SENDGRID_API_KEY')),
        ],
    ),
];
