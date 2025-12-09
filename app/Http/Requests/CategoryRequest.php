<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Admin middleware guards access; return true here.
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('category')?->id ?? $this->route('id') ?? null;

        return [
            'name' => [
                'required',
                'string',
                'max:191',
                Rule::unique('categories', 'name')->ignore($id),
            ],
            'slug' => [
                'nullable',
                'string',
                'max:191',
                Rule::unique('categories', 'slug')->ignore($id),
            ],
        ];
    }

    protected function prepareForValidation()
    {
        if ($this->filled('name') && !$this->filled('slug')) {
            $slug = \Str::slug($this->input('name'));
            $this->merge(['slug' => $slug]);
        }
    }
}
