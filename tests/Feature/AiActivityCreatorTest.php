<?php

use App\Models\GradeLevel;
use App\Models\Practical;
use App\Models\Quiz;
use App\Models\Seatwork;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->gradeLevel = GradeLevel::create([
        'name' => 'Grade 10',
        'display_order' => 1,
        'is_active' => true,
    ]);

    $this->section = Section::create([
        'name' => 'Section A',
        'grade_level_id' => $this->gradeLevel->id,
        'is_active' => true,
    ]);

    $this->admin = User::factory()->create([
        'role' => 'superadmin',
        'email_verified_at' => now(),
    ]);

    $this->teacher = User::factory()->create([
        'role' => 'teacher',
        'email_verified_at' => now(),
    ]);

    $this->otherTeacher = User::factory()->create([
        'role' => 'teacher',
        'email_verified_at' => now(),
    ]);

    $this->student = User::factory()->create([
        'role' => 'student',
        'grade_level_id' => $this->gradeLevel->id,
        'email_verified_at' => now(),
    ]);
});

/*
|--------------------------------------------------------------------------
| Access Control
|--------------------------------------------------------------------------
*/

test('guests are redirected to login when accessing ai creator workspace', function () {
    $response = $this->get('/teacher/ai-creator');

    $response->assertRedirect(route('login'));
});

test('students cannot access ai creator workspace', function () {
    $this->actingAs($this->student);

    $response = $this->get('/teacher/ai-creator');

    $response->assertRedirect(route('student.dashboard'));
});

test('teachers can access ai creator workspace', function () {
    $this->actingAs($this->teacher);

    $response = $this->get('/teacher/ai-creator');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Teacher/AiCreator/Index')
        ->has('gradeLevels')
        ->has('allSections')
        ->where('isSuperadmin', false)
    );
});

test('superadmins can access ai creator workspace with teachers list', function () {
    $this->actingAs($this->admin);

    $response = $this->get('/teacher/ai-creator');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Teacher/AiCreator/Index')
        ->where('isSuperadmin', true)
        ->has('teachers')
    );
});

/*
|--------------------------------------------------------------------------
| Parsing via /teacher/ai-creator/parse
|--------------------------------------------------------------------------
*/

test('guests and students cannot access parse endpoint', function () {
    $response = $this->postJson('/teacher/ai-creator/parse', [
        'activity' => 'quiz',
        'raw_text' => 'Sample content',
    ]);
    $response->assertUnauthorized();

    $this->actingAs($this->student);
    $responseStudent = $this->postJson('/teacher/ai-creator/parse', [
        'activity' => 'quiz',
        'raw_text' => 'Sample content',
    ]);
    $responseStudent->assertForbidden();
});

test('parse endpoint fails with 422 if no file and no text provided', function () {
    $this->actingAs($this->teacher);

    $response = $this->postJson('/teacher/ai-creator/parse', [
        'activity' => 'quiz',
        'raw_text' => '',
    ]);

    $response->assertStatus(422);
    $response->assertJson([
        'ok' => false,
    ]);
});

