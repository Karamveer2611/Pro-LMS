<?php

namespace App\Http\Requests\Attendance;

use App\Enums\AttendanceStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $session = $this->route('session');

        return [
            'records' => ['required', 'array', 'min:1'],
            'records.*.user_id' => [
                'required', 'integer',
                // Only learners actively enrolled in this session's batch
                // may have attendance marked — see BatchManagementTest-style
                // ownership rules; this is the attendance equivalent.
                Rule::exists('enrollments', 'user_id')
                    ->where('batch_id', $session->batch_id)
                    ->where('status', 'active'),
            ],
            'records.*.status' => ['required', Rule::enum(AttendanceStatus::class)],
            'records.*.notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'records.*.user_id.exists' => 'This learner is not actively enrolled in this session\'s batch.',
        ];
    }
}
