<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RewardRedemption extends Model
{
    protected $fillable = ['user_id', 'level', 'status', 'metadata'];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
