<?php

declare(strict_types=1);

namespace PhilipRehberger\BackgroundJobs\Contracts;

interface Job
{
    /**
     * Execute the job.
     */
    public function handle(): void;
}
