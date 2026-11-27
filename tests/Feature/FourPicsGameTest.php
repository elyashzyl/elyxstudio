<?php

use App\Enums\GameType;
use App\Models\Game;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->gradeLevel = GradeLevel::create(['name' => 'Grade 10', 'display_order' => 1]);
    $this->section = Section::create(['name' => 'Section A', 'grade_level_id' => $this->gradeLevel->id]);

    $this->teacher = User::factory()->create([
        'role' => 'teacher',
        'email_verified_at' => now(),
    ]);

    $this->student = User::factory()->create([
        'role' => 'student',
        'grade' => 'Grade 10',
        'section_id' => $this->section->id,
        'email_verified_at' => now(),
        'total_points' => 0,
    ]);
});

test('game type enum includes fourpics', function () {
    expect(GameType::FourPics->value)->toBe('fourpics');
    expect(str_contains(GameType::validationList(), 'fourpics'))->toBeTrue();
});

test('teacher can create a 4 pics 1 word game with 4 image options and target answer', function () {
    $this->actingAs($this->teacher);

    $payload = [
        'title' => 'Design Principles 4 Pics',
        'subject' => 'Graphic Design',
        'grade' => 'Grade 10',
        'description' => 'Guess the common design principle from 4 pictures',
        'xp_reward' => 20,
        'type' => 'fourpics',
        'cards' => [
            [
                'question' => 'Design principle',
                'answer' => 'CONTRAST',
                'options' => [
                    'https://images.unsplash.com/photo-1.jpg',
                    'https://images.unsplash.com/photo-2.jpg',
                    'https://images.unsplash.com/photo-3.jpg',
                    'https://images.unsplash.com/photo-4.jpg',
                ],
            ],
            [
                'question' => '', // Empty clue should fallback gracefully
                'answer' => 'BALANCE',
                'options' => [
                    'https://images.unsplash.com/photo-5.jpg',
                    'https://images.unsplash.com/photo-6.jpg',
                    'https://images.unsplash.com/photo-7.jpg',
                    'https://images.unsplash.com/photo-8.jpg',
                ],
            ],
        ],
    ];

    $response = $this->post('/teacher/games', $payload);
    $response->assertRedirect();

    $game = Game::where('title', 'Design Principles 4 Pics')->with('cards')->first();
    expect($game)->not->toBeNull();
    expect($game->type)->toBe('fourpics');
    expect($game->cards)->toHaveCount(2);

    $firstCard = $game->cards[0];
    expect($firstCard->answer)->toBe('CONTRAST');
    expect($firstCard->question)->toBe('Design principle');
    expect($firstCard->options)->toBeArray();
    expect(count($firstCard->options))->toBe(4);

    $secondCard = $game->cards[1];
    expect($secondCard->answer)->toBe('BALANCE');
    expect($secondCard->question)->toBe('4 Pics 1 Word');
    expect(count($secondCard->options))->toBe(4);
});

test('teacher can update an existing 4 pics 1 word game', function () {
    $this->actingAs($this->teacher);

    $game = Game::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'Original 4 Pics Game',
        'subject' => 'Science',
        'grade' => 'Grade 10',
        'description' => 'Original description',
        'xp_reward' => 10,
        'type' => 'fourpics',
    ]);

    $game->cards()->create([
        'question' => 'Element',
        'answer' => 'WATER',
        'order' => 0,
        'options' => ['https://img.com/1', 'https://img.com/2', 'https://img.com/3', 'https://img.com/4'],
    ]);

    $updatePayload = [
        'title' => 'Updated 4 Pics Game',
        'subject' => 'Science',
        'grade' => 'Grade 10',
        'description' => 'Updated description',
        'xp_reward' => 15,
        'type' => 'fourpics',
        'cards' => [
            [
                'question' => 'Updated Element',
                'answer' => 'OXYGEN',
                'options' => ['https://img.com/a', 'https://img.com/b', 'https://img.com/c', 'https://img.com/d'],
            ],
        ],
    ];

    $response = $this->put("/teacher/games/{$game->id}", $updatePayload);
    $response->assertRedirect();

    $freshGame = $game->fresh()->load('cards');
    expect($freshGame->title)->toBe('Updated 4 Pics Game');
    expect($freshGame->xp_reward)->toBe(15);
    expect($freshGame->cards)->toHaveCount(1);
    expect($freshGame->cards[0]->answer)->toBe('OXYGEN');
});

test('student can view, answer, and complete a 4 pics 1 word game and earn xp', function () {
    $game = Game::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'Student 4 Pics Game',
        'subject' => 'Art',
        'grade' => 'Grade 10',
        'description' => 'Guess the art terms',
        'xp_reward' => 30,
        'type' => 'fourpics',
        'hidden' => false,
    ]);

    $card1 = $game->cards()->create([
        'question' => 'Art Concept',
        'answer' => 'COLOR',
        'order' => 0,
        'options' => ['https://img.com/1', 'https://img.com/2', 'https://img.com/3', 'https://img.com/4'],
    ]);

    $card2 = $game->cards()->create([
        'question' => 'Art Concept',
        'answer' => 'SPACE',
        'order' => 1,
        'options' => ['https://img.com/5', 'https://img.com/6', 'https://img.com/7', 'https://img.com/8'],
    ]);

    $this->actingAs($this->student);

    // 1. Student views games page
    $response = $this->get('/student/games');
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Student/Games')
        ->has('games')
    );

    // 2. Student records correct answer for card 1
    $answerResponse1 = $this->postJson("/student/games/{$game->id}/answer", [
        'card_id' => $card1->id,
    ]);
    $answerResponse1->assertOk();
    $answerResponse1->assertJson(['ok' => true]);

    // 3. Student records correct answer for card 2
    $answerResponse2 = $this->postJson("/student/games/{$game->id}/answer", [
        'card_id' => $card2->id,
    ]);
    $answerResponse2->assertOk();
    $answerResponse2->assertJson(['ok' => true]);

    // 4. Student saves completion progress
    $progressResponse = $this->postJson("/student/games/{$game->id}/progress", [
        'current_card' => 1,
        'done_count' => 2,
        'score' => 2,
        'completed' => true,
        'graded' => [true, true],
    ]);

    $progressResponse->assertOk();
    $progressResponse->assertJson([
        'ok' => true,
        'awarded' => true,
        'xp' => 30,
    ]);

    // 5. Verify student points awarded
    expect($this->student->fresh()->total_points)->toBe(30);
    $this->assertDatabaseHas('game_progress', [
        'user_id' => $this->student->id,
        'game_id' => $game->id,
        'completed' => true,
    ]);
});
