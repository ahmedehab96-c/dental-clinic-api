<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\ApiResource;
use Illuminate\Http\Request;

/**
 * The admin-facing FAQ shape — adds `sort_order` and `is_published`, which
 * the public FaqResource deliberately never exposes since the public
 * endpoint already applies both (ordering and publish filtering) itself.
 */
class FaqResource extends ApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'question' => $this->localized('question'),
            'answer' => $this->localized('answer'),
            'sort_order' => $this->sort_order,
            'is_published' => $this->is_published,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
