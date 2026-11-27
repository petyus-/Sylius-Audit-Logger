<?php

declare(strict_types=1);

namespace Petyus\SyliusAuditLogger\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public const RESOURCES_DEFAULT = [
        'App\Entity\Product\Product',
        'App\Entity\Product\ProductVariant',
        'App\Entity\Order\Order',
        'App\Entity\Customer\Customer',
        'App\Entity\User\AdminUser',
    ];

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('petyus_audit_logger');

        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('resources')
                    ->defaultValue(self::RESOURCES_DEFAULT)
                    ->scalarPrototype()->cannotBeEmpty()->end()
                ->end()
                ->enumNode('driver')
                    ->values(['doctrine', 'file'])
                    ->defaultValue('doctrine')
                ->end()
                ->arrayNode('file')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('path')
                            ->defaultValue('%kernel.cache_dir%/audit-log.jsonl')
                            ->cannotBeEmpty()
                        ->end()
                    ->end()
                ->end()
                ->integerNode('retention_days')
                    ->defaultValue(0)
                    ->min(0)
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
