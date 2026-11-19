<?php

namespace App\Http\Requests\Settings;

use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    use ProfileValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->profileRules($this->user()->id),
            'locale' => [
                'string',
                'sometimes',
                'required',
                Rule::in([
                    'hr-HR',
                    'en-GB',
                ]),
            ],
            'timezone' => ['timezone', 'sometimes', 'required'],
        ];
    }
    protected function prepareForValidation(): void
    {
        $email = $this->input('email');
        if (is_string($email)) {
            $email = Str::lower($email);
            $this->merge(['email' => $email]);
        }
    }
}
