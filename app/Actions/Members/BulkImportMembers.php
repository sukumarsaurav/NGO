<?php

declare(strict_types=1);

namespace App\Actions\Members;

use App\Models\Department;
use App\Models\Designation;
use App\Models\User;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;

/**
 * CSV import: upload -> column mapping -> dry run -> error report -> confirm
 * -> import. See docs/modules/M03-members.md.
 *
 * Column mapping here is auto-matching by header name (case-insensitive),
 * not a manual drag-and-drop mapper — the doc's valuable behaviours are the
 * dry run, per-row error reporting, and name-based department/designation
 * resolution with near-miss suggestions; a fully generic column-mapping UI is
 * a larger feature than Sprint 3's budget and is deliberately out of scope.
 *
 * Every row is re-validated on the real (non-dry-run) pass too, not just the
 * dry run — data can change between the two (someone else registers that
 * email in the meantime).
 */
final class BulkImportMembers
{
    /** @var string[] */
    public const EXPECTED_HEADERS = [
        'name', 'email', 'phone', 'department', 'designation',
        'date_of_birth', 'gender', 'blood_group',
        'address_line1', 'address_line2', 'city', 'state', 'pincode',
        'emergency_contact_name', 'emergency_contact_phone',
        'id_proof_type', 'id_proof_number', 'joined_on',
    ];

    public function __construct(
        private readonly CreateMember $createMember,
    ) {}

    /**
     * @param  list<array<string, string|null>>  $rows  1-indexed by position, header-keyed
     */
    public function handle(array $rows, bool $dryRun = true): ImportResult
    {
        $errors = [];
        $created = [];
        $seenEmails = [];
        $existingEmails = User::query()->pluck('email')->map(fn (string $e) => strtolower(trim($e)))->all();

        $departments = Department::query()->pluck('id', 'name')->all();
        $designations = Designation::query()->pluck('id', 'title')->all();

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 1;
            $rowErrors = $this->validateRow($row, $rowNumber, $seenEmails, $existingEmails, $departments, $designations);

            if ($rowErrors !== []) {
                array_push($errors, ...$rowErrors);

                continue;
            }

            $email = strtolower(trim((string) $row['email']));
            $seenEmails[] = $email;

            if ($dryRun) {
                continue;
            }

            $created[] = $this->createMember->handle([
                'name' => trim((string) $row['name']),
                'email' => $email,
                'phone' => $this->blankToNull($row['phone'] ?? null),
                'department_id' => $departments[$this->normaliseLookup($row['department'] ?? null, $departments)] ?? null,
                'designation_id' => $designations[$this->normaliseLookup($row['designation'] ?? null, $designations)] ?? null,
                'date_of_birth' => $this->parseDate($row['date_of_birth'] ?? null),
                'gender' => $this->blankToNull($row['gender'] ?? null),
                'blood_group' => $this->blankToNull($row['blood_group'] ?? null),
                'address_line1' => $this->blankToNull($row['address_line1'] ?? null),
                'address_line2' => $this->blankToNull($row['address_line2'] ?? null),
                'city' => $this->blankToNull($row['city'] ?? null),
                'state' => $this->blankToNull($row['state'] ?? null),
                'pincode' => $this->blankToNull($row['pincode'] ?? null),
                'emergency_contact_name' => $this->blankToNull($row['emergency_contact_name'] ?? null),
                'emergency_contact_phone' => $this->blankToNull($row['emergency_contact_phone'] ?? null),
                'id_proof_type' => $this->blankToNull($row['id_proof_type'] ?? null),
                'id_proof_number' => $this->blankToNull($row['id_proof_number'] ?? null),
                'joined_on' => $this->parseDate($row['joined_on'] ?? null) ?? now()->toDateString(),
            ]);
        }

