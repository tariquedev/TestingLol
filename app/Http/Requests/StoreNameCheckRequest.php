<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreNameCheckRequest extends FormRequest
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
            'store_name' => [
                'required',
                'string',
                'min:3',
                'regex:/^[a-z\-]+$/',
                function ($attribute, $value, $fail) {
                    // List of reserved names
                    $reservedNames = [
                        'blog', 'user', 'profile', 'flexpoint', 'support',
                        'about', 'terms', 'service', 'policy', 'gdpr',
                        'article', 'password'
                    ];

                    if (in_array($value, $reservedNames)) {
                        $fail("The $attribute '$value' is already taken.");
                    }
                }
            ],
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json(['errors' => $validator->errors()], 422)
        );
    }
}