test('parse endpoint parses quiz with multi-column answer key accurately', function () {
    $this->actingAs($this->teacher);

    $text = <<<'TXT'
Shapes, Colors, and Graphic Elements in Adobe InDesign
Instructions: Read each question carefully. Choose the best answer.

1. A student wants to move an already-created rectangle to another location without changing its shape. Which tool should be used?
A. Rectangle Tool
B. Selection Tool
C. Free Transform Tool
D. Direct Selection Tool

2. A rectangle has a blue interior and a thin black outline. Which statement correctly identifies these properties?
A. Blue is the stroke, while black is the fill.
B. Blue is the fill, while black is the stroke.
C. Blue is the gradient, while black is the swatch.
D. Blue is the swatch, while black is the transparency.

3. A designer wants to create a circular graphic element. Which tool is most appropriate?
A. Polygon Tool
B. Rectangle Tool
C. Ellipse Tool
D. Selection Tool

Answer Key
1	B	3	C
2	B
TXT;

    $response = $this->postJson('/teacher/ai-creator/parse', [
        'activity' => 'quiz',
        'raw_text' => $text,
        'mode' => 'extract',
    ]);

    $response->assertOk();
    $data = $response->json();

    expect($data['ok'])->toBeTrue();
    expect($data['title'])->toContain('Shapes, Colors, and Graphic Elements');
    expect($data['items'])->toBeArray()->toHaveCount(3);

    // Question 1: Selection Tool (B) must be correct
    $q1 = $data['items'][0];
    expect($q1['question_text'])->toContain('move an already-created rectangle');
    $correct1 = collect($q1['options'])->firstWhere('is_correct', true);
    expect($correct1['option_text'])->toContain('Selection Tool');

    // Question 2: B must be correct
    $q2 = $data['items'][1];
    $correct2 = collect($q2['options'])->firstWhere('is_correct', true);
    expect($correct2['option_text'])->toContain('Blue is the fill, while black is the stroke');

    // Question 3: Ellipse Tool (C) must be correct
    $q3 = $data['items'][2];
    $correct3 = collect($q3['options'])->firstWhere('is_correct', true);
    expect($correct3['option_text'])->toContain('Ellipse Tool');
});

test('parse endpoint extracts practical criteria from rubric text', function () {
    $this->actingAs($this->teacher);

    $text = <<<'TXT'
Practical Exam: Adobe InDesign Layout Design
Instructions: Design a 2-page newsletter adhering to the following rubric.

Criteria:
1. Document Setup & Margins (25 pts): Set up correct document dimensions, margins, and bleed settings.
2. Typography and Hierarchy (35 pts): Apply paragraph and character styles consistently across headings and body copy.
3. Graphic Elements & Colors (40 pts): Use custom swatches, shapes, and transparency appropriately.
TXT;

    $response = $this->postJson('/teacher/ai-creator/parse', [
        'activity' => 'practical',
        'raw_text' => $text,
        'mode' => 'extract',
    ]);

    $response->assertOk();
    $data = $response->json();

    expect($data['ok'])->toBeTrue();
    expect($data['activity'])->toBe('practical');
    expect($data['items'])->toBeArray()->toHaveCount(3);
    expect($data['items'][0]['criterion_name'])->toContain('Document Setup');
    expect($data['items'][0]['max_points'])->toBe(25);
    expect($data['items'][1]['max_points'])->toBe(35);
    expect($data['items'][2]['max_points'])->toBe(40);
});

test('parse endpoint generates items from lesson notes for seatwork', function () {
    $this->actingAs($this->teacher);

    $notes = <<<'TXT'
Adobe InDesign is a professional desktop publishing software application produced by Adobe Systems.
The Selection Tool allows users to move and resize object frames across the document canvas.
The Direct Selection Tool selects content inside a frame or edits individual anchor points on a vector path.
Swatches store named colors, tints, and gradients for consistent application across the entire document.
Master pages allow designers to maintain consistent headers, footers, and page numbers across multi-page layouts.
Vector shapes retain sharp quality regardless of how large they are scaled in the publication layout.
Paragraph styles save typographic formatting including font family, size, leading, tracking, and alignment.
TXT;

    $response = $this->postJson('/teacher/ai-creator/parse', [
        'activity' => 'seatwork',
        'raw_text' => $notes,
        'count' => 3,
        'types' => ['identification', 'multiple_choice'],
        'mode' => 'generate',
    ]);

    $response->assertOk();
    $data = $response->json();

    expect($data['ok'])->toBeTrue();
    expect($data['activity'])->toBe('seatwork');
    expect($data['items'])->toBeArray()->not->toBeEmpty();
});

