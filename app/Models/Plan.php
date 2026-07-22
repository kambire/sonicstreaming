<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Plan extends Model
{
    protected static string $table = 'plans';

    /** Planes globales (sin reseller). */
    public static function global(): array
    {
        return self::where(['reseller_id' => null], 'name ASC');
    }
}
