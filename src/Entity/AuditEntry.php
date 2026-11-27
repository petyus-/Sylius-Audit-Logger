<?php

declare(strict_types=1);

namespace Petyus\SyliusAuditLogger\Entity;

use Petyus\SyliusAuditLogger\AuditEntryInterface;

final class AuditEntry implements AuditEntryInterface
{
    private ?int $id = null;

    private ?string $adminUserEmail = null;

    private string $action = '';

    private string $resourceClass = '';

    private string $resourceId = '';

    /** @var array<string, array{0: mixed, 1: mixed}> */
    private array $changeset = [];

    private ?string $ip = null;

    private readonly \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAdminUserEmail(): ?string
    {
        return $this->adminUserEmail;
    }

    public function setAdminUserEmail(?string $adminUserEmail): void
    {
        $this->adminUserEmail = $adminUserEmail;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function setAction(string $action): void
    {
        $this->action = $action;
    }

    public function getResourceClass(): string
    {
        return $this->resourceClass;
    }

    public function setResourceClass(string $resourceClass): void
    {
        $this->resourceClass = $resourceClass;
    }

    public function getResourceId(): string
    {
        return $this->resourceId;
    }

    public function setResourceId(string $resourceId): void
    {
        $this->resourceId = $resourceId;
    }

    public function getChangeset(): array
    {
        return $this->changeset;
    }

    public function setChangeset(array $changeset): void
    {
        $this->changeset = $changeset;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(?string $ip): void
    {
        $this->ip = $ip;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
