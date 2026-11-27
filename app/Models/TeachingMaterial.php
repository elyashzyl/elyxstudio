<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeachingMaterial extends Model
{
    protected $fillable = [
        'teacher_id',
        'title',
        'subject',
        'subject_id',
        'description',
        'file_path',
        'file_name',
        'file_size',
        'file_type',
        'grade_level_id',
        'quarter_id',
        'school_year_id',
        'file_data',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $material) {
            if (empty($material->school_year_id)) {
                $material->school_year_id = SchoolYear::defaultIdForCreate();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function subjectRecord(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class, 'grade_level_id');
    }

    public function quarter(): BelongsTo
    {
        return $this->belongsTo(Quarter::class, 'quarter_id');
    }

    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class, 'school_year_id');
    }

    public function reads(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TeachingMaterialRead::class, 'teaching_material_id');
    }

    public function isReadBy(?int $userId): bool
    {
        if (! $userId) {
            return false;
        }

        return $this->reads()->where('user_id', $userId)->exists();
    }

    /**
     * Resolve the physical file path, restoring from embedded file_data if the disk copy was wiped.
     */
    public function getResolvedFilePath(): ?string
    {
        $path = \App\Services\SecureFileService::resolve($this->file_path);
        if ($path && file_exists($path)) {
            return $path;
        }

        // If file missing on disk but backed up in database, self-heal to storage
        if (! empty($this->file_data)) {
            $relativePath = ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string) $this->file_path), DIRECTORY_SEPARATOR);
            $dest = storage_path('app/public/' . $relativePath);
            $dir = dirname($dest);
            if (! is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }

            $binary = base64_decode((string) $this->file_data, true);
            if ($binary !== false) {
                file_put_contents($dest, $binary);
                return file_exists($dest) ? $dest : null;
            }
        }

        return null;
    }
}