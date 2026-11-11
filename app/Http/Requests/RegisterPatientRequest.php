<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterPatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_name' => ['required', 'string', 'max:100'],
            'patient_age' => ['required', 'integer', 'min:0', 'max:130'],
            'patient_address' => ['required', 'string', 'max:255'],
            'complaint' => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'integer' => ':attribute harus berupa angka.',
            'max' => ':attribute terlalu panjang atau terlalu besar.',
            'min' => ':attribute tidak valid.',
        ];
    }

    public function attributes(): array
    {
        return [
            'patient_name' => 'Nama pasien',
            'patient_age' => 'Usia',
            'patient_address' => 'Alamat',
            'complaint' => 'Keterangan sakit',
        ];
    }
}
