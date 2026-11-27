<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Game extends Model
{
    protected $fillable = ['teacher_id', 'title', 'subject', 'subject_id', 'quarter_id', 'school_year_id', 'grade', 'description', 'xp_reward', 'type', 'hidden', 'is_live', 'auto_reopen'];
    protected $casts = [
        'xp_reward' => 'integer',
        'hidden' => 'boolean',
        'is_live' => 'boolean',
        'auto_reopen' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $game) {
            if (empty($game->school_year_id)) {
                $game->school_year_id = SchoolYear::defaultIdForCreate();
            }
        });
    }

    /**
     * Return all normalized grade representations for a student (e.g. ['Grade 10', '10', 'grade 10']).
     *
     * @return array<int, string>
     */
    public static function studentGradeStrings(User $student): array
    {
        $rawGrades = collect([
            $student->grade,
            $student->gradeLevel?->name,
            $student->grade_level_id ? GradeLevel::where('id', $student->grade_level_id)->value('name') : null,
        ])->filter()->map(fn ($g) => trim((string) $g))->filter(fn ($g) => $g !== '')->unique();

        if ($rawGrades->isEmpty()) {
            return [];
        }

        $allForms = collect();
        foreach ($rawGrades as $gradeStr) {
            $allForms->push($gradeStr);
            $withoutPrefix = trim((string) preg_replace('/^\s*grade\s*/i', '', $gradeStr));
            if ($withoutPrefix !== '') {
                $allForms->push($withoutPrefix);
                $allForms->push('Grade ' . $withoutPrefix);
                $allForms->push('grade ' . $withoutPrefix);
            }
        }

        return $allForms->unique()->values()->all();
    }

    /**
     * Check if this game is accessible to a given student based on grade restrictions.
     */
    public function isAccessibleToStudent(User $student): bool
    {
        $targetGrade = trim((string) ($this->grade ?? ''));
        if ($targetGrade === '' || strcasecmp($targetGrade, 'All Grades') === 0) {
            return true;
        }

        $studentGrades = static::studentGradeStrings($student);
        if (empty($studentGrades)) {
            return false;
        }

        $cleanTarget = strtolower($targetGrade);
        $cleanTargetWithoutPrefix = strtolower(trim((string) preg_replace('/^\s*grade\s*/i', '', $targetGrade)));

        foreach ($studentGrades as $sg) {
            $cleanSg = strtolower($sg);
            if ($cleanSg === $cleanTarget || $cleanSg === $cleanTargetWithoutPrefix) {
                return true;
            }
        }

        return false;
    }

    /**
     * Scope query to games accessible to a given student.
     */
    public function scopeAccessibleToStudent(Builder $query, User $student): Builder
    {
        $studentGrades = static::studentGradeStrings($student);

        return $query->where(function ($q) use ($studentGrades) {
            $q->whereNull('grade')
                ->orWhere('grade', '')
                ->orWhere('grade', 'All Grades');

            if (! empty($studentGrades)) {
                $q->orWhereIn('grade', $studentGrades);
            }
        });
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function subjectRecord(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function quarter(): BelongsTo
    {
        return $this->belongsTo(Quarter::class, 'quarter_id');
    }

    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class, 'school_year_id');
    }

    public function cards(): HasMany
    {
        return $this->hasMany(GameCard::class)->orderBy('order');
    }

    public function progress(): HasOne
    {
        return $this->hasOne(GameProgress::class);
    }

    public function progresses(): HasMany
    {
        return $this->hasMany(GameProgress::class);
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(GameRoom::class);
    }

    public function reopenRequests(): HasMany
    {
        return $this->hasMany(GameReopenRequest::class);
    }
}
