<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameRoomParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_room_id',
        'user_id',
        'score',
        'streak',
        'last_answered_card_index',
        'last_answered_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'streak' => 'integer',
            'last_answered_card_index' => 'integer',
            'last_answered_at' => 'datetime',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(GameRoom::class, 'game_room_id');
    }

    public function gameRoom(): BelongsTo
    {
        return $this->room();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
