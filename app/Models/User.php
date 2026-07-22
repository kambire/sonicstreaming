<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class User extends Model
{
    protected static string $table = 'users';

    /** @return array<int,array<string,mixed>> */
    public static function clients(): array
    {
        return self::where(['role' => 'client'], 'name ASC');
    }

    /** @return array<int,array<string,mixed>> */
    public static function resellers(): array
    {
        return self::where(['role' => 'reseller'], 'name ASC');
    }

    /** Clientes que pertenecen a un reseller dado. */
    public static function clientsOfReseller(int $resellerId): array
    {
        return self::where(['role' => 'client', 'reseller_id' => $resellerId], 'name ASC');
    }

    public static function emailExists(string $email, int $ignoreId = 0): bool
    {
        $stmt = self::db()->prepare('SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1');
        $stmt->execute([$email, $ignoreId]);
        return (bool) $stmt->fetch();
    }
}
