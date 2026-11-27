<?php

declare(strict_types=1);

namespace Petyus\SyliusAuditLogger\Menu;

use Knp\Menu\ItemInterface;
use Sylius\Bundle\AdminBundle\Menu\Provider\MenuProviderInterface;

final readonly class AuditEntryMenuProvider implements MenuProviderInterface
{
    public function __invoke(ItemInterface $menu): void
    {
        $configuration = $menu->getChild('configuration');

        if (null === $configuration) {
            $configuration = $menu
                ->addChild('configuration')
                ->setLabel('sylius.menu.admin.main.configuration.header')
            ;
        }

        $configuration
            ->addChild('audit_logs', ['route' => 'petyus_audit_logger_admin_audit_entry_index'])
            ->setLabel('Audit logs')
        ;
    }
}
