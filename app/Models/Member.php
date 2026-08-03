<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Gender;
use App\Enums\MemberStatus;
use App\Models\Concerns\HasUuid;
use Database\Factories\MemberFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * See docs/02-DATABASE-SCHEMA.md §3 and docs/modules/M03-members.md.
 *
 * @property MemberStatus $status Larastan doesn't yet infer enum casts declared
 *                                via the `casts()` method — see the identical note on Setting::$type.
 * @property Gender|null $gender
 * @property Carbon|null $date_of_birth
 * @property Carbon|null $joined_on
 * @property Carbon|null $valid_until
 */
class Member extends Model
{
    /** @use HasFactory<MemberFactory> */
    use HasFactory, HasUuid, LogsActivity, SoftDeletes;

    protected $fillable = [
        'user_id', 'member_code', 'department_id', 'designation_id', 'photo_path',
        'date_of_birth', 'gender', 'blood_group',
        'address_line1', 'address_line2', 'city', 'state', 'pincode',
        'emergency_contact_name', 'emergency_contact_phone',
        'id_proof_type', 'id_proof_number',
        'joined_on', 'valid_until', 'status', 'notes',
    ];

    /**
     * Status and designation changes are the ones docs/modules/M03-members.md
     * calls out for activity logging explicitly. `id_proof_number` is never
     * logged even though it's not in this list by omission — encrypted casts
     * bypass LogsActivity's diffing anyway, but the allow-list is the real guard.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'designation_id', 'department_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('members');
    }

    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
            'status' => MemberStatus::class,
            'date_of_birth' => 'date',
            'joined_on' => 'date',
            'valid_until' => 'date',
            'id_proof_number' => 'encrypted',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * @return BelongsTo<Designation, $this>
     */
    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    /**
     * @return HasMany<IssuedDocument, $this>
     */
    public function issuedDocuments(): HasMany
    {
        return $this->hasMany(IssuedDocument::class);
    }

    public function isActive(): bool
    {
        return $this->status === MemberStatus::Active;
    }
}
