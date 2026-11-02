<?php

namespace App\Http\Requests;

use App\Models\SavedPlace;
use App\Models\Stop;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SavePlaceRequest extends FormRequest
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
            'label' => ['required', 'string', 'max:60', Rule::unique(SavedPlace::class)->where('user_id', $this->user()->id)],
            'stop_id' => ['required', 'integer', Rule::exists(Stop::class, 'id')],
        ];
    }
}
