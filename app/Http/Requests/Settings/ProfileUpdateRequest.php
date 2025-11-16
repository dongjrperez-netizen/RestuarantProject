<?php

namespace App\Http\Requests\Settings;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $employee = Auth::guard('employee')->user();
        $owner = Auth::guard('web')->user();

        // If an employee/manager is the active user (and no owner is logged in),
        // validate email uniqueness against the employees table.
        if ($employee && ! $owner) {
            return [
                'first_name' => ['required', 'string', 'max:255'],
                'last_name' => ['required', 'string', 'max:255'],
                'middle_name' => ['nullable', 'string', 'max:255'],
                'date_of_birth' => ['required', 'date'],
                'gender' => ['required', 'string', 'in:Male,Female,Other'],
                'email' => [
                    'required',
                    'string',
                    'lowercase',
                    'email',
                    'max:255',
                    Rule::unique(Employee::class, 'email')->ignore($employee->getKey(), $employee->getKeyName()),
                ],
            ];
        }

        $user = $owner ?: $this->user();

        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'date_of_birth' => ['required', 'date'],
            'gender' => ['required', 'string', 'in:Male,Female,Other'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user?->id),
            ],
        ];
    }
}
