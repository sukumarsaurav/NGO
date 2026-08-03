<?php

declare(strict_types=1);

namespace App\Enums;

enum CancelledBy: string
{
    case Donor = 'donor';
    case Admin = 'admin';
    case Gateway = 'gateway';
    case Bank = 'bank';
}
