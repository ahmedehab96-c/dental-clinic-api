<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
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
     * Mirrors RegisterRequest's field rules, plus an admin-chosen role
     * restricted to the roles the users.role enum actually allows.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'regex:/^(\+?966|0)?5\d{8}$/'],
            'role' => ['required', Rule::in(['patient', 'doctor', 'admin'])],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
