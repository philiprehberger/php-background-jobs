<?php

declare(strict_types=1);

namespace PhilipRehberger\BackgroundJobs\Tests\Stubs;

use PhilipRehberger\BackgroundJobs\Contracts\Job;

class FailingJob implements Job
{
    public function handle(): void
    {
        throw new \RuntimeException('Job failed intentionally');
    }
}
