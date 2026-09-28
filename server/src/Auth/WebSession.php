<?php

declare(strict_types=1);

namespace Training\Auth;

final class WebSession
{
    public function __construct(
        public readonly string $tokenHash,
        public readonly int $userId,
        public readonly string $login,
        public readonly string $tz,
        private readonly string $csrfSecret,
    ) {
    }

    /** CSRF-Token für Formulare dieser Session (D-33, 12.3a). */
    public function csrfToken(): string
    {
        return hash_hmac('sha256', 'csrf', $this->csrfSecret);
    }

    public function verifyCsrf(?string $token): bool
    {
        return is_string($token) && $token !== '' && hash_equals($this->csrfToken(), $token);
    }
}
