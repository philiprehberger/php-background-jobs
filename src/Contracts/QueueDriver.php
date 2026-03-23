<?php

declare(strict_types=1);

namespace PhilipRehberger\BackgroundJobs\Contracts;

use PhilipRehberger\BackgroundJobs\JobPayload;

interface QueueDriver
{
    public function push(JobPayload $payload): void;

    public function pop(): ?JobPayload;

    public function size(): int;

    public function clear(): void;

    /**
     * Get all pending job payloads.
     *
     * @return list<JobPayload>
     */
    public function pending(): array;
}
