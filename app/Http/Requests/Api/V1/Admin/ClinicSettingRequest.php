<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ClinicSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The clinic settings row is a singleton that always exists — every
     * field is optional so an admin can patch just one setting at a time.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'address_ar' => ['sometimes', 'string', 'max:255'],
            'address_en' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:50'],
            'whatsapp' => ['sometimes', 'string', 'max:50'],
            'email' => ['sometimes', 'email', 'max:255'],
            'working_hours_ar' => ['sometimes', 'string', 'max:255'],
            'working_hours_en' => ['sometimes', 'string', 'max:255'],
            'social_links' => ['nullable', 'array'],
            'default_og_image_path' => ['nullable', 'string', 'max:2048'],
            'map_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'map_lng' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }
}
