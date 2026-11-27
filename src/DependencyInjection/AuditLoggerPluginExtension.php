<?php

declare(strict_types=1);

namespace Petyus\SyliusAuditLogger\DependencyInjection;

use Petyus\SyliusAuditLogger\Doctrine\DoctrineAuditDriver;
use Petyus\SyliusAuditLogger\DriverInterface;
use Petyus\SyliusAuditLogger\File\FileAuditDriver;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Yaml\Yaml;

final class AuditLoggerPluginExtension extends Extension implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container): void
    {
        if (!$container->hasExtension('sylius_grid')) {
            return;
        }

        $gridFile = __DIR__ . '/../../Resources/config/grids/audit_entry.yaml';
        $config = Yaml::parseFile($gridFile);

        if (!isset($config['sylius_grid']['grids'])) {
            return;
        }

        $container->prependExtensionConfig('sylius_grid', $config['sylius_grid']);
        $container->addResource(new FileResource($gridFile));
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $container->setParameter('petyus_audit_logger.resources', $config['resources']);
        $container->setParameter('petyus_audit_logger.driver', $config['driver']);
        $container->setParameter('petyus_audit_logger.file.path', $config['file']['path']);
        $container->setParameter('petyus_audit_logger.retention_days', $config['retention_days']);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../../Resources/config'));
        $loader->load('services.yaml');

        $driverClass = 'file' === $config['driver'] ? FileAuditDriver::class : DoctrineAuditDriver::class;
        $container->setAlias(DriverInterface::class, $driverClass)->setPublic(true);
    }
}
