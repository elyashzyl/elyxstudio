<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'body',
        'attachment_path',
        'attachment_name',
        'attachment_type',
        'attachment_size',
        'attachment_data',
        'edited_at',
    ];

    protected $casts = [
        'edited_at' => 'datetime',
        'attachment_size' => 'integer',
    ];

    public function getResolvedAttachmentPath(): ?string
    {
        if (! $this->attachment_path) {
            return null;
        }

        $resolved = \App\Services\SecureFileService::resolve($this->attachment_path);
        if ($resolved && file_exists($resolved)) {
            return $resolved;
        }

        if (! empty($this->attachment_data)) {
            $dest = storage_path('app/private/' . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $this->attachment_path), DIRECTORY_SEPARATOR));
            $dir = dirname($dest);
            if (! is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            $binary = base64_decode((string) $this->attachment_data, true);
            if ($binary !== false) {
                @file_put_contents($dest, $binary);
                if (file_exists($dest)) {
                    return $dest;
                }
            }
        }

        return null;
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        return $this->attachment_path ? route('messenger.attachment', $this) : null;
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
