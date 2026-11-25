<?php

namespace App\Http\Requests;

use App\Enums\AccessibilityNeed;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlanTripRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'origin_id' => ['required', 'integer', 'exists:stops,id'],
            'destination_id' => ['required', 'integer', 'different:origin_id', 'exists:stops,id'],
            'departure_at' => ['nullable', 'date'],
            'modes' => ['nullable', 'array'],
            'modes.*' => ['string', 'distinct', 'exists:transport_modes,code'],
            'accessibility_needs' => ['nullable', 'array'],
            'accessibility_needs.*' => ['string', 'distinct', Rule::in(AccessibilityNeed::values())],
        ];
    }

    public function attributes(): array
    {
        return [
            'origin_id' => 'origem',
            'destination_id' => 'destino',
            'departure_at' => 'horário de saída',
            'modes' => 'modais',
            'accessibility_needs' => 'necessidades de acessibilidade',
        ];
    }
}
