<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * See docs/modules/M09-notices-communication.md's audience targeting table.
 * Resolved to `notice_recipients` rows at publish time, not read time — a
 * member who joins next week shouldn't retroactively receive last week's
 * notice.
 */
enum NoticeAudience: string implements HasLabel
{
    case AllMembers = 'all_members';
    case Department = 'department';
    case Designation = 'designation';
    case Specific = 'specific';
    case AllDonors = 'all_donors';

    public function label(): string
    {
        return match ($this) {
            self::AllMembers => 'All members',
            self::Department => 'By department',
            self::Designation => 'By designation',
            self::Specific => 'Specific users',
            self::AllDonors => 'All donors',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }
}
