<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Seatwork extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_id',
        'title',
        'instructions',
        'grade',
        'time_limit_minutes',
        'max_attempts',
        'grading_policy',
        'is_published',
        'quarter_id',
        'school_year_id',
        'closes_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $seatwork) {
            if (empty($seatwork->school_year_id)) {
                $seatwork->school_year_id = SchoolYear::defaultIdForCreate();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'closes_at' => 'datetime',
            'max_attempts' => 'integer',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function quarter(): BelongsTo
    {
        return $this->belongsTo(Quarter::class, 'quarter_id');
    }

    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class, 'school_year_id');
    }

    public function isClosed(): bool
    {
        return $this->closes_at && $this->closes_at->isPast();
    }

    public function gradeLevels(): BelongsToMany
    {
        return $this->belongsToMany(GradeLevel::class, 'seatwork_grade_level');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(SeatworkQuestion::class)->orderBy('order');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(SeatworkAttempt::class);
    }

    public function totalPoints(): int
    {
        return $this->questions()->sum('points');
    }

    /**
     * Recalculate which attempt counts as the official recorded score for a student.
     */
    public function recalculateRecordedAttempts(int $studentId): ?SeatworkAttempt
    {
        $submittedAttempts = $this->attempts()
            ->where('student_id', $studentId)
            ->where('status', \App\Enums\AttemptStatus::Submitted->value)
            ->orderBy('attempt_number', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        if ($submittedAttempts->isEmpty()) {
            return null;
        }

        $policy = $this->grading_policy ?: 'first';
        $chosen = null;

        if ($policy === 'first') {
            $chosen = $submittedAttempts->firstWhere('attempt_number', 1) ?: $submittedAttempts->first();
        } elseif ($policy === 'second') {
            $chosen = $submittedAttempts->firstWhere('attempt_number', 2) ?: $submittedAttempts->first();
        } elseif ($policy === 'third') {
            $chosen = $submittedAttempts->firstWhere('attempt_number', 3)
                ?: ($submittedAttempts->firstWhere('attempt_number', 2) ?: $submittedAttempts->first());
        } elseif ($policy === 'highest') {
            $chosen = $submittedAttempts->sortByDesc('score')->first();
        } elseif ($policy === 'latest') {
            $chosen = $submittedAttempts->sortByDesc('attempt_number')->first();
        } elseif ($policy === 'manual') {
            $chosen = $submittedAttempts->firstWhere('is_recorded', true) ?: $submittedAttempts->sortByDesc('attempt_number')->first();
        } else {
            $chosen = $submittedAttempts->first();
        }

        if ($chosen) {
            foreach ($submittedAttempts as $att) {
                $isRec = (int) $att->id === (int) $chosen->id;
                if ((bool) $att->is_recorded !== $isRec) {
                    $att->update(['is_recorded' => $isRec]);
                }
            }
        }

        return $chosen;
    }

    /**
     * Re-evaluate and re-award official grades for all students when grading policy changes.
     */
    public function recalculateAllStudentsRecordedAttempts(): void
    {
        $studentIds = $this->attempts()
            ->where('status', \App\Enums\AttemptStatus::Submitted->value)
            ->pluck('student_id')
            ->unique();

        foreach ($studentIds as $sId) {
            $recorded = $this->recalculateRecordedAttempts((int) $sId);
            if ($recorded) {
                \App\Helpers\PointsHelper::award(
                    (int) $sId,
                    \App\Enums\ActivityType::Seatwork->value,
                    $this->id,
                    (int) $recorded->score,
                    $this->totalPoints(),
                    null,
                    true
                );
            }
        }
    }
}
