<?php

namespace App\Http\Requests\Sessions;

use App\Enums\SessionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLiveSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'instructor_id' => [
                'sometimes', 'required', 'integer',
                Rule::exists('users', 'id')->where('role', 'instructor'),
            ],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'scheduled_at' => ['sometimes', 'required', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'meeting_provider' => ['nullable', 'string', 'max:50'],
            'meeting_link' => ['nullable', 'url', 'max:500'],
            'status' => ['sometimes', Rule::enum(SessionStatus::class)],
            'recording_url' => ['nullable', 'url', 'max:500'],
        ];
    }
}
