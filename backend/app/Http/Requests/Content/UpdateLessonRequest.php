<?php

namespace App\Http\Requests\Content;

use App\Enums\LessonReleaseType;
use App\Enums\LessonType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $type = $this->input('type', $this->route('lesson')->type->value);
        $releaseType = $this->input('release_type', $this->route('lesson')->release_type->value);

        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'type' => ['sometimes', Rule::enum(LessonType::class)],
            'content_body' => [
                Rule::requiredIf(in_array($type, ['text', 'external'], true)),
                'nullable', 'string',
            ],
            'media_id' => [
                Rule::requiredIf(in_array($type, ['video', 'document'], true)),
                'nullable', 'integer', 'exists:media_assets,id',
            ],
            'is_published' => ['sometimes', 'boolean'],
            'is_preview' => ['sometimes', 'boolean'],
            'is_required' => ['sometimes', 'boolean'],
            'release_type' => ['sometimes', Rule::enum(LessonReleaseType::class)],
            'release_days' => [
                Rule::requiredIf(in_array($releaseType, ['days_after_enrollment', 'days_after_batch_start'], true)),
                'nullable', 'integer', 'min:0',
            ],
            'release_date' => [
                Rule::requiredIf($releaseType === 'fixed_date'),
                'nullable', 'date',
            ],
        ];
    }
}
