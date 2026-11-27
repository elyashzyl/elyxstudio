<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameRoom extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'teacher_id',
        'pin',
        'status',
        'current_card_index',
        'state',
    ];

    protected function casts(): array
    {
        return [
            'current_card_index' => 'integer',
            'state' => 'array',
        ];
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(GameRoomParticipant::class);
    }

    public function isWaiting(): bool
    {
        return $this->status === 'waiting';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isFinished(): bool
    {
        return $this->status === 'finished';
    }

    /**
     * Check if this live room is accessible to a given student.
     */
    public function isAccessibleToStudent(User $student): bool
    {
        $this->loadMissing('game');

        return $this->game ? $this->game->isAccessibleToStudent($student) : true;
    }

    /**
     * Scope query to game rooms whose game is accessible to a given student.
     */
    public function scopeAccessibleToStudent(Builder $query, User $student): Builder
    {
        return $query->whereHas('game', function (Builder $gq) use ($student) {
            $gq->accessibleToStudent($student);
        });
    }
}
