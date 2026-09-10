<?php

namespace App\Enums;

enum LessonReleaseType: string
{
    case Immediate = 'immediate';
    case DaysAfterEnrollment = 'days_after_enrollment';
    case FixedDate = 'fixed_date';
    case DaysAfterBatchStart = 'days_after_batch_start';
}
