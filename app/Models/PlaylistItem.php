<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class PlaylistItem extends Model
{
    protected static string $table = 'playlist_items';

    public static function nextPosition(int $playlistId): int
    {
        $stmt = self::db()->prepare('SELECT COALESCE(MAX(position),0)+1 FROM playlist_items WHERE playlist_id = ?');
        $stmt->execute([$playlistId]);
        return (int) $stmt->fetchColumn();
    }
}
