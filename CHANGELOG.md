# Changelog

All notable changes to this project will be documented in this file.

## [1.0.1] - 2026-03-16

### Changed
- Standardize composer.json: add type, homepage, scripts

## [1.0.0] - 2026-03-13

### Added

- `Queue` class for pushing, popping, and managing jobs
- `Job` contract interface
- `QueueDriver` contract interface
- `JobPayload` serialized job wrapper with delay and attempt tracking
- `FileDriver` — JSON file-based queue driver
- `Worker` for processing queued jobs
- `JobFailedException` with factory methods for common failure scenarios
