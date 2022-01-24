# Changelog

All notable changes to `php-background-jobs` will be documented in this file.

## [Unreleased]

## [1.1.0] - 2026-03-22

### Added
- `onSuccess()` and `onFailure()` lifecycle hook methods on Job
- `getAttempts()` method for tracking job attempt count
- `pending()` method on the job runner for listing queued jobs

## [1.0.2] - 2026-03-17

### Changed
- Standardized package metadata, README structure, and CI workflow per package guide

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
