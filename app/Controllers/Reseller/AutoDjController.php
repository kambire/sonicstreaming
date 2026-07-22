<?php

declare(strict_types=1);

namespace App\Controllers\Reseller;

use App\Controllers\BaseAutoDjController;
use App\Core\Auth;
use App\Models\Station;
use App\Models\User;

final class AutoDjController extends BaseAutoDjController
{
    protected string $base = 'reseller';

    protected function authorizeStation(int $id): ?array
    {
        $station = Station::findWithServer($id);
        if (!$station) {
            return null;
        }
        $owner = User::find((int) $station['user_id']);
        if (!$owner || (int) ($owner['reseller_id'] ?? 0) !== Auth::id()) {
            return null;
        }
        return $station;
    }
}
