<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Conversation;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Game;
use App\Models\Grade10Profile;
use App\Models\Meeting;
use App\Models\Message;
use App\Models\Practical;
use App\Models\PracticalAttempt;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\RewardRedemption;
use App\Models\Seatwork;
use App\Models\SeatworkAttempt;
use App\Models\StudentPoint;
use App\Models\TeacherSchedule;
use App\Models\TeachingMaterial;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class UserMergeService
{
    /**
     * Merge $source user account into $target user account.
     * All associated records, attempts, submissions, scores, XP, messages,
     * and materials are safely consolidated or transferred to $target,
     * and $source is deleted.
     *
     * @return array<string, mixed>
     */
    public function merge(User $source, User $target): array
    {
        if ($source->id === $target->id) {
            throw new InvalidArgumentException('Source and target user cannot be the same account.');
        }

        if ($source->role !== $target->role) {
            throw new InvalidArgumentException("Cannot merge users of different roles ({$source->role} into {$target->role}).");
        }

        return DB::transaction(function () use ($source, $target) {
            $stats = [
                'source_id' => $source->id,
                'target_id' => $target->id,
                'source_name' => $source->name,
                'target_name' => $target->name,
                'source_email' => $source->email,
                'target_email' => $target->email,
                'role' => $source->role,
                'quiz_attempts_moved' => 0,
                'seatwork_attempts_moved' => 0,
                'practical_attempts_moved' => 0,
                'exam_attempts_moved' => 0,
                'points_consolidated' => 0,
                'messages_moved' => 0,
                'conversations_consolidated' => 0,
                'game_progress_consolidated' => 0,
                'materials_moved' => 0,
                'activities_moved' => 0,
            ];

            // 1. Consolidate profile metadata (fill empty fields on target)
            $this->mergeProfileFields($source, $target);

            // 2. Student assessments & gamification
            if ($source->isStudent()) {
                $this->mergeStudentAssessments($source, $target, $stats);
                $this->mergeGamification($source, $target, $stats);
                $this->mergeGrade10Profile($source, $target);
                $this->mergeStudentTeachers($source, $target);
            }

            // 3. Teacher content, schedules & student associations
            if ($source->isTeacher()) {
                $this->mergeTeacherContent($source, $target, $stats);
                $this->mergeTeacherStudents($source, $target);
            }

            // 4. Messenger: messages & conversations
            $this->mergeMessenger($source, $target, $stats);

            // 5. Passkeys
            $this->mergePasskeys($source, $target);

            // 6. Activity logs
            ActivityLog::where('user_id', $source->id)->update(['user_id' => $target->id]);

            // 7. Flush active sessions of source user
            DB::table('sessions')->where('user_id', $source->id)->delete();

            // 8. Delete the source user account
            $source->delete();

            // 9. Recompute target student total points / XP
            if ($target->isStudent()) {
                $target->total_points = (int) StudentPoint::where('student_id', $target->id)->sum('points');
                $target->save();
            }

            // 10. Audit log the merge operation
            ActivityLogger::log(
                'user_merged',
                "Merged {$stats['role']} '{$stats['source_name']}' ({$stats['source_email']}) into '{$stats['target_name']}' ({$stats['target_email']})",
                $target,
                null,
                $stats
            );

            return $stats;
        });
    }

    /**
     * Backfill missing profile details from source to target.
     */
    protected function mergeProfileFields(User $source, User $target): void
    {
        $dirty = false;

        if (empty($target->gender) && ! empty($source->gender)) {
            $target->gender = $source->gender;
            $dirty = true;
        }

        if (empty($target->grade_level_id) && ! empty($source->grade_level_id)) {
            $target->grade_level_id = $source->grade_level_id;
            $dirty = true;
        }

        if (empty($target->section_id) && ! empty($source->section_id)) {
            $target->section_id = $source->section_id;
            $dirty = true;
        }

        if (empty($target->grade) && ! empty($source->grade)) {
            $target->grade = $source->grade;
            $dirty = true;
        }

        if ($dirty) {
            $target->save();
        }
    }

    /**
     * Merge student assessments across quizzes, seatworks, practicals, and exams.
     *
     * @param array<string, mixed> $stats
     */
    protected function mergeStudentAssessments(User $source, User $target, array &$stats): void
    {
        // 1. Quiz attempts (unique constraint on quiz_id, student_id)
        $sourceQuizAttempts = QuizAttempt::where('student_id', $source->id)->get();
        foreach ($sourceQuizAttempts as $sAttempt) {
            $tAttempt = QuizAttempt::where('student_id', $target->id)
                ->where('quiz_id', $sAttempt->quiz_id)
                ->first();

            if (! $tAttempt) {
                $sAttempt->update(['student_id' => $target->id]);
                $stats['quiz_attempts_moved']++;
            } else {
                // Both have attempts. Keep the completed or higher-scoring attempt.
                $sourceIsBetter = false;
                if ($sAttempt->status === 'completed' && $tAttempt->status !== 'completed') {
                    $sourceIsBetter = true;
                } elseif ($sAttempt->status === $tAttempt->status && $sAttempt->score > $tAttempt->score) {
                    $sourceIsBetter = true;
                }

                if ($sourceIsBetter) {
                    $tAttempt->delete();
                    $sAttempt->update(['student_id' => $target->id]);
                    $stats['quiz_attempts_moved']++;
                } else {
                    $sAttempt->delete();
                }
            }
        }

        // 2. Seatwork attempts
        $stats['seatwork_attempts_moved'] = (int) SeatworkAttempt::where('student_id', $source->id)
            ->update(['student_id' => $target->id]);

        // 3. Practical attempts
        $stats['practical_attempts_moved'] = (int) PracticalAttempt::where('student_id', $source->id)
            ->update(['student_id' => $target->id]);

        // 4. Exam attempts
        $stats['exam_attempts_moved'] = (int) ExamAttempt::where('student_id', $source->id)
            ->update(['student_id' => $target->id]);
    }

    /**
     * Merge points, game progress, and reward redemptions.
     *
     * @param array<string, mixed> $stats
     */
    protected function mergeGamification(User $source, User $target, array &$stats): void
    {
        // 1. Student points (deduplicate by activity_type and activity_id)
        $sourcePoints = StudentPoint::where('student_id', $source->id)->get();
        foreach ($sourcePoints as $sp) {
            $tPoint = StudentPoint::where('student_id', $target->id)
                ->where('activity_type', $sp->activity_type)
                ->where('activity_id', $sp->activity_id)
                ->first();

            if (! $tPoint) {
                $sp->update(['student_id' => $target->id]);
                $stats['points_consolidated']++;
            } else {
                if ($sp->points > $tPoint->points) {
                    $tPoint->update([
                        'points' => $sp->points,
                        'score' => $sp->score ?? $tPoint->score,
                        'total' => $sp->total ?? $tPoint->total,
                    ]);
                    $stats['points_consolidated']++;
                }
                $sp->delete();
            }
        }

        // 2. Game progress (unique on user_id, game_id)
        $sourceProgress = DB::table('game_progress')->where('user_id', $source->id)->get();
        foreach ($sourceProgress as $sp) {
            $tProgress = DB::table('game_progress')
                ->where('user_id', $target->id)
                ->where('game_id', $sp->game_id)
                ->first();

            if (! $tProgress) {
                DB::table('game_progress')->where('id', $sp->id)->update(['user_id' => $target->id]);
                $stats['game_progress_consolidated']++;
            } else {
                if ($sp->score > $tProgress->score || ($sp->completed && ! $tProgress->completed)) {
                    DB::table('game_progress')->where('id', $tProgress->id)->delete();
                    DB::table('game_progress')->where('id', $sp->id)->update(['user_id' => $target->id]);
                    $stats['game_progress_consolidated']++;
                } else {
                    DB::table('game_progress')->where('id', $sp->id)->delete();
                }
            }
        }

        // 3. Game correct answers (unique on user_id, game_id, card_id)
        $sourceAnswers = DB::table('game_correct_answers')->where('user_id', $source->id)->get();
        foreach ($sourceAnswers as $sa) {
            $exists = DB::table('game_correct_answers')
                ->where('user_id', $target->id)
                ->where('game_id', $sa->game_id)
                ->where('card_id', $sa->card_id)
                ->exists();

            if (! $exists) {
                DB::table('game_correct_answers')->where('id', $sa->id)->update(['user_id' => $target->id]);
            } else {
                DB::table('game_correct_answers')->where('id', $sa->id)->delete();
            }
        }

        // 4. Reward redemptions (unique on user_id, level)
        $sourceRedemptions = RewardRedemption::where('user_id', $source->id)->get();
        foreach ($sourceRedemptions as $sr) {
            $exists = RewardRedemption::where('user_id', $target->id)
                ->where('level', $sr->level)
                ->exists();

            if (! $exists) {
                $sr->update(['user_id' => $target->id]);
            } else {
                $sr->delete();
            }
        }

        if (! $target->equipped_badge && $source->equipped_badge) {
            $target->equipped_badge = $source->equipped_badge;
        }
        if (! $target->active_xp_booster && $source->active_xp_booster) {
            $target->active_xp_booster = true;
        }
        if (! $target->active_brain_shield && $source->active_brain_shield) {
            $target->active_brain_shield = true;
        }
        $target->save();
    }

    /**
     * Merge Grade 10 yearbook/milestone profile.
     */
    protected function mergeGrade10Profile(User $source, User $target): void
    {
        $sourceProfile = Grade10Profile::where('user_id', $source->id)->first();
        if (! $sourceProfile) {
            return;
        }

        $targetProfile = Grade10Profile::where('user_id', $target->id)->first();
        if (! $targetProfile) {
            $sourceProfile->update(['user_id' => $target->id]);
            return;
        }

        $fields = ['full_name', 'chinese_name', 'birthday', 'hometown', 'ambition', 'motto', 'message', 'social_media', 'photo_path'];
        $dirty = false;
        foreach ($fields as $field) {
            if (empty($targetProfile->$field) && ! empty($sourceProfile->$field)) {
                $targetProfile->$field = $sourceProfile->$field;
                $dirty = true;
            }
        }

        if ($dirty) {
            $targetProfile->save();
        }

        if ($sourceProfile->photo_path && $targetProfile->photo_path !== $sourceProfile->photo_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($sourceProfile->photo_path);
        }

        $sourceProfile->delete();
    }

    /**
     * Merge student_teacher associations for students.
     */
    protected function mergeStudentTeachers(User $source, User $target): void
    {
        $records = DB::table('student_teacher')->where('student_id', $source->id)->get();
        foreach ($records as $record) {
            $exists = DB::table('student_teacher')
                ->where('student_id', $target->id)
                ->where('teacher_id', $record->teacher_id)
                ->exists();

            if (! $exists) {
                DB::table('student_teacher')->where('id', $record->id)->update(['student_id' => $target->id]);
            } else {
                DB::table('student_teacher')->where('id', $record->id)->delete();
            }
        }
    }

    /**
     * Merge student_teacher associations for teachers.
     */
    protected function mergeTeacherStudents(User $source, User $target): void
    {
        $records = DB::table('student_teacher')->where('teacher_id', $source->id)->get();
        foreach ($records as $record) {
            $exists = DB::table('student_teacher')
                ->where('teacher_id', $target->id)
                ->where('student_id', $record->student_id)
                ->exists();

            if (! $exists) {
                DB::table('student_teacher')->where('id', $record->id)->update(['teacher_id' => $target->id]);
            } else {
                DB::table('student_teacher')->where('id', $record->id)->delete();
            }
        }
    }

    /**
     * Merge teacher-created activities, materials, and schedules.
     *
     * @param array<string, mixed> $stats
     */
    protected function mergeTeacherContent(User $source, User $target, array &$stats): void
    {
        $stats['activities_moved'] += (int) Quiz::where('teacher_id', $source->id)->update(['teacher_id' => $target->id]);
        $stats['activities_moved'] += (int) Seatwork::where('teacher_id', $source->id)->update(['teacher_id' => $target->id]);
        $stats['activities_moved'] += (int) Practical::where('teacher_id', $source->id)->update(['teacher_id' => $target->id]);
        $stats['activities_moved'] += (int) Exam::where('teacher_id', $source->id)->update(['teacher_id' => $target->id]);
        $stats['activities_moved'] += (int) Game::where('teacher_id', $source->id)->update(['teacher_id' => $target->id]);

        $stats['materials_moved'] += (int) TeachingMaterial::where('teacher_id', $source->id)->update(['teacher_id' => $target->id]);

        TeacherSchedule::where('teacher_id', $source->id)->update(['teacher_id' => $target->id]);
        Meeting::where('user_id', $source->id)->update(['user_id' => $target->id]);
    }

    /**
     * Merge Messenger conversations, participants, and sent messages.
     *
     * @param array<string, mixed> $stats
     */
    protected function mergeMessenger(User $source, User $target, array &$stats): void
    {
        // Re-attribute messages sent by source to target
        $stats['messages_moved'] = (int) Message::where('sender_id', $source->id)
            ->update(['sender_id' => $target->id]);

        $sourceParticipants = DB::table('conversation_participants')
            ->where('user_id', $source->id)
            ->get();

        foreach ($sourceParticipants as $sp) {
            $conv = Conversation::find($sp->conversation_id);
            if (! $conv) {
                DB::table('conversation_participants')->where('id', $sp->id)->delete();
                continue;
            }

            // If target already participates in this conversation, remove duplicate participant row
            $targetAlreadyInConv = DB::table('conversation_participants')
                ->where('conversation_id', $conv->id)
                ->where('user_id', $target->id)
                ->exists();

            if ($targetAlreadyInConv) {
                DB::table('conversation_participants')->where('id', $sp->id)->delete();
                $stats['conversations_consolidated']++;
                continue;
            }

            // For 1-on-1 conversations, check if target already has an existing 1-on-1 with that user
            if (! $conv->is_group) {
                $otherParticipant = DB::table('conversation_participants')
                    ->where('conversation_id', $conv->id)
                    ->where('user_id', '!=', $source->id)
                    ->first();

                if ($otherParticipant) {
                    $otherUserId = $otherParticipant->user_id;

                    $existingTargetConv = Conversation::where('is_group', false)
                        ->whereHas('participants', fn ($q) => $q->where('user_id', $target->id))
                        ->whereHas('participants', fn ($q) => $q->where('user_id', $otherUserId))
                        ->first();

                    if ($existingTargetConv) {
                        // Transfer messages from old 1-on-1 into the existing conversation
                        Message::where('conversation_id', $conv->id)
                            ->update(['conversation_id' => $existingTargetConv->id]);

                        DB::table('conversation_participants')->where('conversation_id', $conv->id)->delete();
                        $conv->delete();

                        $stats['conversations_consolidated']++;
                        continue;
                    }
                }
            }

            // Transfer participant entry to target
            DB::table('conversation_participants')
                ->where('id', $sp->id)
                ->update(['user_id' => $target->id]);

            $stats['conversations_consolidated']++;
        }
    }

    /**
     * Reassign passkeys from source to target.
     */
    protected function mergePasskeys(User $source, User $target): void
    {
        if (DB::getSchemaBuilder()->hasTable('passkeys')) {
            DB::table('passkeys')->where('user_id', $source->id)->update(['user_id' => $target->id]);
        }
    }
}
