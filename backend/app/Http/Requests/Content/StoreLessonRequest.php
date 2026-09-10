<?php

namespace App\Http\Requests\Content;

use App\Enums\LessonReleaseType;
use App\Enums\LessonType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(LessonType::class)],
            'content_body' => [
                Rule::requiredIf(in_array($this->input('type'), ['text', 'external'], true)),
                'nullable', 'string',
            ],
            'media_id' => [
                Rule::requiredIf(in_array($this->input('type'), ['video', 'document'], true)),
                'nullable', 'integer', 'exists:media_assets,id',
            ],
            'is_published' => ['sometimes', 'boolean'],
            'is_preview' => ['sometimes', 'boolean'],
            'is_required' => ['sometimes', 'boolean'],
            'release_type' => ['sometimes', Rule::enum(LessonReleaseType::class)],
            'release_days' => [
                Rule::requiredIf(in_array($this->input('release_type'), ['days_after_enrollment', 'days_after_batch_start'], true)),
                'nullable', 'integer', 'min:0',
            ],
            'release_date' => [
                Rule::requiredIf($this->input('release_type') === 'fixed_date'),
                'nullable', 'date',
            ],
        ];
    }
}
