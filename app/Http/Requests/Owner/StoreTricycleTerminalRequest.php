<?php

namespace App\Http\Requests\owner;

use Illuminate\Foundation\Http\FormRequest;

class StoreTricycleTerminalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ownership' => ['required'],

            'name' => [
                'required',
                'string',
                'max:255',
                'unique:tricycle_terminals,name',
            ],

            'region' => ['required'],
            'province' => ['nullable'],
            'city' => ['required'],
            'barangay' => ['required'],
            'street' => ['required'],
            'postal_code' => ['required'],

            'latitude' => [
                'required',
                'numeric',
                'between:-90,90',
            ],

            'longitude' => [
                'required',
                'numeric',
                'between:-180,180',
            ],
        ];
    }
}
