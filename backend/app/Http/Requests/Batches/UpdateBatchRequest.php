<?php

namespace App\Http\Requests\Batches;

use App\Enums\BatchStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // A partial update might change only one of the two dates — compare
        // against whichever value (request input, else the existing record)
        // is in play, not just what's present in this particular payload.
        $batch = $this->route('batch');
        $effectiveStart = $this->input('start_date', $batch->start_date->toDateString());

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'start_date' => ['sometimes', 'required', 'date'],
            'end_date' => ['sometimes', 'required', 'date', 'after_or_equal:'.$effectiveStart],
            'status' => ['sometimes', Rule::enum(BatchStatus::class)],
            'access_days_override' => ['nullable', 'integer', 'min:1'],
            'capacity' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
