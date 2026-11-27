<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Snapshot of the database schema as at 2026-09-15.
 * The messages table and framework-infra tables (sessions, cache, jobs,
 * failed_jobs, job_batches, cache_locks, password_reset_tokens, migrations)
 * are intentionally not included.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('attendance_records')) {
            Schema::create('attendance_records', function (Blueprint $table) {
                $table->string('id');
                $table->string('date');
                $table->string('grade');
                $table->string('section');
                $table->string('adviser');
                $table->string('created_by')->nullable();
                $table->string('created_by_name')->nullable();
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->primary(['id']);
            });
        }

        if (! Schema::hasTable('attendance_entries')) {
            Schema::create('attendance_entries', function (Blueprint $table) {
                $table->id();
                $table->integer('record_id');
                $table->string('student_id');
                $table->string('name');
                $table->string('am1')->nullable();
                $table->string('am2')->nullable();
                $table->string('am3')->nullable();
                $table->string('am4')->nullable();
                $table->string('am5')->nullable();
                $table->string('am6')->nullable();
                $table->string('pm1')->nullable();
                $table->string('pm2')->nullable();
                $table->string('pm3')->nullable();
                $table->string('pm4')->nullable();
                $table->text('reason')->nullable();
                $table->boolean('excused')->default('0');
                $table->boolean('unexcused')->default('0');
                $table->boolean('nls')->default('0');
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->foreign('record_id')->references('id')->on('attendance_records')->onDelete('cascade');
            });
        }

        if (! Schema::hasTable('calendar_events')) {
            Schema::create('calendar_events', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('type');
                $table->string('event_date');
                $table->string('color')->nullable();
                $table->string('created_by')->nullable();
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
            });
        }

        if (! Schema::hasTable('grade_levels')) {
            Schema::create('grade_levels', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->integer('display_order')->default('0');
                $table->boolean('is_active')->default('1');
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
            });
        }

        if (! Schema::hasTable('sections')) {
            Schema::create('sections', function (Blueprint $table) {
                $table->id();
                $table->integer('grade_level_id');
                $table->string('name');
                $table->boolean('is_active')->default('1');
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->foreign('grade_level_id')->references('id')->on('grade_levels')->onDelete('cascade');
                $table->unique(['grade_level_id', 'name']);
            });
        }

        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email');
                $table->dateTime('email_verified_at')->nullable();
                $table->string('password');
                $table->string('remember_token')->nullable();
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->text('two_factor_secret')->nullable();
                $table->text('two_factor_recovery_codes')->nullable();
                $table->dateTime('two_factor_confirmed_at')->nullable();
                $table->string('role')->default('student');
                $table->string('grade')->nullable();
                $table->integer('section_id')->nullable();
                $table->integer('grade_level_id')->nullable();
                $table->string('gender')->nullable();
                $table->integer('total_points')->default('0');
                $table->dateTime('notifications_read_at')->nullable();
                $table->boolean('messenger_enabled')->default('1');
                $table->dateTime('last_login_at')->nullable();
                $table->foreign('section_id')->references('id')->on('sections')->onDelete('set null');
                $table->foreign('grade_level_id')->references('id')->on('grade_levels')->onDelete('set null');
                $table->unique(['email']);
            });
        }

        if (! Schema::hasTable('conversations')) {
            Schema::create('conversations', function (Blueprint $table) {
                $table->id();
                $table->string('subject')->nullable();
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
            });
        }

        if (! Schema::hasTable('conversation_participants')) {
            Schema::create('conversation_participants', function (Blueprint $table) {
                $table->id();
                $table->integer('conversation_id');
                $table->integer('user_id');
                $table->dateTime('last_read_at')->nullable();
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->dateTime('archived_at')->nullable();
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('conversation_id')->references('id')->on('conversations')->onDelete('cascade');
                $table->unique(['conversation_id', 'user_id']);
            });
        }

        if (! Schema::hasTable('exams')) {
            Schema::create('exams', function (Blueprint $table) {
                $table->id();
                $table->integer('teacher_id');
                $table->string('title');
                $table->text('instructions')->nullable();
                $table->integer('time_limit_minutes')->nullable();
                $table->integer('max_score')->default('100');
                $table->boolean('is_published')->default('0');
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->string('grade')->nullable();
                $table->dateTime('closes_at')->nullable();
                $table->foreign('teacher_id')->references('id')->on('users')->onDelete('cascade');
            });
        }

        if (! Schema::hasTable('exam_sections')) {
            Schema::create('exam_sections', function (Blueprint $table) {
                $table->id();
                $table->integer('exam_id');
                $table->string('section_type');
                $table->string('title');
                $table->text('instructions')->nullable();
                $table->integer('order')->default('0');
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->foreign('exam_id')->references('id')->on('exams')->onDelete('cascade');
            });
        }

        if (! Schema::hasTable('exam_questions')) {
            Schema::create('exam_questions', function (Blueprint $table) {
                $table->id();
                $table->integer('exam_section_id');
                $table->text('question_text');
                $table->string('type');
                $table->integer('points')->default('1');
                $table->integer('order')->default('0');
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->foreign('exam_section_id')->references('id')->on('exam_sections')->onDelete('cascade');
            });
        }

        if (! Schema::hasTable('exam_options')) {
            Schema::create('exam_options', function (Blueprint $table) {
                $table->id();
                $table->integer('exam_question_id');
                $table->string('option_text');
                $table->boolean('is_correct')->default('0');
                $table->integer('order')->default('0');
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->foreign('exam_question_id')->references('id')->on('exam_questions')->onDelete('cascade');
            });
        }

        if (! Schema::hasTable('exam_attempts')) {
            Schema::create('exam_attempts', function (Blueprint $table) {
                $table->id();
                $table->integer('exam_id');
                $table->integer('student_id');
                $table->string('status')->default('in_progress');
                $table->integer('total_score')->nullable();
                $table->dateTime('started_at')->nullable();
                $table->dateTime('submitted_at')->nullable();
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->foreign('student_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('exam_id')->references('id')->on('exams')->onDelete('cascade');
            });
        }

        if (! Schema::hasTable('exam_answers')) {
            Schema::create('exam_answers', function (Blueprint $table) {
                $table->id();
                $table->integer('exam_attempt_id');
                $table->integer('exam_question_id');
                $table->text('answer_text')->nullable();
                $table->integer('selected_option_id')->nullable();
                $table->text('matching_answers')->nullable();
                $table->boolean('is_correct')->nullable();
                $table->integer('points_earned')->nullable();
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->foreign('selected_option_id')->references('id')->on('exam_options')->onDelete('set null');
                $table->foreign('exam_question_id')->references('id')->on('exam_questions')->onDelete('cascade');
                $table->foreign('exam_attempt_id')->references('id')->on('exam_attempts')->onDelete('cascade');
                $table->unique(['exam_attempt_id', 'exam_question_id']);
            });
        }

        if (! Schema::hasTable('exam_criteria')) {
            Schema::create('exam_criteria', function (Blueprint $table) {
                $table->id();
                $table->integer('exam_section_id');
                $table->string('criterion_name');
                $table->text('description')->nullable();
                $table->integer('max_points')->default('10');
                $table->integer('order')->default('0');
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->foreign('exam_section_id')->references('id')->on('exam_sections')->onDelete('cascade');
            });
        }

        if (! Schema::hasTable('exam_grade_level')) {
            Schema::create('exam_grade_level', function (Blueprint $table) {
                $table->integer('exam_id');
                $table->integer('grade_level_id');
                $table->foreign('grade_level_id')->references('id')->on('grade_levels')->onDelete('cascade');
                $table->foreign('exam_id')->references('id')->on('exams')->onDelete('cascade');
                $table->unique(['exam_id', 'grade_level_id']);
            });
        }

        if (! Schema::hasTable('exam_matching_pairs')) {
            Schema::create('exam_matching_pairs', function (Blueprint $table) {
                $table->id();
                $table->integer('exam_question_id');
                $table->string('left_text');
                $table->string('right_text');
                $table->integer('order')->default('0');
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->foreign('exam_question_id')->references('id')->on('exam_questions')->onDelete('cascade');
            });
        }

        if (! Schema::hasTable('exam_section_scores')) {
            Schema::create('exam_section_scores', function (Blueprint $table) {
                $table->id();
                $table->integer('exam_attempt_id');
                $table->integer('exam_criterion_id');
                $table->integer('exam_section_id');
                $table->integer('score')->default('0');
                $table->text('comment')->nullable();
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->foreign('exam_section_id')->references('id')->on('exam_sections')->onDelete('cascade');
                $table->foreign('exam_criterion_id')->references('id')->on('exam_criteria')->onDelete('cascade');
                $table->foreign('exam_attempt_id')->references('id')->on('exam_attempts')->onDelete('cascade');
                $table->unique(['exam_attempt_id', 'exam_criterion_id']);
            });
        }

        if (! Schema::hasTable('games')) {
            Schema::create('games', function (Blueprint $table) {
                $table->id();
                $table->integer('teacher_id');
                $table->string('title');
                $table->string('subject');
                $table->string('grade')->nullable();
                $table->text('description')->nullable();
                $table->integer('xp_reward')->default('10');
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->string('type')->default('flashcard');
                $table->boolean('hidden')->default('0');
                $table->foreign('teacher_id')->references('id')->on('users')->onDelete('cascade');
            });
        }

        if (! Schema::hasTable('game_cards')) {
            Schema::create('game_cards', function (Blueprint $table) {
                $table->id();
                $table->integer('game_id');
                $table->text('question');
                $table->text('answer');
                $table->integer('order')->default('0');
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->text('options')->nullable();
                $table->string('color')->nullable();
                $table->foreign('game_id')->references('id')->on('games')->onDelete('cascade');
            });
        }

        if (! Schema::hasTable('game_correct_answers')) {
            Schema::create('game_correct_answers', function (Blueprint $table) {
                $table->id();
                $table->integer('user_id');
                $table->integer('game_id');
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->integer('card_id')->nullable();
                $table->foreign('game_id')->references('id')->on('games')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                // skipped UNIQUE index game_correct_answers_user_id_game_id_card_id_unique (data has duplicates)
            });
        }

        if (! Schema::hasTable('game_progress')) {
            Schema::create('game_progress', function (Blueprint $table) {
                $table->id();
                $table->integer('user_id');
                $table->integer('game_id');
                $table->integer('current_card')->default('0');
                $table->integer('done_count')->default('0');
                $table->integer('score')->default('0');
                $table->boolean('completed')->default('0');
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->text('card_order')->nullable();
                $table->text('graded')->nullable();
                $table->foreign('game_id')->references('id')->on('games')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->unique(['user_id', 'game_id']);
            });
        }

        if (! Schema::hasTable('meetings')) {
            Schema::create('meetings', function (Blueprint $table) {
                $table->id();
                $table->integer('user_id');
                $table->string('title')->nullable();
                $table->string('room');
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->string('password')->nullable();
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->unique(['room']);
            });
        }

        if (! Schema::hasTable('monthly_records')) {
            Schema::create('monthly_records', function (Blueprint $table) {
                $table->string('id');
                $table->integer('month');
                $table->integer('year');
                $table->string('grade');
                $table->string('section');
                $table->string('adviser')->nullable();
                $table->string('school_head')->nullable();
                $table->text('summary_data')->nullable();
                $table->text('excluded_dates')->nullable();
                $table->string('created_by')->nullable();
                $table->string('created_by_name')->nullable();
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->primary(['id']);
            });
        }

        if (! Schema::hasTable('monthly_entries')) {
            Schema::create('monthly_entries', function (Blueprint $table) {
                $table->id();
                $table->integer('record_id');
                $table->string('student_id');
                $table->string('student_name');
                $table->text('days')->nullable();
                $table->integer('present')->default('0');
                $table->integer('absent')->default('0');
                $table->integer('tardy')->default('0');
                $table->text('remarks')->nullable();
                $table->boolean('late_enrollee')->default('0');
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->foreign('record_id')->references('id')->on('monthly_records')->onDelete('cascade');
            });
        }

        if (! Schema::hasTable('passkeys')) {
            Schema::create('passkeys', function (Blueprint $table) {
                $table->id();
                $table->integer('user_id');
                $table->string('name');
                $table->string('credential_id');
                $table->text('credential');
                $table->dateTime('last_used_at')->nullable();
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->unique(['credential_id']);
                $table->index(['user_id']);
            });
        }

        if (! Schema::hasTable('practicals')) {
            Schema::create('practicals', function (Blueprint $table) {
                $table->id();
                $table->integer('teacher_id');
                $table->string('title');
                $table->text('instructions')->nullable();
                $table->integer('time_limit_minutes')->nullable();
                $table->integer('max_score')->default('100');
                $table->boolean('is_published')->default('0');
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->string('grade')->nullable();
                $table->integer('max_attempts')->default('3');
                $table->dateTime('closes_at')->nullable();
                $table->foreign('teacher_id')->references('id')->on('users')->onDelete('cascade');
            });
        }

        if (! Schema::hasTable('practical_attempts')) {
            Schema::create('practical_attempts', function (Blueprint $table) {
                $table->id();
                $table->integer('practical_id');
                $table->integer('student_id');
                $table->string('status')->default('in_progress');
                $table->integer('total_score')->nullable();
                $table->dateTime('started_at')->nullable();
                $table->dateTime('submitted_at')->nullable();
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->text('submission_text')->nullable();
                $table->string('submission_file')->nullable();
                $table->dateTime('closed_at')->nullable();
                $table->foreign('student_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('practical_id')->references('id')->on('practicals')->onDelete('cascade');
            });
        }

        if (! Schema::hasTable('practical_criteria')) {
            Schema::create('practical_criteria', function (Blueprint $table) {
                $table->id();
                $table->integer('practical_id');
                $table->string('criterion_name');
                $table->text('description')->nullable();
                $table->integer('max_points')->default('10');
                $table->integer('order')->default('0');
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->foreign('practical_id')->references('id')->on('practicals')->onDelete('cascade');
            });
        }

        if (! Schema::hasTable('practical_grade_level')) {
            Schema::create('practical_grade_level', function (Blueprint $table) {
                $table->integer('practical_id');
                $table->integer('grade_level_id');
                $table->foreign('grade_level_id')->references('id')->on('grade_levels')->onDelete('cascade');
                $table->foreign('practical_id')->references('id')->on('practicals')->onDelete('cascade');
                $table->unique(['practical_id', 'grade_level_id']);
            });
        }

        if (! Schema::hasTable('practical_scores')) {
            Schema::create('practical_scores', function (Blueprint $table) {
                $table->id();
                $table->integer('attempt_id');
                $table->integer('criterion_id');
                $table->integer('score')->default('0');
                $table->text('comment')->nullable();
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->foreign('criterion_id')->references('id')->on('practical_criteria')->onDelete('cascade');
                $table->foreign('attempt_id')->references('id')->on('practical_attempts')->onDelete('cascade');
            });
        }

        if (! Schema::hasTable('quarterly_events')) {
            Schema::create('quarterly_events', function (Blueprint $table) {
                $table->id();
                $table->string('event_name');
                $table->string('first_grading')->nullable();
                $table->string('second_grading')->nullable();
                $table->string('third_grading')->nullable();
                $table->string('fourth_grading')->nullable();
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
            });
        }

        if (! Schema::hasTable('quizzes')) {
            Schema::create('quizzes', function (Blueprint $table) {
                $table->id();
                $table->integer('teacher_id');
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('grade');
                $table->integer('time_limit_minutes')->nullable();
                $table->boolean('is_published')->default('0');
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->integer('section_id')->nullable();
                $table->dateTime('closes_at')->nullable();
                $table->foreign('section_id')->references('id')->on('sections')->onDelete('set null');
                $table->foreign('teacher_id')->references('id')->on('users')->onDelete('cascade');
            });
        }

        if (! Schema::hasTable('questions')) {
            Schema::create('questions', function (Blueprint $table) {
                $table->id();
                $table->integer('quiz_id');
                $table->text('question_text');
                $table->string('type')->default('multiple_choice');
                $table->integer('points')->default('1');
                $table->integer('order')->default('0');
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->foreign('quiz_id')->references('id')->on('quizzes')->onDelete('cascade');
            });
        }

        if (! Schema::hasTable('quiz_options')) {
            Schema::create('quiz_options', function (Blueprint $table) {
                $table->id();
                $table->integer('question_id');
                $table->string('option_text');
                $table->boolean('is_correct')->default('0');
                $table->integer('order')->default('0');
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->foreign('question_id')->references('id')->on('questions')->onDelete('cascade');
            });
        }

        if (! Schema::hasTable('quiz_attempts')) {
            Schema::create('quiz_attempts', function (Blueprint $table) {
                $table->id();
                $table->integer('quiz_id');
                $table->integer('student_id');
                $table->integer('score')->default('0');
                $table->integer('total_points')->default('0');
                $table->string('status')->default('in_progress');
                $table->dateTime('started_at')->nullable();
                $table->dateTime('submitted_at')->nullable();
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->foreign('student_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('quiz_id')->references('id')->on('quizzes')->onDelete('cascade');
                $table->unique(['quiz_id', 'student_id']);
            });
        }

        if (! Schema::hasTable('quiz_answers')) {
            Schema::create('quiz_answers', function (Blueprint $table) {
                $table->id();
                $table->integer('quiz_attempt_id');
                $table->integer('question_id');
                $table->integer('quiz_option_id')->nullable();
                $table->boolean('is_correct')->default('0');
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->foreign('quiz_option_id')->references('id')->on('quiz_options')->onDelete('set null');
                $table->foreign('question_id')->references('id')->on('questions')->onDelete('cascade');
                $table->foreign('quiz_attempt_id')->references('id')->on('quiz_attempts')->onDelete('cascade');
                $table->unique(['quiz_attempt_id', 'question_id']);
            });
        }

        if (! Schema::hasTable('quiz_grade_level')) {
            Schema::create('quiz_grade_level', function (Blueprint $table) {
                $table->integer('quiz_id');
                $table->integer('grade_level_id');
                $table->foreign('grade_level_id')->references('id')->on('grade_levels')->onDelete('cascade');
                $table->foreign('quiz_id')->references('id')->on('quizzes')->onDelete('cascade');
                $table->unique(['quiz_id', 'grade_level_id']);
            });
        }

        if (! Schema::hasTable('quiz_section')) {
            Schema::create('quiz_section', function (Blueprint $table) {
                $table->integer('quiz_id');
                $table->integer('section_id');
                $table->foreign('section_id')->references('id')->on('sections')->onDelete('cascade');
                $table->foreign('quiz_id')->references('id')->on('quizzes')->onDelete('cascade');
                $table->unique(['quiz_id', 'section_id']);
            });
        }

        if (! Schema::hasTable('seatworks')) {
            Schema::create('seatworks', function (Blueprint $table) {
                $table->id();
                $table->integer('teacher_id');
                $table->string('title');
                $table->text('instructions')->nullable();
                $table->string('grade')->nullable();
                $table->integer('time_limit_minutes')->nullable();
                $table->boolean('is_published')->default('0');
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->dateTime('closes_at')->nullable();
                $table->foreign('teacher_id')->references('id')->on('users')->onDelete('cascade');
            });
        }

        if (! Schema::hasTable('seatwork_questions')) {
            Schema::create('seatwork_questions', function (Blueprint $table) {
                $table->id();
                $table->integer('seatwork_id');
                $table->text('question_text');
                $table->string('type');
                $table->integer('points')->default('1');
                $table->integer('order')->default('0');
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->foreign('seatwork_id')->references('id')->on('seatworks')->onDelete('cascade');
            });
        }

        if (! Schema::hasTable('seatwork_options')) {
            Schema::create('seatwork_options', function (Blueprint $table) {
                $table->id();
                $table->integer('question_id');
                $table->string('option_text');
                $table->boolean('is_correct')->default('0');
                $table->integer('order')->default('0');
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->foreign('question_id')->references('id')->on('seatwork_questions')->onDelete('cascade');
            });
        }

        if (! Schema::hasTable('seatwork_attempts')) {
            Schema::create('seatwork_attempts', function (Blueprint $table) {
                $table->id();
                $table->integer('seatwork_id');
                $table->integer('student_id');
                $table->string('status')->default('in_progress');
                $table->integer('score')->nullable();
                $table->integer('total_points')->nullable();
                $table->dateTime('started_at')->nullable();
                $table->dateTime('submitted_at')->nullable();
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->foreign('student_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('seatwork_id')->references('id')->on('seatworks')->onDelete('cascade');
            });
        }

        if (! Schema::hasTable('seatwork_answers')) {
            Schema::create('seatwork_answers', function (Blueprint $table) {
                $table->id();
                $table->integer('attempt_id');
                $table->integer('question_id');
                $table->text('answer_text')->nullable();
                $table->integer('selected_option_id')->nullable();
                $table->text('matching_answers')->nullable();
                $table->boolean('is_correct')->default('0');
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->foreign('selected_option_id')->references('id')->on('seatwork_options')->onDelete('set null');
                $table->foreign('question_id')->references('id')->on('seatwork_questions')->onDelete('cascade');
                $table->foreign('attempt_id')->references('id')->on('seatwork_attempts')->onDelete('cascade');
            });
        }

        if (! Schema::hasTable('seatwork_grade_level')) {
            Schema::create('seatwork_grade_level', function (Blueprint $table) {
                $table->integer('seatwork_id');
                $table->integer('grade_level_id');
                $table->foreign('grade_level_id')->references('id')->on('grade_levels')->onDelete('cascade');
                $table->foreign('seatwork_id')->references('id')->on('seatworks')->onDelete('cascade');
                $table->unique(['seatwork_id', 'grade_level_id']);
            });
        }

        if (! Schema::hasTable('seatwork_matching_pairs')) {
            Schema::create('seatwork_matching_pairs', function (Blueprint $table) {
                $table->id();
                $table->integer('question_id');
                $table->string('left_text');
                $table->string('right_text');
                $table->integer('order')->default('0');
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->foreign('question_id')->references('id')->on('seatwork_questions')->onDelete('cascade');
            });
        }

        if (! Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table) {
                $table->string('key');
                $table->text('value')->nullable();
                $table->primary(['key']);
            });
        }

        if (! Schema::hasTable('student_points')) {
            Schema::create('student_points', function (Blueprint $table) {
                $table->id();
                $table->integer('student_id');
                $table->string('activity_type');
                $table->integer('activity_id');
                $table->integer('points');
                $table->string('reason')->nullable();
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->integer('score')->nullable();
                $table->integer('total')->nullable();
                $table->foreign('student_id')->references('id')->on('users')->onDelete('cascade');
            });
        }

        if (! Schema::hasTable('student_teacher')) {
            Schema::create('student_teacher', function (Blueprint $table) {
                $table->id();
                $table->integer('student_id');
                $table->integer('teacher_id');
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->foreign('teacher_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('student_id')->references('id')->on('users')->onDelete('cascade');
                $table->unique(['student_id', 'teacher_id']);
            });
        }

        if (! Schema::hasTable('students')) {
            Schema::create('students', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('grade');
                $table->string('section');
                $table->string('gender')->default('');
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
            });
        }

        if (! Schema::hasTable('teacher_schedules')) {
            Schema::create('teacher_schedules', function (Blueprint $table) {
                $table->id();
                $table->integer('teacher_id');
                $table->string('day_of_week');
                $table->string('period');
                $table->string('start_time')->nullable();
                $table->string('end_time')->nullable();
                $table->string('subject')->nullable();
                $table->string('grade')->nullable();
                $table->string('section')->nullable();
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->foreign('teacher_id')->references('id')->on('users')->onDelete('cascade');
            });
        }

        if (! Schema::hasTable('teaching_materials')) {
            Schema::create('teaching_materials', function (Blueprint $table) {
                $table->id();
                $table->integer('teacher_id');
                $table->string('title');
                $table->string('subject');
                $table->text('description')->nullable();
                $table->string('file_path');
                $table->string('file_name');
                $table->integer('file_size')->nullable();
                $table->string('file_type');
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->foreign('teacher_id')->references('id')->on('users')->onDelete('cascade');
            });
        }

    }

    public function down(): void
    {
        Schema::dropIfExists('teaching_materials');
        Schema::dropIfExists('teacher_schedules');
        Schema::dropIfExists('students');
        Schema::dropIfExists('student_teacher');
        Schema::dropIfExists('student_points');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('seatwork_matching_pairs');
        Schema::dropIfExists('seatwork_grade_level');
        Schema::dropIfExists('seatwork_answers');
        Schema::dropIfExists('seatwork_attempts');
        Schema::dropIfExists('seatwork_options');
        Schema::dropIfExists('seatwork_questions');
        Schema::dropIfExists('seatworks');
        Schema::dropIfExists('quiz_section');
        Schema::dropIfExists('quiz_grade_level');
        Schema::dropIfExists('quiz_answers');
        Schema::dropIfExists('quiz_attempts');
        Schema::dropIfExists('quiz_options');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('quizzes');
        Schema::dropIfExists('quarterly_events');
        Schema::dropIfExists('practical_scores');
        Schema::dropIfExists('practical_grade_level');
        Schema::dropIfExists('practical_criteria');
        Schema::dropIfExists('practical_attempts');
        Schema::dropIfExists('practicals');
        Schema::dropIfExists('passkeys');
        Schema::dropIfExists('monthly_entries');
        Schema::dropIfExists('monthly_records');
        Schema::dropIfExists('meetings');
        Schema::dropIfExists('game_progress');
        Schema::dropIfExists('game_correct_answers');
        Schema::dropIfExists('game_cards');
        Schema::dropIfExists('games');
        Schema::dropIfExists('exam_section_scores');
        Schema::dropIfExists('exam_matching_pairs');
        Schema::dropIfExists('exam_grade_level');
        Schema::dropIfExists('exam_criteria');
        Schema::dropIfExists('exam_answers');
        Schema::dropIfExists('exam_attempts');
        Schema::dropIfExists('exam_options');
        Schema::dropIfExists('exam_questions');
        Schema::dropIfExists('exam_sections');
        Schema::dropIfExists('exams');
        Schema::dropIfExists('conversation_participants');
        Schema::dropIfExists('conversations');
        Schema::dropIfExists('users');
        Schema::dropIfExists('sections');
        Schema::dropIfExists('grade_levels');
        Schema::dropIfExists('calendar_events');
        Schema::dropIfExists('attendance_entries');
        Schema::dropIfExists('attendance_records');
    }
};
