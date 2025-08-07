<?php

declare(strict_types=1);

use PhoneBurner\Pinch\Framework\Scheduler\Config\SchedulerConfigStruct;

return [
    'scheduler' => new SchedulerConfigStruct(
        schedule_providers: [
            // 'default' => \PhoneBurner\Pinch\Framework\Scheduler\ApplicationScheduleProvider::class,
        ],
    ),
];
