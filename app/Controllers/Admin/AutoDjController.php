<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseAutoDjController;
use App\Models\Station;

final class AutoDjController extends BaseAutoDjController
{
    protected string $base = 'admin';

    protected function authorizeStation(int $id): ?array
    {
        return Station::findWithServer($id);
    }
}
