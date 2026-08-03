<?php

declare(strict_types=1);

use App\Enums\DocumentType;
use App\Services\Numbering\DocumentNumberGenerator;

it('produces sequential prefixed numbers per type per year', function () {
    $generator = app(DocumentNumberGenerator::class);

    $first = $generator->next(DocumentType::AppointmentLetter);
    $second = $generator->next(DocumentType::AppointmentLetter);
    $idCard = $generator->next(DocumentType::IdCard);

    $year = now()->year;

    expect($first)->toBe("AL-{$year}-0001")
        ->and($second)->toBe("AL-{$year}-0002")
        ->and($idCard)->toBe("IC-{$year}-0001");
});
