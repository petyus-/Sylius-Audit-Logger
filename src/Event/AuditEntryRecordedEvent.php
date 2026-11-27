<?php

declare(strict_types=1);

namespace Petyus\SyliusAuditLogger\Event;

use Petyus\SyliusAuditLogger\AuditEntryInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched after an audit entry has been recorded by the driver.
 *
 * Extension point for Pro features (alerts, exports, tamper-evident hashing)
 * without touching the core subscriber.
 */
final class AuditEntryRecordedEvent extends Event
{
    public function __construct(
        public readonly AuditEntryInterface $entry,
    ) {
    }
}