test('parse endpoint parses uploaded text file and returns source filename', function () {
    $this->actingAs($this->teacher);

    $fileContent = <<<'TXT'
Sample Quiz Test
Instructions: Choose the correct answer.

1. What is the shortcut for Selection Tool?
A. V
B. A
C. P
D. T

Answer Key:
1 A
TXT;

    $file = UploadedFile::fake()->createWithContent('quiz_sample.txt', $fileContent);

    $response = $this->postJson('/teacher/ai-creator/parse', [
        'activity' => 'quiz',
        'file' => $file,
    ]);

    $response->assertOk();
    $data = $response->json();

    expect($data['ok'])->toBeTrue();
    expect($data['source_filename'])->toBe('quiz_sample.txt');
    expect($data['items'])->toHaveCount(1);
    expect($data['items'][0]['options'][0]['is_correct'])->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| Activity Persistence via POST /teacher/ai-creator
|--------------------------------------------------------------------------
*/

test('teacher can store quiz via ai creator', function () {
    $this->actingAs($this->teacher);

    $payload = [
        'activity' => 'quiz',
        'title' => 'InDesign Master Quiz',
        'description' => 'AI populated InDesign assessment',
        'grade_level_ids' => [$this->gradeLevel->id],
        'section_ids' => [$this->section->id],
        'time_limit_minutes' => 30,
        'action' => 'save',
        'questions' => [
            [
                'question_text' => 'Which tool moves frames?',
                'type' => 'multiple_choice',
                'points' => 2,
                'options' => [
                    ['option_text' => 'Selection Tool', 'is_correct' => true],
                    ['option_text' => 'Pen Tool', 'is_correct' => false],
                    ['option_text' => 'Type Tool', 'is_correct' => false],
                    ['option_text' => 'Hand Tool', 'is_correct' => false],
                ],
            ],
            [
                'question_text' => 'InDesign is made by Adobe.',
                'type' => 'true_false',
                'points' => 1,
                'options' => [
                    ['option_text' => 'True', 'is_correct' => true],
                    ['option_text' => 'False', 'is_correct' => false],
                ],
            ],
        ],
    ];

    $response = $this->post('/teacher/ai-creator', $payload);

    $response->assertRedirect(route('teacher.ai-creator.create'));

    $quiz = Quiz::where('title', 'InDesign Master Quiz')->first();
    expect($quiz)->not->toBeNull();
    expect($quiz->teacher_id)->toBe($this->teacher->id);
    expect($quiz->time_limit_minutes)->toBe(30);
    expect($quiz->is_published)->toBeFalse();
    expect($quiz->gradeLevels)->toHaveCount(1);
    expect($quiz->sections)->toHaveCount(1);
    expect($quiz->questions)->toHaveCount(2);

    $firstQ = $quiz->questions()->where('question_text', 'Which tool moves frames?')->first();
    expect($firstQ->options)->toHaveCount(4);
    expect($firstQ->options()->where('is_correct', true)->value('option_text'))->toBe('Selection Tool');
});

test('teacher can store and publish quiz immediately or redirect to edit', function () {
    $this->actingAs($this->teacher);

    // Test publish
    $payloadPublish = [
        'activity' => 'quiz',
        'title' => 'Published InDesign Quiz',
        'grade_level_ids' => [$this->gradeLevel->id],
        'section_ids' => [$this->section->id],
        'action' => 'publish',
        'questions' => [
            [
                'question_text' => 'Question 1',
                'type' => 'true_false',
                'points' => 1,
                'options' => [
                    ['option_text' => 'True', 'is_correct' => true],
                    ['option_text' => 'False', 'is_correct' => false],
                ],
            ],
        ],
    ];

    $resPublish = $this->post('/teacher/ai-creator', $payloadPublish);
    $resPublish->assertRedirect(route('teacher.quizzes.index'));
    $quizPub = Quiz::where('title', 'Published InDesign Quiz')->first();
    expect($quizPub->is_published)->toBeTrue();

    // Test save_and_edit
    $payloadEdit = [
        'activity' => 'quiz',
        'title' => 'Draft InDesign Quiz for Edit',
        'grade_level_ids' => [$this->gradeLevel->id],
        'section_ids' => [$this->section->id],
        'action' => 'save_and_edit',
        'questions' => [
            [
                'question_text' => 'Question 1',
                'type' => 'true_false',
                'points' => 1,
                'options' => [
                    ['option_text' => 'True', 'is_correct' => true],
                    ['option_text' => 'False', 'is_correct' => false],
                ],
            ],
        ],
    ];

    $resEdit = $this->post('/teacher/ai-creator', $payloadEdit);
    $resEdit->assertRedirect(route('teacher.ai-creator.create'));
    expect(Quiz::where('title', 'Draft InDesign Quiz for Edit')->exists())->toBeTrue();
});

test('teacher can store quiz with rich question types via ai creator', function () {
    $this->actingAs($this->teacher);

    $payload = [
        'activity' => 'quiz',
        'title' => 'Rich InDesign Quiz',
        'description' => 'Quiz with all question types',
        'grade_level_ids' => [$this->gradeLevel->id],
        'section_ids' => [$this->section->id],
        'time_limit_minutes' => 45,
        'action' => 'save',
        'questions' => [
            [
                'question_text' => 'What is the shortcut for Selection Tool?',
                'type' => 'identification',
                'points' => 1,
                'correct_answer' => 'V',
            ],
            [
                'question_text' => 'Enumerate three primary colors in print.',
                'type' => 'enumeration',
                'points' => 3,
                'enum_items' => ['Cyan', 'Magenta', 'Yellow'],
            ],
            [
                'question_text' => 'Match terms to definitions.',
                'type' => 'matching',
                'points' => 2,
                'matching_pairs' => [
                    ['left_text' => 'Bleed', 'right_text' => 'Area beyond trim edge'],
                    ['left_text' => 'Slug', 'right_text' => 'Non-printing information area'],
                ],
            ],
            [
                'question_text' => 'Explain the importance of typography hierarchy.',
                'type' => 'essay',
                'points' => 5,
                'criteria' => 'Clear explanation of font sizes, weights, and visual flow.',
            ],
        ],
    ];

    $response = $this->post('/teacher/ai-creator', $payload);
    $response->assertRedirect(route('teacher.ai-creator.create'));

    $quiz = Quiz::where('title', 'Rich InDesign Quiz')->first();
    expect($quiz)->not->toBeNull();
    expect($quiz->questions)->toHaveCount(4);

    $ident = $quiz->questions()->where('type', 'identification')->first();
    expect($ident->options)->toHaveCount(1);
    expect($ident->options[0]->option_text)->toBe('V');

    $enum = $quiz->questions()->where('type', 'enumeration')->first();
    expect($enum->options)->toHaveCount(3);

    $match = $quiz->questions()->where('type', 'matching')->first();
    expect($match->matchingPairs)->toHaveCount(2);

    $essay = $quiz->questions()->where('type', 'essay')->first();
    expect($essay->criteria)->toBe('Clear explanation of font sizes, weights, and visual flow.');
    expect($essay->points)->toBe(5);
});

test('teacher can store seatwork with multiple question types', function () {
    $this->actingAs($this->teacher);

    $payload = [
        'activity' => 'seatwork',
        'title' => 'Typography and Shapes Seatwork',
        'instructions' => 'Complete all sections accurately.',
        'grade_level_ids' => [$this->gradeLevel->id],
        'time_limit_minutes' => 45,
        'action' => 'save',
        'questions' => [
            [
                'question_text' => 'What tool is used to move shapes?',
                'type' => 'identification',
                'points' => 2,
                'correct_answer' => 'Selection Tool',
            ],
            [
                'question_text' => 'Enumerate two color models in InDesign:',
                'type' => 'enumeration',
                'points' => 2,
                'enum_items' => ['RGB', 'CMYK'],
            ],
            [
                'question_text' => 'Match tool to its primary function:',
                'type' => 'matching',
                'points' => 2,
                'matching_pairs' => [
                    ['left_text' => 'Selection Tool', 'right_text' => 'Move objects'],
                    ['left_text' => 'Type Tool', 'right_text' => 'Create text frames'],
                ],
            ],
            [
                'question_text' => 'Explain the importance of typographic hierarchy.',
                'type' => 'essay',
                'points' => 5,
                'criteria' => 'Clarity, relevance, and technical accuracy.',
            ],
        ],
    ];

    $response = $this->post('/teacher/ai-creator', $payload);

    $response->assertRedirect(route('teacher.ai-creator.create'));

    $seatwork = Seatwork::where('title', 'Typography and Shapes Seatwork')->first();
    expect($seatwork)->not->toBeNull();
    expect($seatwork->teacher_id)->toBe($this->teacher->id);
    expect($seatwork->gradeLevels)->toHaveCount(1);
    expect($seatwork->questions)->toHaveCount(4);

    $identQ = $seatwork->questions()->where('type', 'identification')->first();
    expect($identQ->options()->first()->option_text)->toBe('Selection Tool');

    $enumQ = $seatwork->questions()->where('type', 'enumeration')->first();
    expect($enumQ->options)->toHaveCount(2);

    $matchQ = $seatwork->questions()->where('type', 'matching')->first();
    expect($matchQ->matchingPairs)->toHaveCount(2);
});

test('teacher can store practical assessment with criteria', function () {
    $this->actingAs($this->teacher);

    $payload = [
        'activity' => 'practical',
        'title' => 'InDesign Magazine Layout Practical',
        'instructions' => 'Create a 4-page spread following the rubric.',
        'grade_level_ids' => [$this->gradeLevel->id],
        'time_limit_minutes' => 90,
        'max_score' => 100,
        'max_attempts' => 2,
        'action' => 'save',
        'criteria' => [
            [
                'criterion_name' => 'Layout Grid & Alignment',
                'description' => 'Proper column grid and baseline alignment',
                'max_points' => 40,
            ],
            [
                'criterion_name' => 'Color Harmony & Contrast',
                'description' => 'Appropriate swatches and readability',
                'max_points' => 30,
            ],
            [
                'criterion_name' => 'Asset Packaging',
                'description' => 'Fonts and linked images correctly packaged',
                'max_points' => 30,
            ],
        ],
    ];

    $response = $this->post('/teacher/ai-creator', $payload);

    $response->assertRedirect(route('teacher.ai-creator.create'));

    $practical = Practical::where('title', 'InDesign Magazine Layout Practical')->first();
    expect($practical)->not->toBeNull();
    expect($practical->teacher_id)->toBe($this->teacher->id);
    expect($practical->max_score)->toBe(100);
    expect($practical->max_attempts)->toBe(2);
    expect($practical->gradeLevels)->toHaveCount(1);
    expect($practical->criteria)->toHaveCount(3);
    expect($practical->criteria()->sum('max_points'))->toBe(100);
});

test('superadmin can create activity assigned to a designated teacher', function () {
    $this->actingAs($this->admin);

    $payload = [
        'activity' => 'quiz',
        'title' => 'Admin Assigned Quiz',
        'teacher_id' => $this->otherTeacher->id,
        'grade_level_ids' => [$this->gradeLevel->id],
        'section_ids' => [$this->section->id],
        'action' => 'save',
        'questions' => [
            [
                'question_text' => 'What is 2 + 2?',
                'type' => 'true_false',
                'points' => 1,
                'options' => [
                    ['option_text' => 'True', 'is_correct' => true],
                    ['option_text' => 'False', 'is_correct' => false],
                ],
            ],
        ],
    ];

    $response = $this->post('/teacher/ai-creator', $payload);

    $response->assertRedirect(route('teacher.ai-creator.create'));

    $quiz = Quiz::where('title', 'Admin Assigned Quiz')->first();
    expect($quiz)->not->toBeNull();
    expect($quiz->teacher_id)->toBe($this->otherTeacher->id);
});

test('store normalizes singular grade_level_id input', function () {
    $this->actingAs($this->teacher);

    $payload = [
        'activity' => 'practical',
        'title' => 'Singular Grade Practical',
        'instructions' => 'Follow rubric.',
        'grade_level_id' => $this->gradeLevel->id, // singular
        'action' => 'save',
        'criteria' => [
            [
                'criterion_name' => 'Criterion 1',
                'description' => 'Test',
                'max_points' => 50,
            ],
        ],
    ];

    $response = $this->post('/teacher/ai-creator', $payload);

    $response->assertRedirect(route('teacher.ai-creator.create'));

    $practical = Practical::where('title', 'Singular Grade Practical')->first();
    expect($practical)->not->toBeNull();
    expect($practical->gradeLevels)->toHaveCount(1);
    expect($practical->gradeLevels->first()->id)->toBe($this->gradeLevel->id);
});
