# Changelog

All notable changes to this project will be documented in this file.

The format is inspired by Keep a Changelog and this project follows Semantic Versioning.

## [Unreleased]

### Added
- Expanded test coverage for manager operations (direct charge, lifecycle actions, subscription lifecycle).
- Added integration tests for webhook controller and install command.
- Added unit tests for HTTP client behavior and exception mapping.
- Added production-readiness checklist documentation.
- Added governance docs: SECURITY.md, UPGRADE.md, CONTRIBUTING.md.

### Changed
- Updated README to match implemented API and current documentation structure.
- Updated install command output to use PAYID_DEFAULT_DRIVER key.

### Fixed
- Modernized PHPStan configuration and removed deprecated config key usage.
- Improved static type annotations across DTOs, manager, testing helpers, and support classes.

## [1.0.0] - 2026-04-13

### Added
- Initial stable baseline for PayID core package.
- Core orchestrator manager with capability-based driver delegation.
- Webhook processing pipeline with verification/parsing event flow.
- DTO, enums, exceptions, facades, service provider integration.
- Fake driver and testing utilities.
