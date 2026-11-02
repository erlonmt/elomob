<?php

namespace App\Http\Requests;

use App\Enums\TransportModeCode;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CatalogRequest extends FormRequest
{
    /**
     * Accept the lat, lng and radius_meters aliases for latitude, longitude and radius.
     */
    protected function prepareForValidation(): void
    {
        foreach (['lat' => 'latitude', 'lng' => 'longitude', 'radius_meters' => 'radius'] as $alias => $field) {
            if ($this->has($alias) && ! $this->has($field)) {
                $this->merge([$field => $this->input($alias)]);
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
            'q' => ['sometimes', 'nullable', 'string', 'max:120'],
            'mode' => ['sometimes', 'nullable', Rule::enum(TransportModeCode::class)],
            'latitude' => ['required_with:longitude', 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['required_with:latitude', 'nullable', 'numeric', 'between:-180,180'],
            'radius' => ['sometimes', 'integer', 'between:100,20000'],
        ];
    }
}
