<?php

namespace App\Enums;

/**
 * Only "manual" is active in V1 (no payment gateway — see
 * docs/phase0-architecture.md §6). A future payments module adds a
 * "payment" case here and nothing else changes in EnrollmentAccessService.
 */
enum EnrollmentSource: string
{
    case Manual = 'manual';
}
