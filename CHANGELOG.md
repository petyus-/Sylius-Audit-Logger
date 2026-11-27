# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.1.1] - 2026-11-24

### Added

- `AuditEntryRecordedEvent` dispatched after every recorded entry — stable extension point for
  alerting, export and tamper-evident hashing without touching core code.
- README section documenting both extension points (`DriverInterface`, `AuditEntryRecordedEvent`).

## [0.1.0] - 2026-11-24

### Added

- Audit entry entity, factory and repository (find, findByResource, add) with Doctrine mapping.
- Storage drivers: `DoctrineAuditDriver` (default) and `FileAuditDriver` (JSONL append), both behind
  a `DriverInterface` contract with a swappable container alias for custom drivers.
- Automatic change capture: Doctrine `onFlush` listener collecting changesets of whitelisted
  resources, persisted on `kernel.terminate`.
- Actor resolution: admin user email from the security token and client IP address.
- Semantic configuration (`petyus_audit_logger`): `resources` whitelist, `driver` (`doctrine`|`file`),
  `file.path`, `retention_days` (default `0`, purging disabled).
- Admin UI: read-only audit entry grid with filters at `/admin/audit-logs/`, per-entry changeset
  detail page, and an *Audit Logs* item in the Sylius admin main menu.
- Retention purge command `petyus:audit-logger:purge` with `--days` override and `--dry-run`
  support; bulk DQL delete, no-op notice when retention is disabled.
- Functional kernel test suite (SQLite) covering persistence, both drivers, subscriber, resolver,
  admin grid/detail, configuration and the purge command.

[0.1.0]: https://github.com/petyus/sylius-audit-logger/releases/tag/v0.1.0
