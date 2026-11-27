<?php

use App\Enums\GameType;
use App\Models\Game;
use App\Models\GradeLevel;
use App\Models\Poll;
use App\Models\PollVote;
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
        'grade_level_id' => $this->gradeLevel->id,
        'section_id' => $this->section->id,
        'email_verified_at' => now(),
        'total_points' => 100,
        'streak_freeze_count' => 2,
        'streak_freeze_active' => false,
    ]);
});

test('game type enum includes crossword, wordsearch, and codepuzzle', function () {
    expect(GameType::Crossword->value)->toBe('crossword');
    expect(GameType::WordSearch->value)->toBe('wordsearch');
    expect(GameType::CodePuzzle->value)->toBe('codepuzzle');

    $validations = GameType::validationList();
    expect(str_contains($validations, 'crossword'))->toBeTrue();
    expect(str_contains($validations, 'wordsearch'))->toBeTrue();
    expect(str_contains($validations, 'codepuzzle'))->toBeTrue();
});

test('teacher can create crossword, word search, and code puzzle games', function () {
    $this->actingAs($this->teacher);

    // Crossword
    $crosswordPayload = [
        'title' => 'Web Dev Crossword',
        'subject' => 'Web Development',
        'grade' => 'Grade 10',
        'description' => 'Crossword for web technologies',
        'xp_reward' => 25,
        'type' => 'crossword',
        'cards' => [
            ['question' => 'Markup language', 'answer' => 'HTML'],
            ['question' => 'Cascading styling', 'answer' => 'CSS'],
        ],
    ];
    $res1 = $this->post('/teacher/games', $crosswordPayload);
    $res1->assertRedirect();
    $crossword = Game::where('title', 'Web Dev Crossword')->with('cards')->first();
    expect($crossword)->not->toBeNull();
    expect($crossword->type)->toBe('crossword');
    expect($crossword->cards)->toHaveCount(2);

    // Word Search
    $wsPayload = [
        'title' => 'Astronomy Word Search',
        'subject' => 'Science',
        'grade' => 'Grade 10',
        'description' => 'Vocabulary retention search',
        'xp_reward' => 20,
        'type' => 'wordsearch',
        'cards' => [
            ['question' => 'Solar center', 'answer' => 'SUN'],
            ['question' => '', 'answer' => 'MOON'], // fallback clue
        ],
    ];
    $res2 = $this->post('/teacher/games', $wsPayload);
    $res2->assertRedirect();
    $ws = Game::where('title', 'Astronomy Word Search')->with('cards')->first();
    expect($ws)->not->toBeNull();
    expect($ws->type)->toBe('wordsearch');
    expect($ws->cards[1]->question)->toBe('Word Search');

    // Code Puzzle
    $codePayload = [
        'title' => 'Variable Initialization Code Puzzle',
        'subject' => 'Programming',
        'grade' => 'Grade 10',
        'description' => 'Arrange steps in execution order',
        'xp_reward' => 30,
        'type' => 'codepuzzle',
        'cards' => [
            ['question' => 'Define counter', 'answer' => 'let counter = 0;'],
            ['question' => '', 'answer' => 'counter++;'],
        ],
    ];
    $res3 = $this->post('/teacher/games', $codePayload);
    $res3->assertRedirect();
    $cp = Game::where('title', 'Variable Initialization Code Puzzle')->with('cards')->first();
    expect($cp)->not->toBeNull();
    expect($cp->type)->toBe('codepuzzle');
    expect($cp->cards[1]->question)->toBe('Code Step');
});

test('student dashboard renders streakInfo, badges, and activePoll', function () {
    $this->actingAs($this->student);

    $poll = Poll::create([
        'question' => 'What is your primary programming language?',
        'options' => ['PHP', 'TypeScript', 'Python', 'Rust'],
        'is_active' => true,
    ]);

    $response = $this->get('/student/dashboard');
    $response->assertOk();

    $response->assertInertia(fn ($page) => $page
        ->component('Student/Dashboard')
        ->has('streakInfo')
        ->has('streakInfo.streak')
        ->has('streakInfo.multiplier')
        ->has('streakInfo.freeze_count')
        ->has('badges')
        ->has('activePoll')
        ->where('activePoll.id', $poll->id)
    );
});

test('student can toggle streak freeze shield', function () {
    $this->actingAs($this->student);

    expect($this->student->streak_freeze_active)->toBeFalse();
    expect($this->student->streak_freeze_count)->toBe(2);

    // Activate
    $res1 = $this->post('/student/streak/freeze');
    $res1->assertRedirect();

    $this->student->refresh();
    expect($this->student->streak_freeze_active)->toBeTrue();
    expect($this->student->streak_freeze_count)->toBe(1);

    // Deactivate
    $res2 = $this->post('/student/streak/freeze');
    $res2->assertRedirect();

    $this->student->refresh();
    expect($this->student->streak_freeze_active)->toBeFalse();
    expect($this->student->streak_freeze_count)->toBe(2);
});

test('student can vote in quick class poll and update their vote', function () {
    $this->actingAs($this->student);

    $poll = Poll::create([
        'question' => 'Preferred UI mode?',
        'options' => ['Dark Glow', 'Light Clean', 'High Contrast'],
        'is_active' => true,
    ]);

    // Vote option 0
    $res1 = $this->post("/student/polls/{$poll->id}/vote", ['option_index' => 0]);
    $res1->assertRedirect();

    $vote = PollVote::where('poll_id', $poll->id)->where('user_id', $this->student->id)->first();
    expect($vote)->not->toBeNull();
    expect($vote->option_index)->toBe(0);

    // Change vote to option 1
    $res2 = $this->post("/student/polls/{$poll->id}/vote", ['option_index' => 1]);
    $res2->assertRedirect();

    $vote->refresh();
    expect($vote->option_index)->toBe(1);
    expect(PollVote::where('poll_id', $poll->id)->count())->toBe(1);
});
