<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('member')?->id ?? $this->route('id') ?? null;

        return [
            'name' => ['required', 'string', 'max:191'],
            'phone' => [
                'required',
                'string',
                'max:30',
                Rule::unique('members', 'phone')->ignore($id),
            ],
            'email' => ['nullable', 'email', 'max:191'],
            'points' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
