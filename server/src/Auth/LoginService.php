<?php

declare(strict_types=1);

namespace Training\Auth;

use Training\Clock;

/**
 * Passwort-Login mit Kontosperre (D-33). Während einer Sperre wird das Passwort nicht geprüft und
 * kein Fehlversuch gezählt. Fehlversuche mit falschem Anmeldenamen zählen ebenfalls (Einzelnutzer).
 */
final class LoginService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly Clock $clock,
        private readonly LoginThrottle $throttle = new LoginThrottle(),
    ) {
    }

    /** Aktive Sperre des Kontos (Zeitpunkt) oder null. */
    public function lockedUntil(): ?int
    {
        $user = $this->users->first();
        if ($user === null || $user->lockedUntil === null || $user->lockedUntil <= $this->clock->now()) {
            return null;
        }

        return $user->lockedUntil;
    }

    public function attempt(string $login, string $password): LoginResult
    {
        $user = $this->users->first();
        if ($user === null) {
            return LoginResult::failed(LoginThrottle::MAX_FAILURES);
        }
        if ($user->lockedUntil !== null && $user->lockedUntil > $this->clock->now()) {
            return LoginResult::locked($user->lockedUntil);
        }

        $passwordOk = password_verify($password, $user->passwordHash);
        if ($passwordOk && hash_equals($user->login, $login)) {
            $this->users->resetFailures($user->id);
            if (Password::needsRehash($user->passwordHash)) {
                $this->users->updatePasswordHash($user->id, Password::hash($password));
            }

            return LoginResult::ok($user->id);
        }

        $failures = $this->users->recordFailure($user->id, $this->throttle);
        $lock = $this->throttle->lockSeconds($failures);
        if ($lock > 0) {
            return LoginResult::locked($this->clock->now() + $lock);
        }

        return LoginResult::failed($this->throttle->remaining($failures));
    }
}
