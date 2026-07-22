<?php

declare(strict_types=1);

namespace App\Controllers\Reseller;

use App\Controllers\BaseAutoDjController;
use App\Core\Auth;
use App\Models\Station;

final class AutoDjController extends BaseAutoDjController
{
    protected string $base = 'reseller';

    protected function authorizeStation(int $id): ?array
    {
        $station = Station::findWithServer($id);
        if (!$station) {
            return null;
        }
        $user = Auth::user();
        if ($user && ($user['role'] === 'admin' || (int) $station['user_id'] === (int) $user['id'])) {
            return $station;
        }
        return null;
    }
}
