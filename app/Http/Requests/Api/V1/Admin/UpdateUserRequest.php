<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
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
        $user = $this->route('user');

        return [
            'name' => ['sometimes', 'string', 'min:2', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'phone' => ['sometimes', 'nullable', 'regex:/^(\+?966|0)?5\d{8}$/'],
            'role' => ['sometimes', Rule::in(['patient', 'doctor', 'admin'])],
            'password' => ['sometimes', 'string', Password::min(8), 'confirmed'],
        ];
    }
}
