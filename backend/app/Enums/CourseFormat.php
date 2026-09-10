<?php

namespace App\Enums;

enum CourseFormat: string
{
    case SelfPaced = 'self_paced';
    case InstructorLed = 'instructor_led';
    case Hybrid = 'hybrid';
}
