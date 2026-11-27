<?php

declare(strict_types=1);

namespace Petyus\SyliusAuditLogger;

interface AuditEntryInterface
{
    public function getId(): ?int;

    public function getAdminUserEmail(): ?string;

    public function setAdminUserEmail(?string $adminUserEmail): void;

    public function getAction(): string;

    public function setAction(string $action): void;

    public function getResourceClass(): string;

    public function setResourceClass(string $resourceClass): void;

    public function getResourceId(): string;

    public function setResourceId(string $resourceId): void;

    /**
     * @return array<string, array{0: mixed, 1: mixed}>
     */
    public function getChangeset(): array;

    /**
     * @param array<string, array{0: mixed, 1: mixed}> $changeset
     */
    public function setChangeset(array $changeset): void;

    public function getIp(): ?string;

    public function setIp(?string $ip): void;

    public function getCreatedAt(): \DateTimeImmutable;
}
