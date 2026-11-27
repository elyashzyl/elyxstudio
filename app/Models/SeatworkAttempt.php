<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SeatworkAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'seatwork_id',
        'student_id',
        'attempt_number',
        'status',
        'score',
        'total_points',
        'is_recorded',
        'started_at',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'attempt_number' => 'integer',
            'is_recorded' => 'boolean',
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    public function seatwork(): BelongsTo
    {
        return $this->belongsTo(Seatwork::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(SeatworkAnswer::class, 'attempt_id');
    }
}
