<?php

declare(strict_types=1);

namespace Training\OAuth;

/** Fehler nach RFC 6749 (error, error_description). */
final class OAuthException extends \RuntimeException
{
    public function __construct(public readonly string $error, string $description, public readonly int $status = 400)
    {
        parent::__construct($description);
    }
}
