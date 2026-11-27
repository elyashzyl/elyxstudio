<?php

namespace App\Models;

use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AiManagerLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_type',
        'status',
        'target_type',
        'target_id',
        'confidence',
        'summary',
        'payload',
        'reverted_at',
    ];

    protected $casts = [
        'confidence' => 'float',
        'payload' => 'array',
        'reverted_at' => 'datetime',
    ];

    public function target(): MorphTo
    {
        return $this->morphTo('target', 'target_type', 'target_id');
    }

    public function canBeReverted(): bool
    {
        if ($this->reverted_at !== null) {
            return false;
        }

        if ($this->event_type !== 'auto_grade') {
            return false;
        }

        if (empty($this->target_type) || empty($this->target_id)) {
            return false;
        }

        $payload = $this->payload ?? [];

        return array_key_exists('previous_score', $payload);
    }

    public function revert(?User $actor = null): bool
    {
        if (! $this->canBeReverted()) {
            return false;
        }

        $targetClass = $this->target_type;
        if (! class_exists($targetClass)) {
            return false;
        }

        $target = $targetClass::find($this->target_id);
        if (! $target) {
            return false;
        }

        $payload = $this->payload ?? [];
        $previousScore = $payload['previous_score'] ?? null;
        $previousStatus = $payload['previous_status'] ?? null;

        if (isset($target->score) || array_key_exists('score', $target->getAttributes())) {
            $target->score = $previousScore;
        }
        if (isset($target->total_score) || array_key_exists('total_score', $target->getAttributes())) {
            $target->total_score = $previousScore;
        }
        if ($previousStatus !== null && (isset($target->status) || array_key_exists('status', $target->getAttributes()))) {
            $target->status = $previousStatus;
        }

        // Restore individual answers or scores if saved in payload
        if ($target instanceof \App\Models\PracticalAttempt) {
            $prevScores = $payload['previous_criteria_scores'] ?? null;
            if ($prevScores === null) {
                $target->scores()->delete();
            } else {
                $target->scores()->delete();
                foreach ($prevScores as $critId => $critData) {
                    $target->scores()->create([
                        'criterion_id' => $critId,
                        'score' => $critData['score'] ?? 0,
                        'comment' => $critData['comment'] ?? '',
                    ]);
                }
            }
        } elseif ($target instanceof \App\Models\SeatworkAttempt) {
            $prevAns = $payload['previous_answers'] ?? null;
            if (is_array($prevAns)) {
                foreach ($prevAns as $ansId => $ansData) {
                    $target->answers()->where('id', $ansId)->update([
                        'is_correct' => $ansData['is_correct'] ?? null,
                        'points_awarded' => $ansData['points_awarded'] ?? null,
                        'feedback' => $ansData['feedback'] ?? null,
                    ]);
                }
            }
        } elseif ($target instanceof \App\Models\QuizAttempt) {
            $prevAns = $payload['previous_answers'] ?? null;
            if (is_array($prevAns)) {
                foreach ($prevAns as $ansId => $ansData) {
                    $target->answers()->where('id', $ansId)->update([
                        'is_correct' => $ansData['is_correct'] ?? null,
                    ]);
                }
            }
        }

        $target->save();

        $this->update(['reverted_at' => now()]);

        ActivityLogger::log(
            'ai_manager_revert',
            "Reverted AI Manager action #{$this->id} on {$this->target_type} #{$this->target_id}",
            $target,
            $actor
        );

        return true;
    }
}
