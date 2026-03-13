<?php

declare(strict_types=1);

namespace PhilipRehberger\BackgroundJobs;

use PhilipRehberger\BackgroundJobs\Contracts\Job;

final class JobPayload
{
    public function __construct(
        public readonly string $id,
        public readonly string $jobClass,
        public readonly string $serializedJob,
        public readonly int $attempts,
        public readonly float $availableAt,
        public readonly float $createdAt,
    ) {}

    public static function fromJob(Job $job, int $delaySeconds = 0): self
    {
        return new self(
            id: bin2hex(random_bytes(16)),
            jobClass: $job::class,
            serializedJob: serialize($job),
            attempts: 0,
            availableAt: microtime(true) + $delaySeconds,
            createdAt: microtime(true),
        );
    }

    /**
     * Deserialize and return the Job instance.
     */
    public function resolveJob(): Job
    {
        $job = unserialize($this->serializedJob);
        if (! $job instanceof Job) {
            throw new \RuntimeException("Failed to deserialize job: {$this->jobClass}");
        }

        return $job;
    }

    /**
     * Check if this job is available for processing.
     */
    public function isAvailable(): bool
    {
        return microtime(true) >= $this->availableAt;
    }

    /**
     * Create a new payload with incremented attempt count.
     */
    public function withIncrementedAttempts(): self
    {
        return new self(
            id: $this->id,
            jobClass: $this->jobClass,
            serializedJob: $this->serializedJob,
            attempts: $this->attempts + 1,
            availableAt: $this->availableAt,
            createdAt: $this->createdAt,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'job_class' => $this->jobClass,
            'serialized_job' => base64_encode($this->serializedJob),
            'attempts' => $this->attempts,
            'available_at' => $this->availableAt,
            'created_at' => $this->createdAt,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) $data['id'],
            jobClass: (string) $data['job_class'],
            serializedJob: base64_decode((string) $data['serialized_job'], true) ?: '',
            attempts: (int) $data['attempts'],
            availableAt: (float) $data['available_at'],
            createdAt: (float) $data['created_at'],
        );
    }
}
