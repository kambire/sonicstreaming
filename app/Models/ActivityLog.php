<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Auth;
use App\Core\Model;

final class ActivityLog extends Model
{
    protected static string $table = 'activity_log';

    public static function record(string $action, string $details = ''): void
    {
        self::create([
            'user_id' => Auth::id(),
            'action'  => $action,
            'details' => mb_substr($details, 0, 255),
            'ip'      => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    }
}
