<?php

declare(strict_types=1);

namespace Petyus\SyliusAuditLogger;

use Symfony\Component\HttpKernel\Bundle\Bundle;

final class AuditLoggerPlugin extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
