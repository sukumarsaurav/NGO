<?php

declare(strict_types=1);

namespace App\Enums;

enum DocumentType: string
{
    case IdCard = 'id_card';
    case AppointmentLetter = 'appointment_letter';
    case Certificate = 'certificate';

    public function label(): string
    {
        return match ($this) {
            self::IdCard => 'ID card',
            self::AppointmentLetter => 'Appointment letter',
            self::Certificate => 'Certificate',
        };
    }

    /** Prefix used by DocumentNumberGenerator, e.g. `AL-2026-0042`. */
    public function numberPrefix(): string
    {
        return match ($this) {
            self::IdCard => 'IC',
            self::AppointmentLetter => 'AL',
            self::Certificate => 'CT',
        };
    }

    /** CR80 (85.6 x 54 mm) for ID cards, A4 for everything else. */
    public function defaultPageSize(): string
    {
        return match ($this) {
            self::IdCard => 'CR80',
            self::AppointmentLetter, self::Certificate => 'A4',
        };
    }

    public function defaultOrientation(): string
    {
        return match ($this) {
            self::IdCard => 'landscape',
            self::AppointmentLetter, self::Certificate => 'portrait',
        };
    }
}
