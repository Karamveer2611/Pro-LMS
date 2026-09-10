<?php

namespace App\Http\Requests\Batches;

use App\Enums\BatchStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'status' => ['sometimes', Rule::enum(BatchStatus::class)],
            'access_days_override' => ['nullable', 'integer', 'min:1'],
            'capacity' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
