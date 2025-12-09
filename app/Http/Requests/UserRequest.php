<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id ?? $this->route('id') ?? null;

        $rules = [
            'name' => ['required', 'string', 'max:191'],
            'email' => [
                'required',
                'email',
                'max:191',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'role' => ['required', 'in:admin,cashier,manager'],
        ];

        // password rules: required on create, optional on update
        if ($this->isMethod('post')) {
            $rules['password'] = ['required', 'string', 'min:6'];
        } elseif ($this->isMethod('put') || $this->isMethod('patch')) {
            $rules['password'] = ['sometimes', 'nullable', 'string', 'min:6'];
        }

        return $rules;
    }
}
