<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $mime_type
 * @property string $data Base64-encoded image bytes
 */
class UserProfilePhoto extends Model
{
    protected $fillable = [
        'user_id',
        'mime_type',
        'data',
    ];

    protected $hidden = [
        'data',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contents(): string
    {
        return base64_decode($this->data, true) ?: '';
    }
}
