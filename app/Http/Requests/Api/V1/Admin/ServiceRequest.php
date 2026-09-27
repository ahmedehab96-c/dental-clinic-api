<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ServiceRequest extends FormRequest
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
        $service = $this->route('service');

        return [
            'slug' => [$sometimes, 'string', 'max:255', 'alpha_dash', Rule::unique('services', 'slug')->ignore($service)],
            'icon' => ['nullable', 'string', 'max:100'],
            'name_ar' => [$sometimes, 'string', 'max:255'],
            'name_en' => [$sometimes, 'string', 'max:255'],
            'short_description_ar' => [$sometimes, 'string', 'max:500'],
            'short_description_en' => [$sometimes, 'string', 'max:500'],
            'description_ar' => [$sometimes, 'string'],
            'description_en' => [$sometimes, 'string'],
            // Required on create (image_path is NOT NULL); optional on
            // update — omitting it just keeps the service's current image.
            'image' => [$this->isMethod('post') ? 'required' : 'nullable', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'duration_ar' => [$sometimes, 'string', 'max:100'],
            'duration_en' => [$sometimes, 'string', 'max:100'],
            'price_from' => [$sometimes, 'integer', 'min:0'],
            'features' => ['nullable', 'array'],
            'features.*.ar' => ['required_with:features', 'string'],
            'features.*.en' => ['required_with:features', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'doctor_ids' => ['nullable', 'array'],
            'doctor_ids.*' => ['integer', 'exists:doctors,id'],
        ];
    }
}
