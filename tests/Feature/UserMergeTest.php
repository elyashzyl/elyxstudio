<?php

use App\Models\Conversation;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Practical;
use App\Models\PracticalAttempt;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Seatwork;
use App\Models\SeatworkAttempt;
use App\Models\StudentPoint;
use App\Models\TeachingMaterial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->superadmin = User::factory()->create([
        'role' => 'superadmin',
        'email_verified_at' => now(),
    ]);

    $this->teacher = User::factory()->create([
        'role' => 'teacher',
        'email_verified_at' => now(),
    ]);
});

test('superadmin can merge duplicate student accounts and consolidate all attempts and points', function () {
    $source = User::factory()->create([
        'role' => 'student',
        'grade' => 'Grade 10',
        'email_verified_at' => now(),
    ]);

    $target = User::factory()->create([
        'role' => 'student',
        'grade' => 'Grade 10',
        'email_verified_at' => now(),
        'total_points' => 0,
    ]);

    $quiz = Quiz::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'Sample Quiz',
        'grade' => 'Grade 10',
        'is_published' => true,
    ]);

    $seatwork = Seatwork::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'Sample Seatwork',
        'grade' => 'Grade 10',
        'is_published' => true,
    ]);

    $practical = Practical::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'Sample Practical',
        'grade' => 'Grade 10',
        'is_published' => true,
    ]);

    $exam = Exam::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'Sample Exam',
        'grade' => 'Grade 10',
        'is_published' => true,
    ]);

    QuizAttempt::create([
        'quiz_id' => $quiz->id,
        'student_id' => $source->id,
        'score' => 20,
        'total_points' => 20,
        'status' => 'completed',
        'submitted_at' => now(),
    ]);

    SeatworkAttempt::create([
        'seatwork_id' => $seatwork->id,
        'student_id' => $source->id,
        'score' => 15,
        'total_points' => 15,
        'status' => 'completed',
        'submitted_at' => now(),
    ]);

    PracticalAttempt::create([
        'practical_id' => $practical->id,
        'student_id' => $source->id,
        'total_score' => 85,
        'status' => 'completed',
        'submitted_at' => now(),
    ]);

    ExamAttempt::create([
        'exam_id' => $exam->id,
        'student_id' => $source->id,
        'total_score' => 90,
        'status' => 'completed',
        'submitted_at' => now(),
    ]);

    StudentPoint::create([
        'student_id' => $source->id,
        'activity_type' => 'quiz',
        'activity_id' => $quiz->id,
        'points' => 50,
    ]);

    $response = $this->actingAs($this->superadmin)->post(route('admin.users.merge'), [
        'source_id' => $source->id,
        'target_id' => $target->id,
    ]);

    $response->assertSessionHas('success');
    $response->assertRedirect();

    // Source account deleted
    $this->assertDatabaseMissing('users', ['id' => $source->id]);
    $this->assertDatabaseHas('users', ['id' => $target->id]);

    // Attempts re-assigned to target
    $this->assertDatabaseHas('quiz_attempts', ['quiz_id' => $quiz->id, 'student_id' => $target->id]);
    $this->assertDatabaseHas('seatwork_attempts', ['seatwork_id' => $seatwork->id, 'student_id' => $target->id]);
    $this->assertDatabaseHas('practical_attempts', ['practical_id' => $practical->id, 'student_id' => $target->id]);
    $this->assertDatabaseHas('exam_attempts', ['exam_id' => $exam->id, 'student_id' => $target->id]);

    // Points consolidated and target total_points recalculated
    $this->assertDatabaseHas('student_points', ['student_id' => $target->id, 'points' => 50]);
    $target->refresh();
    expect($target->total_points)->toBe(50);
});

test('duplicate quiz attempt resolves by preserving the higher scoring attempt', function () {
    $source = User::factory()->create(['role' => 'student']);
    $target = User::factory()->create(['role' => 'student']);

    $quiz = Quiz::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'Conflict Quiz',
        'grade' => 'Grade 10',
        'is_published' => true,
    ]);

    // Source scored higher (95) than target (60)
    QuizAttempt::create([
        'quiz_id' => $quiz->id,
        'student_id' => $source->id,
        'score' => 95,
        'total_points' => 100,
        'status' => 'completed',
        'submitted_at' => now(),
    ]);

    QuizAttempt::create([
        'quiz_id' => $quiz->id,
        'student_id' => $target->id,
        'score' => 60,
        'total_points' => 100,
        'status' => 'completed',
        'submitted_at' => now(),
    ]);

    $response = $this->actingAs($this->superadmin)->post(route('admin.users.merge'), [
        'source_id' => $source->id,
        'target_id' => $target->id,
    ]);

    $response->assertSessionHas('success');

    // Only one attempt remains for target with the higher score (95)
    expect(QuizAttempt::where('quiz_id', $quiz->id)->where('student_id', $target->id)->count())->toBe(1);
    expect(QuizAttempt::where('quiz_id', $quiz->id)->where('student_id', $target->id)->first()->score)->toBe(95);
});

