<?php

declare(strict_types=1);

namespace Petyus\SyliusAuditLogger;

use Sylius\Resource\Factory\FactoryInterface;

interface AuditEntryFactoryInterface extends FactoryInterface
{
    public function createTyped(
        ?string $adminUserEmail,
        string $action,
        string $resourceClass,
        string $resourceId,
        array $changeset,
        ?string $ip,
    ): AuditEntryInterface;
}
