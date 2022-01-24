<?php

declare(strict_types=1);

namespace PhilipRehberger\BackgroundJobs\Tests\Stubs;

use PhilipRehberger\BackgroundJobs\BaseJob;

class FailingJob extends BaseJob
{
    public function handle(): void
    {
        throw new \RuntimeException('Job failed intentionally');
    }
}
