<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\User;

/**
 * Autenticacion basada en sesion.
 */
final class Auth
{
    private const SESSION_KEY = 'auth_user_id';

    public static function attempt(string $email, string $password): bool
    {
        $user = User::findBy(['email' => $email]);
        if (!$user) {
            return false;
        }
        if ($user['status'] !== 'active') {
            return false;
        }
        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }
        session_regenerate_id(true);
        $_SESSION[self::SESSION_KEY] = (int) $user['id'];
        return true;
    }

    public static function login(int $userId): void
    {
        $_SESSION[self::SESSION_KEY] = $userId;
    }

    public static function check(): bool
    {
        return isset($_SESSION[self::SESSION_KEY]);
    }

    /** @return array<string,mixed>|null */
    public static function user(): ?array
    {
        static $cached = null;
        if (!self::check()) {
            return null;
        }
        if ($cached !== null && (int) $cached['id'] === (int) $_SESSION[self::SESSION_KEY]) {
            return $cached;
        }
        $cached = User::find((int) $_SESSION[self::SESSION_KEY]);
        return $cached;
    }

    public static function id(): ?int
    {
        return self::check() ? (int) $_SESSION[self::SESSION_KEY] : null;
    }

    public static function role(): ?string
    {
        $u = self::user();
        return $u['role'] ?? null;
    }

    public static function hasRole(string ...$roles): bool
    {
        return in_array(self::role(), $roles, true);
    }

    public static function logout(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
        session_regenerate_id(true);
    }
}
