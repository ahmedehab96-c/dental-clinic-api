<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class GalleryCaseRequest extends FormRequest
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
        $imageRule = [$this->isMethod('post') ? 'required' : 'nullable', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'];

        return [
            'title_ar' => [$sometimes, 'string', 'max:255'],
            'title_en' => [$sometimes, 'string', 'max:255'],
            'service_id' => [$sometimes, 'integer', 'exists:services,id'],
            // Required on create (the *_path columns are NOT NULL); optional
            // on update — omitting one just keeps that side's current image.
            'before_image' => $imageRule,
            'after_image' => $imageRule,
            'is_published' => ['nullable', 'boolean'],
        ];
    }
}
