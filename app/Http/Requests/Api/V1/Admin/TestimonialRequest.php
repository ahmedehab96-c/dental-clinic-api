<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class TestimonialRequest extends FormRequest
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

        return [
            'patient_name_ar' => [$sometimes, 'string', 'max:255'],
            'patient_name_en' => [$sometimes, 'string', 'max:255'],
            'role_ar' => [$sometimes, 'string', 'max:255'],
            'role_en' => [$sometimes, 'string', 'max:255'],
            // Required on create (photo_path is NOT NULL); optional on
            // update — omitting it just keeps the testimonial's current photo.
            'photo' => [$this->isMethod('post') ? 'required' : 'nullable', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'rating' => [$sometimes, 'integer', 'min:1', 'max:5'],
            'quote_ar' => [$sometimes, 'string'],
            'quote_en' => [$sometimes, 'string'],
            'is_published' => ['nullable', 'boolean'],
        ];
    }
}
