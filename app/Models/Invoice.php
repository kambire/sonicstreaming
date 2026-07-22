<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Invoice extends Model
{
    protected static string $table = 'invoices';

    /** @return array<int,array<string,mixed>> */
    public static function allWithUser(): array
    {
        $sql = 'SELECT i.*, u.name AS user_name, u.email AS user_email
                FROM invoices i
                JOIN users u ON u.id = i.user_id
                ORDER BY i.due_date DESC, i.id DESC';
        return self::db()->query($sql)->fetchAll();
    }

    public static function forUser(int $userId): array
    {
        return self::where(['user_id' => $userId], 'due_date DESC');
    }

    /** Facturas vencidas aun sin pagar (para el cron de facturacion). */
    public static function overdueUnpaid(string $today): array
    {
        $stmt = self::db()->prepare(
            "SELECT * FROM invoices WHERE status IN ('pending','overdue') AND due_date < ?"
        );
        $stmt->execute([$today]);
        return $stmt->fetchAll();
    }
}
