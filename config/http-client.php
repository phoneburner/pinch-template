<?php

declare(strict_types=1);

use PhoneBurner\Pinch\Framework\HttpClient\Webhook\Configuration\SimpleEventWebhookConfiguration;

return [
    'http_client' => [
        'webhooks' => [
            new SimpleEventWebhookConfiguration(
                uri: 'https://example.com/webhook/observations',
                events: [],
            ),
        ],
    ],
];
