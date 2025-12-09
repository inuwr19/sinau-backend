<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BranchRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    } // route middleware handles admin
    public function rules()
    {
        $id = $this->route('branch')?->id ?? null;
        return [
            'name' => 'required|string|max:191|unique:branches,name,' . $id,
            'code' => 'nullable|string|max:50|unique:branches,code,' . $id,
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:30',
        ];
    }
}
