<?php

declare(strict_types=1);

namespace PhilipRehberger\BackgroundJobs;

use PhilipRehberger\BackgroundJobs\Contracts\Job;

abstract class BaseJob implements Job
{
    private int $attempts = 0;

    /**
     * @var array<string, list<callable>>
     */
    private static array $successRegistry = [];

    /**
     * @var array<string, list<callable>>
     */
    private static array $failureRegistry = [];

    /**
     * Register a callback that fires when the job completes successfully.
     */
    public function onSuccess(callable $callback): self
    {
        $key = $this->getRegistryKey();
        self::$successRegistry[$key][] = $callback;

        return $this;
    }

    /**
     * Register a callback that fires when the job fails.
     */
    public function onFailure(callable $callback): self
    {
        $key = $this->getRegistryKey();
        self::$failureRegistry[$key][] = $callback;

        return $this;
    }

    /**
     * Get how many times the job has been attempted.
     */
    public function getAttempts(): int
    {
        return $this->attempts;
    }

    /**
     * Set the attempt count (used internally by the worker).
     *
     * @internal
     */
    public function setAttempts(int $attempts): void
    {
        $this->attempts = $attempts;
    }

    /**
     * Promote callbacks from the object registry key to a job ID key.
     *
     * @internal
     */
    public function storeCallbacks(string $jobId): void
    {
        $objectKey = $this->getRegistryKey();

        if (isset(self::$successRegistry[$objectKey])) {
            self::$successRegistry[$jobId] = self::$successRegistry[$objectKey];
            unset(self::$successRegistry[$objectKey]);
        }

        if (isset(self::$failureRegistry[$objectKey])) {
            self::$failureRegistry[$jobId] = self::$failureRegistry[$objectKey];
            unset(self::$failureRegistry[$objectKey]);
        }
    }

    /**
     * Restore callbacks from the static registry for a given job ID.
     *
     * @internal
     */
    public function restoreCallbacks(string $jobId): void
    {
        $objectKey = $this->getRegistryKey();

        if (isset(self::$successRegistry[$jobId])) {
            self::$successRegistry[$objectKey] = self::$successRegistry[$jobId];
            unset(self::$successRegistry[$jobId]);
        }

        if (isset(self::$failureRegistry[$jobId])) {
            self::$failureRegistry[$objectKey] = self::$failureRegistry[$jobId];
            unset(self::$failureRegistry[$jobId]);
        }
    }

    /**
     * Fire all registered success callbacks.
     *
     * @internal
     */
    public function fireSuccessCallbacks(): void
    {
        $key = $this->getRegistryKey();
        $callbacks = self::$successRegistry[$key] ?? [];

        foreach ($callbacks as $callback) {
            $callback($this);
        }

        unset(self::$successRegistry[$key]);
    }

    /**
     * Fire all registered failure callbacks.
     *
     * @internal
     */
    public function fireFailureCallbacks(\Throwable $exception): void
    {
        $key = $this->getRegistryKey();
        $callbacks = self::$failureRegistry[$key] ?? [];

        foreach ($callbacks as $callback) {
            $callback($this, $exception);
        }

        unset(self::$failureRegistry[$key]);
    }

    private function getRegistryKey(): string
    {
        return 'obj_'.spl_object_id($this);
    }
}
