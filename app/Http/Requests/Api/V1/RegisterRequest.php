<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            // Matches the same Saudi mobile format the React app already validates client-side.
            'phone' => ['required', 'regex:/^(\+?966|0)?5\d{8}$/'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
