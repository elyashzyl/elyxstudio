<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $role
 * @property string|null $grade
 * @property Carbon|null $profile_photo_updated_at
 * @property string|null $bio
 * @property int|null $grade_level_id
 * @property int|null $section_id
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password', 'role', 'grade', 'grade_level_id', 'section_id', 'gender', 'bio', 'messenger_enabled', 'allow_classmate_messaging', 'streak_freeze_count', 'streak_freeze_active', 'equipped_badge', 'active_xp_booster', 'active_brain_shield', 'total_points'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    protected $appends = ['avatar', 'avatar_url'];

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    protected function avatar(): Attribute
    {
        return Attribute::get(function (): ?string {
            if (! $this->profile_photo_updated_at) {
                return null;
            }

            $url = route('profile.avatar', ['user' => $this->getKey()], false);

            return $url . '?v=' . $this->profile_photo_updated_at->timestamp;
        });
    }

    protected function avatarUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->avatar);
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'last_login_at' => 'datetime',
            'profile_photo_updated_at' => 'datetime',
            'messenger_enabled' => 'boolean',
            'allow_classmate_messaging' => 'boolean',
            'streak_freeze_active' => 'boolean',
            'streak_freeze_count' => 'integer',
            'active_xp_booster' => 'boolean',
            'active_brain_shield' => 'boolean',
        ];
    }


    public function isSuperadmin(): bool
    {
        return $this->role === 'superadmin';
    }

    public function isTeacher(): bool
    {
        return $this->isSuperadmin() || $this->role === 'teacher';
    }

    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    public function isBuyer(): bool
    {
        return $this->role === 'buyer';
    }

    public function isSeller(): bool
    {
        return $this->role === 'seller';
    }

    public function isCommerceAdmin(): bool
    {
        return $this->isSuperadmin();
    }

    public function canMessageClassmates(): bool
    {
        if (! $this->isStudent()) {
            return true;
        }

        if (! Setting::isClassmateMessagingEnabled()) {
            return false;
        }

        $teachers = $this->teachers()->get();
        if ($teachers->isNotEmpty()) {
            if ($teachers->contains(fn ($t) => ! (bool) ($t->allow_classmate_messaging ?? true))) {
                return false;
            }
        }

        return true;
    }

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'student_teacher', 'student_id', 'teacher_id');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'student_teacher', 'teacher_id', 'student_id');
    }

    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class, 'conversation_participants')
            ->withPivot('last_read_at')
            ->withTimestamps();
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class, 'teacher_id');
    }

    public function seatworks(): HasMany
    {
        return $this->hasMany(Seatwork::class, 'teacher_id');
    }

    public function practicals(): HasMany
    {
        return $this->hasMany(Practical::class, 'teacher_id');
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class, 'teacher_id');
    }

    public function teachingMaterials(): HasMany
    {
        return $this->hasMany(TeachingMaterial::class, 'teacher_id');
    }

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'subject_teacher', 'teacher_id', 'subject_id')
            ->withTimestamps();
    }

    public function quizAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class, 'student_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class);
    }

    public function profilePhoto(): HasOne
    {
        return $this->hasOne(UserProfilePhoto::class);
    }

    public function grade10Profile(): HasOne
    {
        return $this->hasOne(Grade10Profile::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'seller_id');
    }

    public function approvedProducts(): HasMany
    {
        return $this->hasMany(Product::class, 'approved_by');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function isGrade10(): bool
    {
        if (! $this->isStudent()) {
            return false;
        }

        $gradeName = $this->gradeLevel?->name ?? $this->grade ?? '';

        return (bool) preg_match('/(?:grade\s*10\b|\b10\b|g10\b)/i', (string) $gradeName);
    }
}
