<?php

namespace App\Enums;

enum MediaStatus: string
{
    case Uploading = 'uploading';
    case Ready = 'ready';
    case Failed = 'failed';
}
