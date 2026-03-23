<?php

declare(strict_types=1);

namespace PhilipRehberger\BackgroundJobs\Tests;

use PhilipRehberger\BackgroundJobs\Drivers\FileDriver;
use PhilipRehberger\BackgroundJobs\Exceptions\JobFailedException;
use PhilipRehberger\BackgroundJobs\JobPayload;
use PhilipRehberger\BackgroundJobs\Queue;
use PhilipRehberger\BackgroundJobs\Tests\Stubs\FailingJob;
use PhilipRehberger\BackgroundJobs\Tests\Stubs\TestJob;
use PhilipRehberger\BackgroundJobs\Worker;
use PHPUnit\Framework\TestCase;

final class QueueTest extends TestCase
{
    private string $storagePath;

    private Queue $queue;

    protected function setUp(): void
    {
        $this->storagePath = sys_get_temp_dir().'/php-background-jobs-test-'.bin2hex(random_bytes(8));
        $driver = new FileDriver($this->storagePath);
        $this->queue = new Queue($driver);
        TestJob::$handled = false;
    }

    protected function tearDown(): void
    {
        $this->queue->clear();
        if (is_dir($this->storagePath)) {
            rmdir($this->storagePath);
        }
    }

    public function test_push_and_pop_job(): void
    {
        $job = new TestJob('hello');
        $id = $this->queue->push($job);

        $this->assertNotEmpty($id);

        $payload = $this->queue->pop();
        $this->assertInstanceOf(JobPayload::class, $payload);
        $this->assertSame($id, $payload->id);
    }

    public function test_job_handle_is_called(): void
    {
        $this->queue->push(new TestJob);

        $payload = $this->queue->pop();
        $this->assertNotNull($payload);

        $resolved = $payload->resolveJob();
        $resolved->handle();

        $this->assertTrue(TestJob::$handled);
    }

    public function test_push_multiple_jobs(): void
    {
        $this->queue->push(new TestJob('first'));
        $this->queue->push(new TestJob('second'));
        $this->queue->push(new TestJob('third'));

        $this->assertSame(3, $this->queue->size());
    }

    public function test_pop_returns_null_when_empty(): void
    {
        $this->assertNull($this->queue->pop());
    }

    public function test_queue_size(): void
    {
        $this->assertSame(0, $this->queue->size());

        $this->queue->push(new TestJob);
        $this->assertSame(1, $this->queue->size());

        $this->queue->push(new TestJob);
        $this->assertSame(2, $this->queue->size());

        $this->queue->pop();
        $this->assertSame(1, $this->queue->size());
    }

    public function test_queue_clear(): void
    {
        $this->queue->push(new TestJob);
        $this->queue->push(new TestJob);
        $this->assertSame(2, $this->queue->size());

        $this->queue->clear();
        $this->assertSame(0, $this->queue->size());
    }

    public function test_delayed_job_not_available_immediately(): void
    {
        $this->queue->later(new TestJob, 3600);

        $this->assertSame(1, $this->queue->size());
        $this->assertNull($this->queue->pop());
        $this->assertSame(1, $this->queue->size());
    }

    public function test_worker_processes_next_job(): void
    {
        $this->queue->push(new TestJob);

        $result = Worker::processNext($this->queue);

        $this->assertTrue($result);
        $this->assertTrue(TestJob::$handled);
    }

    public function test_worker_returns_false_when_empty(): void
    {
        $result = Worker::processNext($this->queue);

        $this->assertFalse($result);
    }

    public function test_failing_job_throws_job_failed_exception(): void
    {
        $this->queue->push(new FailingJob);

        $this->expectException(JobFailedException::class);
        $this->expectExceptionMessageMatches('/Job failed intentionally/');

        Worker::processNext($this->queue);
    }

    public function test_job_payload_serialization_round_trip(): void
    {
        $job = new TestJob('round-trip');
        $payload = JobPayload::fromJob($job);

        $array = $payload->toArray();
        $restored = JobPayload::fromArray($array);

        $this->assertSame($payload->id, $restored->id);
        $this->assertSame($payload->jobClass, $restored->jobClass);
        $this->assertSame($payload->attempts, $restored->attempts);

        $resolvedJob = $restored->resolveJob();
        $this->assertInstanceOf(TestJob::class, $resolvedJob);
        $this->assertSame('round-trip', $resolvedJob->data);
    }

    public function test_job_attempts_incremented(): void
    {
        $job = new TestJob;
        $payload = JobPayload::fromJob($job);

        $this->assertSame(0, $payload->attempts);

        $incremented = $payload->withIncrementedAttempts();
        $this->assertSame(1, $incremented->attempts);

        $incremented2 = $incremented->withIncrementedAttempts();
        $this->assertSame(2, $incremented2->attempts);
    }

    public function test_on_success_callback_fires_after_successful_job(): void
    {
        $called = false;
        $job = new TestJob;
        $job->onSuccess(function () use (&$called) {
            $called = true;
        });

        $this->queue->push($job);
        Worker::processNext($this->queue);

        $this->assertTrue($called);
    }

    public function test_on_failure_callback_fires_after_failed_job(): void
    {
        $called = false;
        $receivedException = null;
        $job = new FailingJob;
        $job->onFailure(function ($job, \Throwable $e) use (&$called, &$receivedException) {
            $called = true;
            $receivedException = $e;
        });

        $this->queue->push($job);

        try {
            Worker::processNext($this->queue);
        } catch (JobFailedException) {
            // Expected
        }

        $this->assertTrue($called);
        $this->assertNotNull($receivedException);
        $this->assertSame('Job failed intentionally', $receivedException->getMessage());
    }

    public function test_get_attempts_returns_correct_count(): void
    {
        $job = new TestJob;
        $this->assertSame(0, $job->getAttempts());

        $this->queue->push($job);
        Worker::processNext($this->queue);

        // After processing, the worker sets attempts from the payload.
        // The payload gets incremented to 1 when popped.
        // We verify by checking the job resolved inside the worker.
        // Instead, we test via a callback that captures the job.
        $attempts = null;
        $job2 = new TestJob;
        $job2->onSuccess(function ($j) use (&$attempts) {
            $attempts = $j->getAttempts();
        });

        $this->queue->push($job2);
        Worker::processNext($this->queue);

        $this->assertSame(1, $attempts);
    }

    public function test_pending_returns_correct_job_list(): void
    {
        $this->queue->push(new TestJob('first'));
        $this->queue->push(new TestJob('second'));
        $this->queue->push(new TestJob('third'));

        $pending = $this->queue->pending();

        $this->assertCount(3, $pending);
        $this->assertInstanceOf(JobPayload::class, $pending[0]);
        $this->assertInstanceOf(JobPayload::class, $pending[1]);
        $this->assertInstanceOf(JobPayload::class, $pending[2]);
    }

    public function test_pending_returns_empty_array_when_no_jobs(): void
    {
        $this->assertSame([], $this->queue->pending());
    }

    public function test_pending_decreases_after_processing(): void
    {
        $this->queue->push(new TestJob('a'));
        $this->queue->push(new TestJob('b'));

        $this->assertCount(2, $this->queue->pending());

        Worker::processNext($this->queue);

        $this->assertCount(1, $this->queue->pending());
    }
}
