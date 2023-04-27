<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case SUCCESSFUL = 'SUCCESSFUL';
    case PENDING = 'PENDING';
    case FAILED = 'FAILED';
}
