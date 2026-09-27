<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BlogPostRequest extends FormRequest
{
    /**
     * Route already requires the `role:admin` middleware — reaching this
     * request means the caller is already an authenticated admin.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // Updates arrive as POST + `_method=PATCH` (Laravel's method-spoofing
        // convention) so the browser can send multipart/form-data with a
        // file — PHP never populates $_FILES for a raw PUT/PATCH body.
        // $this->isMethod() already resolves the spoofed method correctly.
        $sometimes = $this->isMethod('patch') ? 'sometimes' : 'required';
        $post = $this->route('post');

        return [
            'slug' => [$sometimes, 'string', 'max:255', 'alpha_dash', Rule::unique('blog_posts', 'slug')->ignore($post)],
            'category_id' => [$sometimes, 'integer', 'exists:blog_categories,id'],
            'title_ar' => [$sometimes, 'string', 'max:255'],
            'title_en' => [$sometimes, 'string', 'max:255'],
            'excerpt_ar' => [$sometimes, 'string', 'max:500'],
            'excerpt_en' => [$sometimes, 'string', 'max:500'],
            'content_ar' => [$sometimes, 'array', 'min:1'],
            'content_ar.*' => ['string'],
            'content_en' => [$sometimes, 'array', 'min:1'],
            'content_en.*' => ['string'],
            // Required on create (image_path is NOT NULL); optional on
            // update — omitting it just keeps the post's current image.
            'image' => [$this->isMethod('post') ? 'required' : 'nullable', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'status' => [$sometimes, Rule::in(['draft', 'published'])],
            'published_at' => ['nullable', 'date'],
            'read_minutes' => ['nullable', 'integer', 'min:1', 'max:120'],
        ];
    }
}
