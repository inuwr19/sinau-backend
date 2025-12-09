<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Admin middleware handles permission, but keep true for request-level
        return true;
    }

    public function rules(): array
    {
        $menuItemId = $this->route('menu_item')?->id ?? $this->route('id') ?? null;

        $rules = [
            'name' => 'required|string|max:191',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'category_id' => 'nullable|exists:categories,id',
            'is_available' => 'sometimes|boolean',
            'image' => 'sometimes|file|image|max:5120', // max 5MB
        ];

        if ($this->isMethod('post')) {
            // create
            $rules['name'] .= '|unique:menu_items,name';
        } else {
            // update
            if ($menuItemId) {
                $rules['name'] .= '|unique:menu_items,name,' . $menuItemId;
            }
        }

        return $rules;
    }


    public function prepareForValidation()
    {
        // Normalize boolean
        if ($this->has('is_available')) {
            $this->merge(['is_available' => filter_var($this->input('is_available'), FILTER_VALIDATE_BOOLEAN)]);
        }
    }
}
