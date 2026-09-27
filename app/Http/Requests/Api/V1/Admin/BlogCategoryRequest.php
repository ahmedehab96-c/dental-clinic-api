<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BlogCategoryRequest extends FormRequest
{
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
        $category = $this->route('blog_category');

        return [
            'key' => [$sometimes, 'string', 'max:100', 'alpha_dash', Rule::unique('blog_categories', 'key')->ignore($category)],
            'label_ar' => [$sometimes, 'string', 'max:255'],
            'label_en' => [$sometimes, 'string', 'max:255'],
        ];
    }
}
