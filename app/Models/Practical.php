<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Practical extends Model
{
    use HasFactory;

    protected $fillable = ['teacher_id', 'title', 'grade', 'instructions', 'time_limit_minutes', 'max_score', 'xp_reward', 'is_published', 'max_attempts', 'quarter_id', 'school_year_id', 'closes_at'];

    protected static function booted(): void
    {
        static::creating(function (self $practical) {
            if (empty($practical->school_year_id)) {
                $practical->school_year_id = SchoolYear::defaultIdForCreate();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'xp_reward' => 'integer',
            'closes_at' => 'datetime',
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
        return $this->belongsToMany(GradeLevel::class, 'practical_grade_level');
    }

    public function criteria(): HasMany
    {
        return $this->hasMany(PracticalCriterion::class)->orderBy('order');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(PracticalAttempt::class);
    }

    public function totalMaxPoints(): int
    {
        return $this->criteria()->sum('max_points');
    }
}
