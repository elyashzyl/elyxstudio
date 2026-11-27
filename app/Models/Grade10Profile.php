<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Grade10Profile extends Model
{
    use HasFactory;

    protected $table = 'grade10_profiles';

    protected $fillable = [
        'user_id',
        'full_name',
        'chinese_name',
        'birthday',
        'hometown',
        'ambition',
        'motto',
        'message',
        'social_media',
        'photo_path',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getPhotoUrl(): ?string
    {
        if (! $this->photo_path) {
            return null;
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->url($this->photo_path);
    }

    public function getPhotoBase64(): ?string
    {
        if (! $this->photo_path) {
            return null;
        }

        $disk = \Illuminate\Support\Facades\Storage::disk('public');
        if (! $disk->exists($this->photo_path)) {
            return null;
        }

        $content = $disk->get($this->photo_path);
        if (! $content) {
            return null;
        }

        $mime = $disk->mimeType($this->photo_path) ?: 'image/jpeg';

        return 'data:' . $mime . ';base64,' . base64_encode($content);
    }
}
