<?php

declare(strict_types=1);

namespace Petyus\SyliusAuditLogger\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Petyus\SyliusAuditLogger\Entity\AuditEntry;
use Petyus\SyliusAuditLogger\AuditEntryInterface;
use Petyus\SyliusAuditLogger\AuditEntryRepositoryInterface;

final class AuditEntryRepository implements AuditEntryRepositoryInterface
{
    private readonly ObjectRepository $repository;

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
        $this->repository = $entityManager->getRepository(AuditEntry::class);
    }

    public function add(AuditEntryInterface $entry): void
    {
        $this->entityManager->persist($entry);
        $this->entityManager->flush();
    }

    public function find($id): ?AuditEntryInterface
    {
        $entry = $this->repository->find($id);

        return $entry instanceof AuditEntryInterface ? $entry : null;
    }

    public function findByResource(string $resourceClass, string $resourceId): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('e')
            ->from(AuditEntry::class, 'e')
            ->where('e.resourceClass = :resourceClass')
            ->andWhere('e.resourceId = :resourceId')
            ->setParameter('resourceClass', $resourceClass)
            ->setParameter('resourceId', $resourceId)
            ->orderBy('e.createdAt', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function countOlderThan(\DateTimeImmutable $threshold): int
    {
        return (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(e.id)')
            ->from(AuditEntry::class, 'e')
            ->where('e.createdAt < :threshold')
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    public function deleteOlderThan(\DateTimeImmutable $threshold): int
    {
        return (int) $this->entityManager->createQueryBuilder()
            ->delete(AuditEntry::class, 'e')
            ->where('e.createdAt < :threshold')
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->execute()
        ;
    }
}
