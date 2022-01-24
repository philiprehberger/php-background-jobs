<?php

declare(strict_types=1);

namespace PhilipRehberger\BackgroundJobs\Tests\Stubs;

use PhilipRehberger\BackgroundJobs\BaseJob;

class TestJob extends BaseJob
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
