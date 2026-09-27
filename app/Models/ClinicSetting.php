<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'address_ar', 'address_en', 'phone', 'whatsapp', 'email',
    'working_hours_ar', 'working_hours_en', 'social_links',
    'default_og_image_path', 'map_lat', 'map_lng',
])]
class ClinicSetting extends Model
{
    protected function casts(): array
    {
        return [
            'social_links' => 'array',
            'map_lat' => 'decimal:7',
            'map_lng' => 'decimal:7',
        ];
    }

    /**
     * The clinic has exactly one settings row (id 1) — this is a singleton.
     */
    public static function current(): self
    {
        return static::firstOrFail();
    }
}
