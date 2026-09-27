<?php

namespace App\Http\Requests\Api\V1\Doctor;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDoctorProfileRequest extends FormRequest
{
    /**
     * The route requires `role:doctor`, and the controller only ever
     * touches the caller's own linked profile.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Deliberately an allow-list of content fields. Anything else sent
     * (services, is_active, featured, slug, user_id, rating…) never reaches
     * validated() and so can't be mass-assigned.
     *
     * Multipart updates arrive as POST + `_method=PATCH` so a photo can be
     * attached — PHP never populates $_FILES for a raw PATCH body.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name_ar' => ['sometimes', 'required', 'string', 'max:255'],
            'name_en' => ['sometimes', 'required', 'string', 'max:255'],
            'specialty_ar' => ['sometimes', 'required', 'string', 'max:255'],
            'specialty_en' => ['sometimes', 'required', 'string', 'max:255'],
            'bio_ar' => ['sometimes', 'required', 'string'],
            'bio_en' => ['sometimes', 'required', 'string'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'regex:/^(\+?966|0)?5\d{8}$/'],
            'photo' => ['nullable', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ];
    }
}
