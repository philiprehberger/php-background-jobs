<?php

declare(strict_types=1);

namespace PhilipRehberger\BackgroundJobs\Exceptions;

use PhilipRehberger\BackgroundJobs\JobPayload;
use RuntimeException;

class JobFailedException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly JobPayload $payload,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public static function maxAttemptsExceeded(JobPayload $payload): self
    {
        return new self(
            "Job {$payload->id} ({$payload->jobClass}) exceeded max attempts ({$payload->attempts}).",
            $payload,
        );
    }

    public static function fromException(JobPayload $payload, \Throwable $exception): self
    {
        return new self(
            "Job {$payload->id} ({$payload->jobClass}) failed: {$exception->getMessage()}",
            $payload,
            $exception,
        );
    }
}
