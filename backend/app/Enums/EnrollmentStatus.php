<?php

namespace App\Enums;

enum EnrollmentStatus: string
{
    case Active = 'active';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
    case Completed = 'completed';
}