test('duplicate student points for identical activities are deduplicated to prevent inflated XP', function () {
    $source = User::factory()->create(['role' => 'student']);
    $target = User::factory()->create(['role' => 'student']);

    // Both earned points for quiz #10
    StudentPoint::create([
        'student_id' => $source->id,
        'activity_type' => 'quiz',
        'activity_id' => 10,
        'points' => 40,
    ]);

    StudentPoint::create([
        'student_id' => $target->id,
        'activity_type' => 'quiz',
        'activity_id' => 10,
        'points' => 20,
    ]);

    $this->actingAs($this->superadmin)->post(route('admin.users.merge'), [
        'source_id' => $source->id,
        'target_id' => $target->id,
    ]);

    // Deduplicated to single record with max points (40)
    $records = StudentPoint::where('student_id', $target->id)->where('activity_type', 'quiz')->where('activity_id', 10)->get();
    expect($records)->toHaveCount(1);
    expect($records->first()->points)->toBe(40);

    $target->refresh();
    expect($target->total_points)->toBe(40);
});

test('superadmin can merge teacher accounts and transfer created teaching resources', function () {
    $sourceTeacher = User::factory()->create(['role' => 'teacher']);
    $targetTeacher = User::factory()->create(['role' => 'teacher']);

    $material = TeachingMaterial::create([
        'teacher_id' => $sourceTeacher->id,
        'title' => 'Physics Slide Deck',
        'subject' => 'Science',
        'file_path' => 'materials/physics.pdf',
        'file_name' => 'physics.pdf',
        'file_size' => 1024,
        'file_type' => 'pdf',
    ]);

    $quiz = Quiz::create([
        'teacher_id' => $sourceTeacher->id,
        'title' => 'Mechanics Quiz',
        'grade' => 'Grade 10',
        'is_published' => true,
    ]);

    $response = $this->actingAs($this->superadmin)->post(route('admin.users.merge'), [
        'source_id' => $sourceTeacher->id,
        'target_id' => $targetTeacher->id,
    ]);

    $response->assertSessionHas('success');

    $this->assertDatabaseMissing('users', ['id' => $sourceTeacher->id]);
    $this->assertDatabaseHas('teaching_materials', ['id' => $material->id, 'teacher_id' => $targetTeacher->id]);
    $this->assertDatabaseHas('quizzes', ['id' => $quiz->id, 'teacher_id' => $targetTeacher->id]);
});

test('messenger messages and conversation participants are safely consolidated', function () {
    $sourceStudent = User::factory()->create(['role' => 'student']);
    $targetStudent = User::factory()->create(['role' => 'student']);

    // Conversation between sourceStudent and teacher
    $conv = Conversation::create(['is_group' => false]);
    DB::table('conversation_participants')->insert([
        ['conversation_id' => $conv->id, 'user_id' => $sourceStudent->id, 'created_at' => now(), 'updated_at' => now()],
        ['conversation_id' => $conv->id, 'user_id' => $this->teacher->id, 'created_at' => now(), 'updated_at' => now()],
    ]);

    $msg = DB::table('messages')->insertGetId([
        'conversation_id' => $conv->id,
        'sender_id' => $sourceStudent->id,
        'body' => 'Hello teacher from source account',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($this->superadmin)->post(route('admin.users.merge'), [
        'source_id' => $sourceStudent->id,
        'target_id' => $targetStudent->id,
    ]);

    // Sent message sender_id transferred to target
    $this->assertDatabaseHas('messages', ['id' => $msg, 'sender_id' => $targetStudent->id]);

    // Target is now participant
    $this->assertDatabaseHas('conversation_participants', ['conversation_id' => $conv->id, 'user_id' => $targetStudent->id]);
    $this->assertDatabaseMissing('conversation_participants', ['conversation_id' => $conv->id, 'user_id' => $sourceStudent->id]);
});

test('non-superadmin cannot merge users', function () {
    $source = User::factory()->create(['role' => 'student']);
    $target = User::factory()->create(['role' => 'student']);

    $response = $this->actingAs($this->teacher)->post(route('admin.users.merge'), [
        'source_id' => $source->id,
        'target_id' => $target->id,
    ]);

    $response->assertForbidden();
});

test('cannot merge accounts with different roles', function () {
    $sourceStudent = User::factory()->create(['role' => 'student']);
    $targetTeacher = User::factory()->create(['role' => 'teacher']);

    $response = $this->actingAs($this->superadmin)->post(route('admin.users.merge'), [
        'source_id' => $sourceStudent->id,
        'target_id' => $targetTeacher->id,
    ]);

    $response->assertSessionHas('error');
    $this->assertDatabaseHas('users', ['id' => $sourceStudent->id]);
});

test('cannot merge a user into themselves', function () {
    $student = User::factory()->create(['role' => 'student']);

    $response = $this->actingAs($this->superadmin)->post(route('admin.users.merge'), [
        'source_id' => $student->id,
        'target_id' => $student->id,
    ]);

    $response->assertSessionHasErrors(['target_id']);
});

test('superadmin cannot merge their own active account as source', function () {
    $otherAdmin = User::factory()->create(['role' => 'superadmin']);

    $response = $this->actingAs($this->superadmin)->post(route('admin.users.merge'), [
        'source_id' => $this->superadmin->id,
        'target_id' => $otherAdmin->id,
    ]);

    $response->assertForbidden();
});
