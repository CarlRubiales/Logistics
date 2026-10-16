<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'not_regex:/\d/'],
            'first_name' => ['sometimes', 'required', 'string', 'max:100', 'not_regex:/\d/'],
            'last_name' => ['sometimes', 'required', 'string', 'max:100', 'not_regex:/\d/'],
            'middle_initial' => ['sometimes', 'nullable', 'string', 'max:4', 'not_regex:/\d/'],
            'sex' => ['sometimes', 'required', Rule::in(['male', 'female'])],
            'contact_number' => ['sometimes', 'required', 'string', 'max:20'],
            'date_of_birth' => ['sometimes', 'required', 'date', 'before:today'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class, 'email')->ignore($this->user()->id),
            ],
            'province' => ['sometimes', 'required', 'string', 'max:255'],
            'municipality' => ['sometimes', 'required', 'string', 'max:255'],
            'barangay' => ['sometimes', 'required', 'string', 'max:255'],
            'street' => ['sometimes', 'nullable', 'string', 'max:255'],
            'house_number' => ['sometimes', 'nullable', 'string', 'max:100'],
            'shop_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'shop_description' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.not_regex' => "You can't input a number in your name.",
            'first_name.not_regex' => "You can't input a number in your name.",
            'last_name.not_regex' => "You can't input a number in your name.",
            'middle_initial.not_regex' => "You can't input a number in your name.",
        ];
    }
}
