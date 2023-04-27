<?php

namespace App\Enums;

enum PayoutStatus: string
{
    case SUCCESS = 'success';
    case PENDING = 'pending';
    case REVERSED = 'reversed';
    case FAILED = 'failed';
}
