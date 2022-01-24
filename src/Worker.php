<?php

declare(strict_types=1);

namespace PhilipRehberger\BackgroundJobs;

use PhilipRehberger\BackgroundJobs\Exceptions\JobFailedException;

final class Worker
{
    /**
     * Process a single job from the queue.
     *
     * @return bool True if a job was processed, false if queue was empty
     *
     * @throws JobFailedException
     */
    public static function processNext(Queue $queue, int $maxAttempts = 3): bool
    {
        $payload = $queue->pop();
        if ($payload === null) {
            return false;
        }

        if ($payload->attempts > $maxAttempts) {
            throw JobFailedException::maxAttemptsExceeded($payload);
        }

        try {
            $job = $payload->resolveJob();

            if ($job instanceof BaseJob) {
                $job->setAttempts($payload->attempts);
                $job->restoreCallbacks($payload->id);
            }

            $job->handle();

            if ($job instanceof BaseJob) {
                $job->fireSuccessCallbacks();
            }

            return true;
        } catch (\Throwable $e) {
            if (isset($job) && $job instanceof BaseJob) {
                $job->fireFailureCallbacks($e);
            }

            throw JobFailedException::fromException($payload, $e);
        }
    }
}
