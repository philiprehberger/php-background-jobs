<?php

declare(strict_types=1);

namespace PhilipRehberger\BackgroundJobs;

use PhilipRehberger\BackgroundJobs\Contracts\Job;
use PhilipRehberger\BackgroundJobs\Contracts\QueueDriver;

final class Queue
{
    public function __construct(
        private readonly QueueDriver $driver,
    ) {}

    /**
     * Push a job onto the queue.
     */
    public function push(Job $job): string
    {
        $payload = JobPayload::fromJob($job);
        $this->driver->push($payload);

        return $payload->id;
    }

    /**
     * Push a job with a delay.
     */
    public function later(Job $job, int $delaySeconds): string
    {
        $payload = JobPayload::fromJob($job, $delaySeconds);
        $this->driver->push($payload);

        return $payload->id;
    }

    /**
     * Pop the next available job from the queue.
     */
    public function pop(): ?JobPayload
    {
        return $this->driver->pop();
    }

    /**
     * Get the number of jobs in the queue.
     */
    public function size(): int
    {
        return $this->driver->size();
    }

    /**
     * Clear all jobs from the queue.
     */
    public function clear(): void
    {
        $this->driver->clear();
    }
}
