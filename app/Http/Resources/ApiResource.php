<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Base for every API Resource. Every bilingual DB field is stored as two
 * flat columns ({field}_ar / {field}_en); this composes them back into the
 * { "ar": ..., "en": ... } shape the React frontend's tr() helper already
 * expects, so no bilingual field is ever assembled by hand twice.
 */
abstract class ApiResource extends JsonResource
{
    protected function localized(string $field): array
    {
        return [
            'ar' => $this->resource->{$field.'_ar'},
            'en' => $this->resource->{$field.'_en'},
        ];
    }

    /**
     * Resolves a stored image/file path to a servable URL. Seed data uses
     * external demo photo URLs directly (passed through unchanged); real
     * uploads store a relative path on the "public" disk.
     */
    protected function fileUrl(?string $path): ?string
    {
        if ($path === null) {
            return null;
        }

        return str_starts_with($path, 'http') ? $path : Storage::disk('public')->url($path);
    }
}
