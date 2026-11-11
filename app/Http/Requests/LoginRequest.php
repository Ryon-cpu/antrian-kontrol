<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string', 'max:200'],
        ];
    }

    public function messages(): array
    {
        return ['required' => ':attribute wajib diisi.'];
    }

    public function attributes(): array
    {
        return ['username' => 'Username', 'password' => 'Password'];
    }
}
