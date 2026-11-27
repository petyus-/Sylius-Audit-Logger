<?php

declare(strict_types=1);

namespace Petyus\SyliusAuditLogger;

interface AuditEntryRepositoryInterface
{
    public function add(AuditEntryInterface $entry): void;

    public function find($id): ?AuditEntryInterface;

    /**
     * @return list<AuditEntryInterface>
     */
    public function findByResource(string $resourceClass, string $resourceId): array;

    public function countOlderThan(\DateTimeImmutable $threshold): int;

    public function deleteOlderThan(\DateTimeImmutable $threshold): int;
}
