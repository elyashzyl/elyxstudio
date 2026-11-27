<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameProgress extends Model
{
    protected $fillable = ['user_id', 'game_id', 'current_card', 'done_count', 'score', 'completed', 'card_order', 'graded', 'hints_used'];

    protected $casts = [
        'completed' => 'boolean',
        'hints_used' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
}