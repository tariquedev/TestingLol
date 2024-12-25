<?php

namespace App\Http\Requests;

use App\Models\Country;
use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;


class StoreUserRequest extends FormRequest
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
        $user = User::where('email', request()->email)->first();

        $emailRules = ['required', 'email'];

        if (!$user || $user->password !== null) {
            $emailRules[] = Rule::unique('users');
        }

        return [
            'name'       => 'required',
            'email'      => $emailRules,
            'password'   => [
                'required',
                Password::min(8)->numbers(),  // Minimum 8 characters and at least one number
                'regex:/[A-Z]/'               // At least one uppercase letter
            ],
            'country'    => 'in:' . implode(',', Country::getCodes()),
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status' => 'error',
            'message' => 'Validation Failed',
            'errors' => $validator->errors()
        ], 422)
        );
    }

}
