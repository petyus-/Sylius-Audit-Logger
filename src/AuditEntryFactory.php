<?php

declare(strict_types=1);

namespace Petyus\SyliusAuditLogger;

use Petyus\SyliusAuditLogger\Entity\AuditEntry;

use Sylius\Resource\Factory\FactoryInterface;

final class AuditEntryFactory implements AuditEntryFactoryInterface
{
    public function createNew(): AuditEntryInterface
    {
        return new AuditEntry();
    }

    public function createTyped(
        ?string $adminUserEmail,
        string $action,
        string $resourceClass,
        string $resourceId,
        array $changeset,
        ?string $ip,
    ): AuditEntryInterface {
        $entry = $this->createNew();
        $entry->setAdminUserEmail($adminUserEmail);
        $entry->setAction($action);
        $entry->setResourceClass($resourceClass);
        $entry->setResourceId($resourceId);
        $entry->setChangeset($changeset);
        $entry->setIp($ip);

        return $entry;
    }
}
