<?php

declare(strict_types=1);

namespace Petyus\SyliusAuditLogger\File;

use Petyus\SyliusAuditLogger\AuditEntryInterface;
use Petyus\SyliusAuditLogger\DriverInterface;

final class FileAuditDriver implements DriverInterface
{
    public function __construct(private readonly string $path)
    {
    }

    public function record(AuditEntryInterface $entry): void
    {
        $this->ensureDirectory();

        $line = json_encode($this->serialize($entry), JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);

        if (false === $line) {
            throw new \RuntimeException(sprintf('Could not encode audit entry for resource "%s".', $entry->getResourceClass()));
        }

        if (false === file_put_contents($this->path, $line . "\n", FILE_APPEND | LOCK_EX)) {
            throw new \RuntimeException(sprintf('Could not append audit entry to "%s".', $this->path));
        }
    }

    private function ensureDirectory(): void
    {
        $directory = \dirname($this->path);

        if (!is_dir($directory) && !@mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new \RuntimeException(sprintf('Could not create audit log directory "%s".', $directory));
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(AuditEntryInterface $entry): array
    {
        return [
            'created_at' => $entry->getCreatedAt()->format(\DATE_ATOM),
            'admin_user_email' => $entry->getAdminUserEmail(),
            'action' => $entry->getAction(),
            'resource_class' => $entry->getResourceClass(),
            'resource_id' => $entry->getResourceId(),
            'changeset' => $entry->getChangeset(),
            'ip' => $entry->getIp(),
        ];
    }
}
