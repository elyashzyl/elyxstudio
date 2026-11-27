<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class SchoolYear extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'start_date',
        'end_date',
        'is_current',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_current' => 'boolean',
            'is_active' => 'boolean',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function teachingMaterials(): HasMany
    {
        return $this->hasMany(TeachingMaterial::class);
    }

    public function games(): HasMany
    {
        return $this->hasMany(Game::class);
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class);
    }

    public function seatworks(): HasMany
    {
        return $this->hasMany(Seatwork::class);
    }

    public function practicals(): HasMany
    {
        return $this->hasMany(Practical::class);
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }

    /**
     * Get the current system school year (defaults to 2026-2027).
     */
    public static function current(): ?self
    {
        if (! Schema::hasTable('school_years')) {
            return null;
        }

        $current = static::where('is_current', true)->first();
        if ($current) {
            return $current;
        }

        $first = static::where('is_active', true)->orderByDesc('name')->first()
            ?? static::orderByDesc('name')->first();

        if ($first) {
            return $first;
        }

        return static::create([
            'name' => '2026-2027',
            'description' => 'Academic School Year 2026-2027',
            'start_date' => '2026-06-01',
            'end_date' => '2027-05-31',
            'is_current' => true,
            'is_active' => true,
        ]);
    }

    public static function currentId(): ?int
    {
        return static::current()?->id;
    }

    /**
     * Get the teacher/admin's currently selected school year filter from session,
     * falling back to the system's current school year ID.
     * Can return an integer school_year_id, 'all', or null.
     */
    public static function selectedFilter(): int|string|null
    {
        if (! Schema::hasTable('school_years')) {
            return null;
        }

        $sessionVal = session('selected_school_year_id');
        if ($sessionVal === 'all') {
            return 'all';
        }

        if ($sessionVal && is_numeric($sessionVal)) {
            $exists = static::whereKey((int) $sessionVal)->exists();
            if ($exists) {
                return (int) $sessionVal;
            }
        }

        return static::currentId();
    }

    /**
     * Resolve the default school_year_id to assign when creating a new record.
     */
    public static function defaultIdForCreate(): ?int
    {
        $selected = static::selectedFilter();
        if (is_int($selected)) {
            return $selected;
        }

        return static::currentId();
    }

    /**
     * Apply the active school year filter to an Eloquent query builder.
     */
    public static function applyFilter(Builder $query, int|string|null $filter = null): Builder
    {
        $resolved = $filter ?? static::selectedFilter();

        if ($resolved === 'all' || $resolved === null) {
            return $query;
        }

        $currentId = static::currentId();
        $targetId = (int) $resolved;

        return $query->where(function (Builder $q) use ($targetId, $currentId) {
            $q->where('school_year_id', $targetId);
            if ($currentId && $targetId === (int) $currentId) {
                $q->orWhereNull('school_year_id');
            }
        });
    }
}
