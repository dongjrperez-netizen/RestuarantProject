<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'age' => 'required|integer',
            'gender' => 'required|string|max:255',
            'address' => 'required|string|max:255|unique:users,address',
            'email' => 'required|string|lowercase|email|max:255|unique:users,email',
            'phonenumber' => 'required|string|max:20|unique:users,phonenumber',
            'password' => ['required', 'confirmed', Password::defaults()],
            'restaurant_name' => 'required|string|max:255',
            'restaurant_address' => 'required|string|max:255',
            'contact_number' => 'required|string|max:20',
        ];
    }
}
