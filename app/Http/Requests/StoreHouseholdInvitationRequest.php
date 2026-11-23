<?php

namespace App\Http\Requests;

use App\Models\Household;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Str;

class StoreHouseholdInvitationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $household = $this->route('household');

        if (! $user instanceof User || ! $household instanceof Household) {
            return false;
        }

        return $user->householdMemberships()
            ->where('household_id', $household->id)
            ->where('role', 'owner')
            ->whereNull('deactivated_at')
            ->exists();
    }

    protected function prepareForValidation(): void
    {
        $email = $this->input('email');
        if (is_string($email)) {
            $this->merge([
                'email' => Str::lower(trim($email)),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => 'required|string|email',
        ];
    }
}
