<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterDoctorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'              => ['required', 'string', 'max:255'],
            'email'             => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password'          => ['required', 'string', 'min:8', 'confirmed'],
            'phone'             => ['nullable', 'string', 'max:20'],
            'specialty_id'      => ['required', 'exists:specialties,id'],
            'license_number'    => ['required', 'string', 'unique:doctors,license_number'],
            'license_document'  => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'bio'               => ['nullable', 'string', 'max:1000'],
            'education'         => ['nullable', 'string', 'max:500'],
            'experience_years'  => ['nullable', 'integer', 'min:0', 'max:60'],
            'consultation_price'=> ['required', 'numeric', 'min:0'],
            'consultation_types'=> ['required', 'array', 'min:1'],
            'consultation_types.*' => ['in:presencial,videollamada,chat'],
            'city'              => ['nullable', 'string', 'max:100'],
            'state'             => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'               => 'El nombre es obligatorio.',
            'email.required'              => 'El email es obligatorio.',
            'email.unique'                => 'Este email ya está registrado.',
            'password.required'           => 'La contraseña es obligatoria.',
            'password.min'                => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed'          => 'Las contraseñas no coinciden.',
            'specialty_id.required'       => 'La especialidad es obligatoria.',
            'specialty_id.exists'         => 'La especialidad seleccionada no existe.',
            'license_number.required'     => 'La cédula profesional es obligatoria.',
            'license_number.unique'       => 'Esta cédula profesional ya está registrada.',
            'consultation_price.required' => 'El precio de consulta es obligatorio.',
            'consultation_types.required' => 'Debes seleccionar al menos un tipo de consulta.',
        ];
    }
}
