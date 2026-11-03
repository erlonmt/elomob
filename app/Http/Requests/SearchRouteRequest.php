<?php

namespace App\Http\Requests;

use App\Enums\TransportModeCode;
use App\Models\Stop;
use App\Services\Routing\RoutePlanner;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SearchRouteRequest extends FormRequest
{
    /**
     * How many days ahead, after today, a trip may be planned.
     */
    public const int MAX_DAYS_AHEAD = 90;

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
     * Keep the departure between the start of today and the end of the last day ahead, both in Recife. It is read
     * as the route planner reads it (a departure without an offset is Recife time), which the date comparison
     * rules would read in UTC instead.
     *
     * @return list<Closure(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $departureAt = $this->input('departure_at');
            if (! is_string($departureAt) || $departureAt === '' || $validator->errors()->has('departure_at')) {
                return;
            }
            $departure = app(RoutePlanner::class)->departure($departureAt);
            $today = CarbonImmutable::today(RoutePlanner::TIMEZONE);
            if ($departure->lessThan($today)) {
                $validator->errors()->add('departure_at', 'Escolha uma saída a partir de hoje.');
            } elseif ($departure->greaterThan($today->addDays(self::MAX_DAYS_AHEAD)->endOfDay())) {
                $validator->errors()->add('departure_at', 'Escolha uma saída nos próximos '.self::MAX_DAYS_AHEAD.' dias.');
            }
        }];
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
