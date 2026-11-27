# Petyus Sylius Audit Logger

A [Sylius 2](https://sylius.com) plugin that records who changed what, when — for every whitelisted
resource — and stores the entries either in Doctrine or in a JSONL file. Ships with a read-only
admin grid, a per-entry changeset view, and a retention purge command for GDPR-friendly log hygiene.

- **Requires:** PHP >= 8.2, Sylius ^2.0, Symfony ^6.4 | ^7.0
- **License:** [MIT](LICENSE)

## Features

- **Automatic change capture** — an `onFlush` Doctrine listener snapshots the changeset of every
  whitelisted resource and persists the audit entry on `kernel.terminate`, so the write never
  blocks the admin response.
- **Actor resolution** — records the admin user's email (from the security token) and the client IP.
- **Two storage drivers**:
  - `doctrine` (default) — audit entries are entities, queryable through the admin grid;
  - `file` — entries are appended as JSON Lines to a configurable path.
- **Admin UI** — read-only grid at `/admin/audit-logs/` with filters (email, action, resource,
  date) and a detail page showing the full changeset, plus a *Audit Logs* menu item in the
  Sylius admin panel.
- **Retention purging** — `petyus:audit-logger:purge` command with dry-run support, ready for cron.
- **Custom drivers** — swap in your own storage backend by implementing `DriverInterface`.

## Installation

### 1. Require the package

```bash
composer require petyus/sylius-audit-logger
```

### 2. Register the bundle

`config/bundles.php`:

```php
return [
    // ...
    Petyus\SyliusAuditLogger\AuditLoggerPlugin::class => ['all' => true],
];
```

### 3. Import the admin routes

`config/routes/petyus_audit_logger.yaml`:

```yaml
petyus_audit_logger_admin:
    resource: '@AuditLoggerPlugin/Controller/'
    type: attribute
```

### 4. Configure

Create `config/packages/petyus_audit_logger.yaml` (see the [configuration reference](#configuration-reference)
below) — or skip this step to run entirely on defaults.

### 5. Doctrine migration

The plugin registers the `petyus_audit_entry` table. Create and run a migration:

```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

## Configuration reference

All keys are optional. Defaults shown:

```yaml
petyus_audit_logger:
    # Entity classes whose changes are audited.
    resources:
        - 'App\Entity\Product\Product'
        - 'App\Entity\Product\ProductVariant'
        - 'App\Entity\Order\Order'
        - 'App\Entity\Customer\Customer'
        - 'App\Entity\User\AdminUser'

    # Storage backend: "doctrine" or "file".
    driver: doctrine

    file:
        # JSONL target when driver is "file".
        path: '%kernel.cache_dir%/audit-log.jsonl'

    # Purge entries older than this many days. 0 disables purging.
    retention_days: 0
```

| Key | Type | Default | Description |
| --- | --- | --- | --- |
| `resources` | list of class strings | core App entities (see above) | Only changes to these classes are audited. |
| `driver` | `doctrine` \| `file` | `doctrine` | Where audit entries are stored. |
| `file.path` | string | `%kernel.cache_dir%/audit-log.jsonl` | Target file for the `file` driver (one JSON object per line). Set e.g. `%kernel.project_dir%/var/log/audit.jsonl` for a persistent location. |
| `retention_days` | integer ≥ 0 | `0` | Age threshold for the purge command. `0` = purging disabled. |

### Driver examples

Doctrine (default) — entries visible in the admin grid:

```yaml
petyus_audit_logger:
    driver: doctrine
```

File — entries appended to a JSONL file outside the database:

```yaml
petyus_audit_logger:
    driver: file
    file:
        path: '%kernel.project_dir%/var/log/audit-log.jsonl'
```

## The purge command

```bash
php bin/console petyus:audit-logger:purge
```

Deletes audit entries older than `retention_days` in a single bulk `DELETE` query. When
`retention_days` is `0` (the default), purging is disabled: the command prints a notice and exits
successfully without deleting anything.

Options:

| Option | Description |
| --- | --- |
| `--days=N` | Override the retention period for this run only. |
| `--dry-run` | Report how many entries would be deleted; delete nothing. |

```bash
# Preview
php bin/console petyus:audit-logger:purge --days=90 --dry-run
# Would delete 1234 audit entries older than 90 days (dry-run).

# Purge
php bin/console petyus:audit-logger:purge --days=90
```

### Cron example

Purge weekly, Sunday 03:00:

```
0 3 * * 0 php /path/to/project/bin/console petyus:audit-logger:purge --no-interaction -v
```

Enable it by configuring `retention_days` (e.g. `365`) or always pass `--days`.

## Writing a custom driver

Any storage backend can be plugged in by implementing the driver contract:

```php
<?php

declare(strict_types=1);

namespace App\Audit;

use Petyus\SyliusAuditLogger\AuditEntryInterface;
use Petyus\SyliusAuditLogger\DriverInterface;

final class ElasticsearchAuditDriver implements DriverInterface
{
    public function record(AuditEntryInterface $entry): void
    {
        // index $entry->getAction(), ->getResourceClass(), ->getResourceId(),
        // ->getChangeset(), ->getAdminUserEmail(), ->getIp(), ->getCreatedAt()
    }
}
```

Register it and point the driver alias at your implementation:

```yaml
# config/services.yaml
services:
    App\Audit\ElasticsearchAuditDriver: ~

    Petyus\SyliusAuditLogger\DriverInterface:
        alias: App\Audit\ElasticsearchAuditDriver
        public: true
```

The `driver` config key becomes irrelevant; your alias wins as long as it is registered after the
plugin's own alias (or you redefine it in `config/services.yaml`, which loads later).

## Extension points (used by Pro)

Two stable hooks let you extend the plugin without patching its internals.

**1. `DriverInterface`** — swap the storage backend (Doctrine, JSONL, or your own Elasticsearch/S3
driver). See [Writing a custom driver](#writing-a-custom-driver).

**2. `AuditEntryRecordedEvent`** — dispatched after every recorded entry:

```php
use Petyus\SyliusAuditLogger\Event\AuditEntryRecordedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
final class SlackAlertListener
{
    public function __construct(private readonly \Psr\Log\LoggerInterface $logger) {}

    public function __invoke(AuditEntryRecordedEvent $event): void
    {
        if ('update' === $event->entry->getAction()) {
            $this->logger->warning('Admin changed a resource', [
                'actor' => $event->entry->getAdminUserEmail(),
                'resource' => $event->entry->getResourceClass(),
            ]);
        }
    }
}
```

Pro features (tamper-evident hashing, SIEM/S3/Webhook export, auditor PDF/CSV reports,
alerting on sensitive actions, per-resource retention policies) are built on top of these two
hooks, so they never require core changes.

## GDPR and personal data

Audit logs inherently contain **personal data**: the admin user's email address and the client IP
address of the actor are recorded with every entry. Under GDPR this means:

- you act as a data controller for your audit log contents;
- you must define and honour a **retention period** — do not keep audit entries indefinitely;
- entries should be deleted when the retention period expires.

The plugin gives you the tooling: set `retention_days` to your policy (for example 180 or 365) and
schedule the [purge command](#the-purge-command) via cron. Use `--dry-run` to verify the impact
before the first real run. If you also need to export or erase records on request, implement a
custom driver targeting your DSR tooling.

## Testing

The plugin is covered by a functional kernel test suite (SQLite, Symfony `KernelTestCase`).

## License

This plugin is released under the [MIT license](LICENSE).
