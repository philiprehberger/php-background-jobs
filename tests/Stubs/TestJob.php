<?php

declare(strict_types=1);

namespace PhilipRehberger\BackgroundJobs\Tests\Stubs;

use PhilipRehberger\BackgroundJobs\Contracts\Job;

class TestJob implements Job
{
    public static bool $handled = false;

    public function __construct(
        public readonly string $data = 'test',
    ) {}

    public function handle(): void
    {
        self::$handled = true;
    }
}
