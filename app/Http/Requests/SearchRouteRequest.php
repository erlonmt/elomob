<?php

namespace App\Http\Requests;

use App\Enums\TransportModeCode;
use App\Models\Stop;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SearchRouteRequest extends FormRequest
{
    /**
     * Turn numeric origin and destination ids sent as strings into integers, so "different" compares them correctly.
     */
    protected function prepareForValidation(): void
    {
        foreach (['origin_id', 'destination_id'] as $field) {
            $value = $this->input($field);
            if ((is_int($value) || is_string($value)) && filter_var($value, FILTER_VALIDATE_INT) !== false) {
                $this->merge([$field => (int) $value]);
            }
        }
    }

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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'origin_id' => ['required', 'integer', Rule::exists(Stop::class, 'id')],
            'destination_id' => ['required', 'integer', 'different:origin_id', Rule::exists(Stop::class, 'id')],
            'modes' => ['required', 'array', 'min:1', 'max:'.count(TransportModeCode::selectable())],
            'modes.*' => ['required', 'distinct', Rule::enum(TransportModeCode::class)->only(TransportModeCode::selectable())],
            'departure_at' => ['sometimes', 'nullable', 'date'],
        ];
    }

    /**
     * Get the custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'destination_id.different' => 'Escolha um destino diferente da origem.',
            'modes.required' => 'Selecione ao menos um transporte.',
            'origin_id.required' => 'Selecione a origem.',
            'destination_id.required' => 'Selecione o destino.',
        ];
    }
}
