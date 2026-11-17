<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSavedPlaceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'stop_id' => ['required', 'integer', 'exists:stops,id'],
            'label' => [
                'required', 'string', 'max:60',
                Rule::unique('saved_places', 'label')->where('user_id', $this->user()->id),
            ],
        ];
    }
}
