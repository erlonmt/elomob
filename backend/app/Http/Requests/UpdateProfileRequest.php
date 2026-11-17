<?php

namespace App\Http\Requests;

use App\Enums\AccessibilityNeed;
use App\Enums\RouteCriterion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'preferred_route' => ['sometimes', Rule::enum(RouteCriterion::class)],
            'transport_modes' => ['sometimes', 'nullable', 'array'],
            'transport_modes.*' => ['string', 'distinct', 'exists:transport_modes,code'],
            'accessibility_needs' => ['sometimes', 'array'],
            'accessibility_needs.*' => ['string', 'distinct', Rule::in(AccessibilityNeed::values())],
        ];
    }
}
