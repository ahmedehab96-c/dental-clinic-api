<?php

namespace App\Models\Concerns;

/**
 * Route-model-binds on `slug` instead of `id` — every public detail page
 * (doctors, services, blog posts) is already addressed by slug in the URL.
 */
trait HasSlug
{
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
