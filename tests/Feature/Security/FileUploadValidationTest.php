<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;

/**
 * Sprint 15 acceptance criterion — docs/03-ROADMAP.md: "Uploading a `.php`
 * renamed to `.jpg` is rejected." `NoticeForm::attachment_path` (and every
 * other FileUpload field) uses Filament's `acceptedFileTypes()`, which
 * attaches a real `mimetypes:` validation rule — content-sniffed via
 * Symfony's finfo-based guesser, not extension-matched.
 *
 * Tested directly against Laravel's Validator rather than through a
 * Livewire form: Livewire's own file-upload test helpers require
 * `UploadedFile::fake()`, which stubs `getMimeType()` to just echo back
 * whatever type you declare — that would make this test pass regardless
 * of whether real content-sniffing is wired up. A real `UploadedFile`
 * (with the `$test` constructor flag only to skip "was this actually
 * POSTed" framework checks) exercises the genuine guesser, exactly like a
 * production upload does.
 */
function realUploadedFile(string $filename, string $content): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'upload-test');
    rename($path, $path .= '-'.$filename);
    file_put_contents($path, $content);

    return new UploadedFile($path, $filename, null, null, true);
}

it('rejects a PHP script disguised with an image extension via the mimetypes rule notice attachments use', function () {
    $disguisedPhp = realUploadedFile('evil.jpg', '<?php echo "pwned"; ?>');

    $validator = Validator::make(
        ['attachment' => $disguisedPhp],
        ['attachment' => 'mimetypes:application/pdf,image/png,image/jpeg,image/webp'],
    );

    expect($validator->fails())->toBeTrue();
});

it('accepts a genuine PDF under the same rule', function () {
    $realPdf = realUploadedFile('flyer.pdf', "%PDF-1.4\n%\xe2\xe3\xcf\xd3\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF");

    $validator = Validator::make(
        ['attachment' => $realPdf],
        ['attachment' => 'mimetypes:application/pdf,image/png,image/jpeg,image/webp'],
    );

    expect($validator->fails())->toBeFalse();
});

it('rejects a PHP script disguised as an image on every image FileUpload field in the app', function () {
    $disguisedPhp = realUploadedFile('evil.png', '<?php echo "pwned"; ?>');

    // Every `->image()` call in app/Filament resolves to Filament's own
    // `acceptedFileTypes(['image/*'])`, which becomes `mimetypes:image/*`.
    $validator = Validator::make(
        ['photo' => $disguisedPhp],
        ['photo' => 'mimetypes:image/*'],
    );

    expect($validator->fails())->toBeTrue();
});
