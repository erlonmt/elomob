<?php

namespace App\Http\Requests;

use App\Enums\AccessibilityNeed;
use App\Enums\RouteCriterion;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        // Formulário exclusivo de preferências de mobilidade.
        if ($this->boolean('preferences_form')) {
            return [
                'preferences_form' => ['required', 'accepted'],

                'preferred_route' => [
                    'required',
                    Rule::enum(RouteCriterion::class),
                ],

                'transport_modes' => ['nullable', 'array'],

                'transport_modes.*' => [
                    'string',
                    'distinct',
                    Rule::in([
                        'bus',
                        'metro',
                        'bike',
                        'taxi',
                        'app',
                        'walk',
                    ]),
                ],

                'accessibility_needs' => ['nullable', 'array'],

                'accessibility_needs.*' => [
                    'string',
                    'distinct',
                    Rule::in(AccessibilityNeed::values()),
                ],
            ];
        }

        // Formulário de informações pessoais.
        return [
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
        ];
    }
}
