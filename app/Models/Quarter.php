<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quarter extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'display_order',
        'is_active',
        'start_date',
        'end_date',
    ];

    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
            'is_active' => 'boolean',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function teachingMaterials(): HasMany
    {
        return $this->hasMany(TeachingMaterial::class);
    }

    public function games(): HasMany
    {
        return $this->hasMany(Game::class);
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class);
    }

    public function seatworks(): HasMany
    {
        return $this->hasMany(Seatwork::class);
    }

    public function practicals(): HasMany
    {
        return $this->hasMany(Practical::class);
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }
}
