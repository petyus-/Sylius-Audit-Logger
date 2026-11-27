<?php

declare(strict_types=1);

namespace Petyus\SyliusAuditLogger\EventSubscriber;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Petyus\SyliusAuditLogger\AuditEntryFactoryInterface;
use Petyus\SyliusAuditLogger\DriverInterface;
use Petyus\SyliusAuditLogger\Resolver\ActorResolver;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Petyus\SyliusAuditLogger\Event\AuditEntryRecordedEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Doctrine onFlush listener that captures audit changesets for configured resource classes.
 *
 * SAFETY NOTE: audit entries are collected during onFlush but only handed to the driver on
 * kernel.terminate. Recording mid-flush (especially with the Doctrine driver, which persists
 * and flushes) would mutate the very UnitOfWork that is currently flushing and cause
 * re-entrancy / "new entity found in changeset" errors. Deferring to kernel.terminate means
 * the original flush has fully completed before we ever touch the manager again.
 */
final class AuditSubscriber implements EventSubscriberInterface
{
    /** @var list<array{entity: object, action: string, changeset: array<string, array{0: mixed, 1: mixed}>}> */
    private array $pending = [];

    public function __construct(
        private readonly AuditEntryFactoryInterface $entryFactory,
        private readonly ActorResolver $actorResolver,
        private readonly DriverInterface $driver,
        /** @var list<class-string> */
        private readonly array $resources,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::TERMINATE => 'onKernelTerminate',
        ];
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $unitOfWork = $args->getObjectManager()->getUnitOfWork();

        foreach ($unitOfWork->getScheduledEntityInsertions() as $entity) {
            $this->collect($unitOfWork, $entity, 'create');
        }

        foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
            $this->collect($unitOfWork, $entity, 'update');
        }
    }

    public function onKernelTerminate(): void
    {
        $actorEmail = $this->actorResolver->resolveAdminUserEmail();
        $ip = $this->actorResolver->resolveClientIp();

        try {
            foreach ($this->pending as $pending) {
                $resourceId = (string) $pending['entity']->getId();

                if ('' === $resourceId) {
                    continue;
                }

                $entry = $this->entryFactory->createTyped(
                    $actorEmail,
                    $pending['action'],
                    $pending['entity']::class,
                    $resourceId,
                    $pending['changeset'],
                    $ip,
                );
                $this->driver->record($entry);
                $this->eventDispatcher->dispatch(new AuditEntryRecordedEvent($entry));
            }
        } finally {
            $this->pending = [];
        }
    }

    private function collect(UnitOfWork $unitOfWork, object $entity, string $action): void
    {
        if (!in_array($entity::class, $this->resources, true)) {
            return;
        }

        $changeset = array_filter(
            $unitOfWork->getEntityChangeSet($entity),
            static fn (string $field): bool => !in_array($field, ['createdAt', 'updatedAt'], true),
            ARRAY_FILTER_USE_KEY,
        );

        $this->pending[] = [
            'entity' => $entity,
            'action' => $action,
            'changeset' => array_map(
                /** @param array{0: mixed, 1: mixed} $pair */
                static fn (array $pair): array => [self::normalize($pair[0]), self::normalize($pair[1])],
                $changeset,
            ),
        ];
    }

    private static function normalize(mixed $value): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format(\DATE_ATOM);
        }

        if (is_object($value)) {
            return method_exists($value, 'getId') && null !== $value->getId()
                ? $value::class . '#' . $value->getId()
                : $value::class;
        }

        return $value;
    }
}
