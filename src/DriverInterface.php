<?php

declare(strict_types=1);

namespace Petyus\SyliusAuditLogger;

interface DriverInterface
{
    public function record(AuditEntryInterface $entry): void;
}
