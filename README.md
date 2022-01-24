# PHP Background Jobs

[![Tests](https://github.com/philiprehberger/php-background-jobs/actions/workflows/tests.yml/badge.svg)](https://github.com/philiprehberger/php-background-jobs/actions/workflows/tests.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/philiprehberger/php-background-jobs.svg)](https://packagist.org/packages/philiprehberger/php-background-jobs)
[![License](https://img.shields.io/github/license/philiprehberger/php-background-jobs)](LICENSE)

Lightweight background job queue with file-based driver.

## Requirements

- PHP 8.2+

## Installation

```bash
composer require philiprehberger/php-background-jobs
```

## Usage

### Define a Job

Implement the `Job` interface:

```php
use PhilipRehberger\BackgroundJobs\Contracts\Job;

class SendEmailJob implements Job
{
    public function __construct(
        private readonly string $to,
        private readonly string $subject,
    ) {}

    public function handle(): void
    {
        // Send the email...
    }
}
```

### Create a Queue

Use the built-in `FileDriver` to store jobs as JSON files:

```php
use PhilipRehberger\BackgroundJobs\Drivers\FileDriver;
use PhilipRehberger\BackgroundJobs\Queue;

$driver = new FileDriver('/path/to/storage/queue');
$queue = new Queue($driver);
```

### Push Jobs

```php
// Push a job for immediate processing
$id = $queue->push(new SendEmailJob('user@example.com', 'Welcome!'));

// Push a job with a delay (in seconds)
$id = $queue->later(new SendEmailJob('user@example.com', 'Reminder'), 3600);
```

### Process Jobs with the Worker

```php
use PhilipRehberger\BackgroundJobs\Worker;

// Process the next available job
$processed = Worker::processNext($queue);

// Process with a custom max attempts limit
$processed = Worker::processNext($queue, maxAttempts: 5);
```

### Queue Management

```php
// Get the number of pending jobs
$count = $queue->size();

// Clear all jobs
$queue->clear();

// Pop a job manually
$payload = $queue->pop();
if ($payload !== null) {
    $job = $payload->resolveJob();
    $job->handle();
}
```

### Custom Queue Driver

Implement the `QueueDriver` interface to use a different storage backend:

```php
use PhilipRehberger\BackgroundJobs\Contracts\QueueDriver;
use PhilipRehberger\BackgroundJobs\JobPayload;

class RedisDriver implements QueueDriver
{
    public function push(JobPayload $payload): void { /* ... */ }
    public function pop(): ?JobPayload { /* ... */ }
    public function size(): int { /* ... */ }
    public function clear(): void { /* ... */ }
}
```

### Lifecycle Hooks

Register callbacks that fire when a job succeeds or fails:

```php
use PhilipRehberger\BackgroundJobs\BaseJob;

class SendEmailJob extends BaseJob
{
    public function __construct(
        private readonly string $to,
    ) {}

    public function handle(): void
    {
        // Send the email...
    }
}

$job = new SendEmailJob('user@example.com');

$job->onSuccess(function ($job) {
    echo "Job completed after {$job->getAttempts()} attempt(s).";
});

$job->onFailure(function ($job, \Throwable $e) {
    echo "Job failed: {$e->getMessage()}";
});

$queue->push($job);
```

### Error Handling

Failed jobs throw a `JobFailedException`:

```php
use PhilipRehberger\BackgroundJobs\Exceptions\JobFailedException;

try {
    Worker::processNext($queue);
} catch (JobFailedException $e) {
    echo $e->getMessage();
    echo $e->payload->id;        // Job ID
    echo $e->payload->jobClass;  // Original job class
    echo $e->payload->attempts;  // Number of attempts
}
```

## API

| Class | Method | Description |
|---|---|---|
| `Queue` | `push(Job $job): string` | Push a job, returns job ID |
| `Queue` | `later(Job $job, int $delaySeconds): string` | Push a delayed job |
| `Queue` | `pop(): ?JobPayload` | Pop the next available job |
| `Queue` | `size(): int` | Get pending job count |
| `Queue` | `clear(): void` | Remove all jobs |
| `Queue` | `pending(): array` | Get all pending job payloads |
| `Worker` | `processNext(Queue $queue, int $maxAttempts = 3): bool` | Process next job |
| `BaseJob` | `onSuccess(callable $callback): self` | Register a success lifecycle hook |
| `BaseJob` | `onFailure(callable $callback): self` | Register a failure lifecycle hook |
| `BaseJob` | `getAttempts(): int` | Get the number of attempts |
| `JobPayload` | `resolveJob(): Job` | Deserialize the job instance |
| `JobPayload` | `isAvailable(): bool` | Check if job is ready to process |
| `JobPayload` | `withIncrementedAttempts(): self` | Clone with incremented attempts |
| `JobPayload` | `toArray(): array` | Serialize to array |
| `JobPayload` | `fromArray(array $data): self` | Restore from array |

## Development

```bash
composer install
vendor/bin/phpunit
vendor/bin/pint --test
vendor/bin/phpstan analyse
```

## License

MIT
