<?php

declare(strict_types=1);

namespace App\Enums;

enum DonationType: string
{
    case OneTime = 'one_time';
    case Recurring = 'recurring';
}
