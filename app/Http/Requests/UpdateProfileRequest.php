<?php

namespace App\Http\Requests;

use App\Enums\AccessibilityNeed;
use App\Enums\RouteCriterion;
use App\Enums\TransportModeCode;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique(User::class)->ignore($this->user())],
            'preferred_route' => ['required', Rule::enum(RouteCriterion::class)],
            'transport_modes' => ['required', 'list', 'min:1', 'max:'.count(TransportModeCode::selectable())],
            'transport_modes.*' => ['distinct', Rule::enum(TransportModeCode::class)->only(TransportModeCode::selectable())],
            'accessibility_needs' => ['sometimes', 'list', 'max:'.count(AccessibilityNeed::cases())],
            'accessibility_needs.*' => ['distinct', Rule::enum(AccessibilityNeed::class)],
        ];
    }

    /**
     * Get the validated profile; accessibility needs left out are turned off.
     *
     * @return array<string, mixed>
     */
    public function profile(): array
    {
        return $this->validated() + ['accessibility_needs' => []];
    }
}