        return new ImportResult(
            totalRows: count($rows),
            errors: $errors,
            created: $created,
            dryRun: $dryRun,
        );
    }

    /**
     * @param  array<string, string|null>  $row
     * @param  string[]  $seenEmails
     * @param  string[]  $existingEmails
     * @param  array<string, int>  $departments
     * @param  array<string, int>  $designations
     * @return list<ImportRowError>
     */
    private function validateRow(
        array $row,
        int $rowNumber,
        array $seenEmails,
        array $existingEmails,
        array $departments,
        array $designations,
    ): array {
        $errors = [];

        $name = trim((string) ($row['name'] ?? ''));
        if ($name === '') {
            $errors[] = new ImportRowError($rowNumber, 'name', 'Name is required.');
        }

        $email = strtolower(trim((string) ($row['email'] ?? '')));
        if ($email === '') {
            $errors[] = new ImportRowError($rowNumber, 'email', 'Email is required.');
        } elseif (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = new ImportRowError($rowNumber, 'email', "'{$email}' is not a valid email address.");
        } elseif (in_array($email, $existingEmails, true)) {
            // Never overwrite an existing member silently — see the
            // "duplicate email on import" edge case.
            $errors[] = new ImportRowError($rowNumber, 'email', "A user with email '{$email}' already exists.");
        } elseif (in_array($email, $seenEmails, true)) {
            $errors[] = new ImportRowError($rowNumber, 'email', "Duplicate email '{$email}' earlier in this file.");
        }

        if (! empty($row['date_of_birth']) && $this->parseDate($row['date_of_birth']) === null) {
            $errors[] = new ImportRowError($rowNumber, 'date_of_birth', "Could not parse date '{$row['date_of_birth']}'. Use dd/mm/yyyy or yyyy-mm-dd.");
        }

        if (! empty($row['joined_on']) && $this->parseDate($row['joined_on']) === null) {
            $errors[] = new ImportRowError($rowNumber, 'joined_on', "Could not parse date '{$row['joined_on']}'. Use dd/mm/yyyy or yyyy-mm-dd.");
        }

        if (! empty($row['department']) && ! isset($departments[$this->normaliseLookup($row['department'], $departments)])) {
            $suggestion = $this->suggest((string) $row['department'], array_keys($departments));
            $errors[] = new ImportRowError(
                $rowNumber, 'department',
                "Department '{$row['department']}' not found.".($suggestion ? " Did you mean '{$suggestion}'?" : '')
            );
        }

        if (! empty($row['designation']) && ! isset($designations[$this->normaliseLookup($row['designation'], $designations)])) {
            $suggestion = $this->suggest((string) $row['designation'], array_keys($designations));
            $errors[] = new ImportRowError(
                $rowNumber, 'designation',
                "Designation '{$row['designation']}' not found.".($suggestion ? " Did you mean '{$suggestion}'?" : '')
            );
        }

        return $errors;
    }

    /**
     * Case-insensitive lookup key resolution — "Fundraising" and "fundraising"
     * both resolve to the same seeded department.
     *
     * @param  array<string, int>  $options
     */
    private function normaliseLookup(?string $value, array $options): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        foreach (array_keys($options) as $name) {
            if (strcasecmp($name, trim($value)) === 0) {
                return $name;
            }
        }

        return $value;
    }

    /**
     * Closest existing name by similarity, for the "did you mean" hint on a
     * near-miss department/designation name.
     *
     * @param  string[]  $candidates
     */
    private function suggest(string $input, array $candidates): ?string
    {
        $best = null;
        $bestPercent = 0.0;

        foreach ($candidates as $candidate) {
            similar_text(strtolower($input), strtolower($candidate), $percent);

            if ($percent > $bestPercent) {
                $bestPercent = $percent;
                $best = $candidate;
            }
        }

        // Below ~55% similarity the suggestion is more confusing than helpful.
        return $bestPercent >= 55.0 ? $best : null;
    }

    private function parseDate(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        foreach (['d/m/Y', 'Y-m-d'] as $format) {
            try {
                return Carbon::createFromFormat($format, trim($value))->toDateString();
            } catch (InvalidFormatException) {
                continue;
            }
        }

        return null;
    }

    private function blankToNull(?string $value): ?string
    {
        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
