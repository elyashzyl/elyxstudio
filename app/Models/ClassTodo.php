<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassTodo extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_id',
        'title',
        'description',
        'category',
        'target_grade_level_id',
        'target_section_id',
        'due_date',
        'link_url',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class, 'target_grade_level_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'target_section_id');
    }

    public function completions(): HasMany
    {
        return $this->hasMany(ClassTodoCompletion::class, 'class_todo_id');
    }

    public function isCompletedBy(?int $userId): bool
    {
        if (! $userId) {
            return false;
        }

        return $this->completions()->where('user_id', $userId)->exists();
    }
}
