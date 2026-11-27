<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('users.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('chat.conversation.{conversationId}', function ($user, $conversationId) {
    $conversation = \App\Models\Conversation::find($conversationId);
    if (! $conversation) {
        return false;
    }

    if (! $conversation->participants()->where('user_id', $user->id)->exists() && ! $user->isSuperadmin()) {
        return false;
    }

    return [
        'id' => (int) $user->id,
        'name' => (string) $user->name,
        'role' => (string) $user->role,
    ];
});

Broadcast::channel('chat.user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('exam.attempt.{attemptId}', function ($user, $attemptId) {
    $attempt = \App\Models\ExamAttempt::with('exam')->find($attemptId);
    if (! $attempt) {
        return false;
    }

    if ((int) $user->id === (int) $attempt->student_id) {
        return true;
    }

    if ($attempt->exam && (int) $user->id === (int) $attempt->exam->teacher_id) {
        return true;
    }

    return $user->isSuperadmin();
});

Broadcast::channel('exam.{examId}', function ($user, $examId) {
    $exam = \App\Models\Exam::with('gradeLevels')->find($examId);
    if (! $exam) {
        return false;
    }

    if ((int) $user->id === (int) $exam->teacher_id || $user->isSuperadmin()) {
        return true;
    }

    if ($user->role === 'student') {
        if (!empty($exam->grade) && (int) $user->grade_level === (int) $exam->grade) {
            return true;
        }
        if ($exam->gradeLevels->contains('id', $user->grade_level_id)) {
            return true;
        }
        if (empty($exam->grade) && $exam->gradeLevels->isEmpty()) {
            return true;
        }
    }

    return false;
});

Broadcast::channel('game.room.{pin}', function ($user, $pin) {
    $room = \App\Models\GameRoom::where('pin', $pin)->first();
    if (! $room) {
        return false;
    }

    if ($user->isStudent() && ! $room->isAccessibleToStudent($user)) {
        return false;
    }

    return [
        'id' => (int) $user->id,
        'name' => (string) $user->name,
        'role' => (string) $user->role,
        'avatar' => $user->avatar_url ?? null,
    ];
});
