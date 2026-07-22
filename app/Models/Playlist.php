<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Playlist extends Model
{
    protected static string $table = 'playlists';

    /** @return array<int,array<string,mixed>> */
    public static function forStation(int $stationId): array
    {
        return self::where(['station_id' => $stationId], 'name ASC');
    }

    /**
     * Pistas de una playlist (con datos del track), ordenadas.
     * @return array<int,array<string,mixed>>
     */
    public static function items(int $playlistId): array
    {
        $sql = 'SELECT pi.id AS item_id, pi.position, t.*
                FROM playlist_items pi
                JOIN media_tracks t ON t.id = pi.track_id
                WHERE pi.playlist_id = ?
                ORDER BY pi.position ASC, pi.id ASC';
        $stmt = self::db()->prepare($sql);
        $stmt->execute([$playlistId]);
        return $stmt->fetchAll();
    }
}
