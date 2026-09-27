<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class FaqRequest extends FormRequest
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
        $sometimes = $this->isMethod('patch') ? 'sometimes' : 'required';

        return [
            'question_ar' => [$sometimes, 'string', 'max:255'],
            'question_en' => [$sometimes, 'string', 'max:255'],
            'answer_ar' => [$sometimes, 'string'],
            'answer_en' => [$sometimes, 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_published' => ['nullable', 'boolean'],
        ];
    }
}
