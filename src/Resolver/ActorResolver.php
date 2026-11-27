<?php

declare(strict_types=1);

namespace Petyus\SyliusAuditLogger\Resolver;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class ActorResolver
{
    private const CONSOLE_ACTOR = 'console';

    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function resolveAdminUserEmail(): ?string
    {
        $token = $this->tokenStorage->getToken();

        if (null === $token) {
            return self::CONSOLE_ACTOR;
        }

        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return self::CONSOLE_ACTOR;
        }

        $email = $user->getUserIdentifier();

        return '' !== $email ? $email : self::CONSOLE_ACTOR;
    }

    public function resolveClientIp(): ?string
    {
        return $this->requestStack->getCurrentRequest()?->getClientIp();
    }
}
