<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DoctorRequest extends FormRequest
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
        $doctor = $this->route('doctor');

        return [
            'user_id' => [
                'nullable', 'integer',
                Rule::exists('users', 'id')->where('role', 'doctor'),
                Rule::unique('doctors', 'user_id')->ignore($doctor),
            ],
            'name_ar' => [$sometimes, 'string', 'max:255'],
            'name_en' => [$sometimes, 'string', 'max:255'],
            'specialty_ar' => [$sometimes, 'string', 'max:255'],
            'specialty_en' => [$sometimes, 'string', 'max:255'],
            'bio_ar' => [$sometimes, 'string'],
            'bio_en' => [$sometimes, 'string'],
            'email' => [$sometimes, 'email', 'max:255'],
            'phone' => [$sometimes, 'regex:/^(\+?966|0)?5\d{8}$/'],
            // Required on create (photo_path is NOT NULL); optional on
            // update — omitting it just keeps the doctor's current photo.
            'photo' => [$this->isMethod('post') ? 'required' : 'nullable', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:80'],
            'rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'reviews_count' => ['nullable', 'integer', 'min:0'],
            'education' => ['nullable', 'array'],
            'education.*.ar' => ['required_with:education', 'string'],
            'education.*.en' => ['required_with:education', 'string'],
            'featured' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'service_ids' => ['nullable', 'array'],
            'service_ids.*' => ['integer', 'exists:services,id'],
        ];
    }
}
