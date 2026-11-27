<?php

declare(strict_types=1);

namespace Petyus\SyliusAuditLogger\Doctrine;

use Petyus\SyliusAuditLogger\AuditEntryInterface;
use Petyus\SyliusAuditLogger\AuditEntryRepositoryInterface;
use Petyus\SyliusAuditLogger\DriverInterface;

final class DoctrineAuditDriver implements DriverInterface
{
    public function __construct(private readonly AuditEntryRepositoryInterface $repository)
    {
    }

    public function record(AuditEntryInterface $entry): void
    {
        $this->repository->add($entry);
    }
}
