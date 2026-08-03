<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum NoticePriority: string implements HasColor, HasLabel
{
    case Normal = 'normal';
    case Important = 'important';
    case Urgent = 'urgent';

    public function label(): string
    {
        return match ($this) {
            self::Normal => 'Normal',
            self::Important => 'Important',
            self::Urgent => 'Urgent',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Normal => 'gray',
            self::Important => 'warning',
            self::Urgent => 'danger',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    public function getColor(): string
    {
        return $this->color();
    }
}
