<?php

declare(strict_types=1);

use PhoneBurner\Pinch\Framework\Notifier\Config\NotifierConfigStruct;
use PhoneBurner\Pinch\Framework\Notifier\Slack\Config\SlackWebhookNotifierConfigStruct;

use function PhoneBurner\Pinch\Framework\env;

return [
    'notifier' => new NotifierConfigStruct([
        'slack_webhooks' => new SlackWebhookNotifierConfigStruct(
            endpoint: (string)env('PINCH_SLACK_WEBHOOK_URL'),
            options: [
                'username' => 'Pinch',
                'channel' => (string)env('PINCH_SLACK_DEFAULT_CHANNEL'),
                'link_names' => true,
            ],
        ),
    ]),
];
