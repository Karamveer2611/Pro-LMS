<?php

namespace App\Http\Requests\Enrollments;

use App\Models\Enrollment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Enrollment::class);
    }

    public function rules(): array
    {
        return [
            'user_id' => [
                'required', 'integer',
                Rule::exists('users', 'id')->where('role', 'learner'),
            ],
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'batch_id' => [
                'nullable', 'integer',
                Rule::exists('batches', 'id')->where('course_id', $this->input('course_id')),
            ],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.exists' => 'This user does not exist or is not a learner.',
            'batch_id.exists' => 'This batch does not belong to the selected course.',
        ];
    }
}
