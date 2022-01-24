<?php

declare(strict_types=1);

namespace PhilipRehberger\BackgroundJobs\Drivers;

use PhilipRehberger\BackgroundJobs\Contracts\QueueDriver;
use PhilipRehberger\BackgroundJobs\JobPayload;

final class FileDriver implements QueueDriver
{
    public function __construct(
        private readonly string $storagePath,
    ) {
        if (! is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0755, true);
        }
    }

    public function push(JobPayload $payload): void
    {
        $file = $this->storagePath.'/'.$payload->id.'.json';
        file_put_contents($file, json_encode($payload->toArray(), JSON_THROW_ON_ERROR), LOCK_EX);
    }

    public function pop(): ?JobPayload
    {
        $files = glob($this->storagePath.'/*.json');
        if ($files === false || empty($files)) {
            return null;
        }

        sort($files);

        foreach ($files as $file) {
            $content = file_get_contents($file);
            if ($content === false) {
                continue;
            }

            $data = json_decode($content, true);
            if (! is_array($data)) {
                continue;
            }

            $payload = JobPayload::fromArray($data);
            if ($payload->isAvailable()) {
                unlink($file);

                return $payload->withIncrementedAttempts();
            }
        }

        return null;
    }

    public function size(): int
    {
        $files = glob($this->storagePath.'/*.json');

        return $files !== false ? count($files) : 0;
    }

    public function clear(): void
    {
        $files = glob($this->storagePath.'/*.json');
        if ($files === false) {
            return;
        }
        foreach ($files as $file) {
            unlink($file);
        }
    }

    /**
     * @return list<JobPayload>
     */
    public function pending(): array
    {
        $files = glob($this->storagePath.'/*.json');
        if ($files === false || empty($files)) {
            return [];
        }

        sort($files);

        $payloads = [];
        foreach ($files as $file) {
            $content = file_get_contents($file);
            if ($content === false) {
                continue;
            }

            $data = json_decode($content, true);
            if (! is_array($data)) {
                continue;
            }

            $payloads[] = JobPayload::fromArray($data);
        }

        return $payloads;
    }
}
