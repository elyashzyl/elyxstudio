<?php

use App\Models\Announcement;
use App\Models\ClassTodo;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->grade = GradeLevel::create([
        'name' => 'Grade 10',
        'display_order' => 1,
        'is_active' => true,
    ]);

    $this->section = Section::create([
        'name' => 'Emerald',
        'grade_level_id' => $this->grade->id,
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

    $this->student = User::factory()->create([
        'role' => 'student',
        'grade_level_id' => $this->grade->id,
        'section_id' => $this->section->id,
        'email_verified_at' => now(),
    ]);

    Setting::set('ai_provider', 'openai');
    Setting::set('openai_api_key', 'sk-test-key');
    Setting::set('openai_model', 'gpt-4o-mini');
    Setting::set('openai_base_url', 'https://api.openai.com/v1');
});

test('guests and students cannot access ai communication draft endpoint', function () {
    $this->postJson(route('teacher.ai-communications.draft'), [
        'type' => 'announcement',
        'prompt' => 'Remind Grade 10 about assembly tomorrow.',
    ])->assertUnauthorized();

    $this->actingAs($this->student)
        ->postJson(route('teacher.ai-communications.draft'), [
            'type' => 'announcement',
            'prompt' => 'Remind Grade 10 about assembly tomorrow.',
        ])
        ->assertForbidden();
});

test('teacher and superadmin can draft announcement and notification without persisting records', function () {
    Http::fake([
        'https://api.openai.com/v1/chat/completions' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode([
                            'title' => 'Midterm Review Session',
                            'content' => 'Grade 10 Emerald midterm review session will take place on Monday at 3:00 PM.',
                            'category' => 'academic',
                            'target_grade_level_id' => $this->grade->id,
                            'target_section_id' => $this->section->id,
                            'expires_at' => '2026-11-10T17:00',
                            'is_pinned' => true,
                        ]),
                    ],
                ],
            ],
        ], 200),
    ]);

    $response = $this->actingAs($this->teacher)
        ->postJson(route('teacher.ai-communications.draft'), [
            'type' => 'announcement',
            'prompt' => 'Pin an academic announcement for Grade 10 Emerald about Monday 3pm midterm review expiring Nov 10 2026 5pm.',
        ]);

    $response->assertOk()
        ->assertJson([
            'ok' => true,
            'draft' => [
                'title' => 'Midterm Review Session',
                'category' => 'academic',
                'target_grade_level_id' => $this->grade->id,
                'target_section_id' => $this->section->id,
                'expires_at' => '2026-11-10T17:00',
                'is_pinned' => true,
            ],
        ]);

    expect(Announcement::count())->toBe(0);

    $adminResponse = $this->actingAs($this->admin)
        ->postJson(route('teacher.ai-communications.draft'), [
            'type' => 'notification',
            'prompt' => 'Notify Grade 10 Emerald about the review session.',
        ]);

    $adminResponse->assertOk()
        ->assertJsonPath('ok', true);
    expect(Announcement::count())->toBe(0);
});

test('teacher can draft class todo with validated due date and prompt link', function () {
    Http::fake([
        'https://api.openai.com/v1/chat/completions' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode([
                            'title' => 'Read Chapter 5 & Answer Guide',
                            'description' => 'Read Chapter 5 and complete the guide questions.',
                            'category' => 'reading',
                            'target_grade_level_id' => $this->grade->id,
                            'target_section_id' => null,
                            'due_date' => '2026-11-12T23:59',
                            'link_url' => 'https://example.com/chapter-5',
                        ]),
                    ],
                ],
            ],
        ], 200),
    ]);

    $response = $this->actingAs($this->teacher)
        ->postJson(route('teacher.ai-communications.draft'), [
            'type' => 'todo',
            'prompt' => 'Assign Grade 10 reading task for Chapter 5 due 2026-11-12 23:59 using https://example.com/chapter-5',
        ]);

    $response->assertOk()
        ->assertJson([
            'ok' => true,
            'draft' => [
                'title' => 'Read Chapter 5 & Answer Guide',
                'category' => 'reading',
                'target_grade_level_id' => $this->grade->id,
                'target_section_id' => null,
                'due_date' => '2026-11-12T23:59',
                'link_url' => 'https://example.com/chapter-5',
            ],
        ]);

    expect(ClassTodo::count())->toBe(0);
});

test('draft endpoint rejects hallucinated links, mismatched sections, and malformed ai responses', function () {
    $otherGrade = GradeLevel::create(['name' => 'Grade 9', 'is_active' => true]);
    $otherSection = Section::create([
        'name' => 'Ruby',
        'grade_level_id' => $otherGrade->id,
        'is_active' => true,
    ]);

    Http::fakeSequence()
        ->push([
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode([
                            'title' => 'Homework Task',
                            'description' => 'Submit homework.',
                            'category' => 'homework',
                            'target_grade_level_id' => $this->grade->id,
                            'target_section_id' => null,
                            'due_date' => null,
                            'link_url' => 'https://invented.example.com/resource',
                        ]),
                    ],
                ],
            ],
        ], 200)
        ->push([
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode([
                            'title' => 'Mismatched Section Notice',
                            'content' => 'Details here.',
                            'category' => 'general',
                            'target_grade_level_id' => $this->grade->id,
                            'target_section_id' => $otherSection->id,
                            'expires_at' => null,
                            'is_pinned' => false,
                        ]),
                    ],
                ],
            ],
        ], 200)
        ->push([
            'choices' => [
                [
                    'message' => [
                        'content' => 'not-json',
                    ],
                ],
            ],
        ], 200);

    $this->actingAs($this->teacher)
        ->postJson(route('teacher.ai-communications.draft'), [
            'type' => 'todo',
            'prompt' => 'Assign homework to Grade 10.',
        ])
        ->assertStatus(422)
        ->assertJsonPath('ok', false);

    $this->actingAs($this->teacher)
        ->postJson(route('teacher.ai-communications.draft'), [
            'type' => 'announcement',
            'prompt' => 'Post notice.',
        ])
        ->assertStatus(422)
        ->assertJsonPath('ok', false);

    $this->actingAs($this->teacher)
        ->postJson(route('teacher.ai-communications.draft'), [
            'type' => 'announcement',
            'prompt' => 'Post notice.',
        ])
        ->assertStatus(422)
        ->assertJsonPath('ok', false);
});

test('draft endpoint falls back to offline heuristic generator when external ai provider is unavailable', function () {
    Setting::set('ai_provider', 'heuristic');

    $this->actingAs($this->teacher)
        ->postJson(route('teacher.ai-communications.draft'), [
            'type' => 'announcement',
            'prompt' => 'Pin an urgent announcement for Grade 10 Emerald about tomorrow lab safety check.',
        ])
        ->assertOk()
        ->assertJson([
            'ok' => true,
            'draft' => [
                'category' => 'urgent',
                'target_grade_level_id' => $this->grade->id,
                'target_section_id' => $this->section->id,
                'is_pinned' => true,
            ],
        ]);
});

test('student notification feed includes targeted active announcements and pending class todos', function () {
    Announcement::create([
        'user_id' => $this->teacher->id,
        'title' => 'Lab Safety Reminder',
        'content' => 'Bring your lab coat tomorrow.',
        'category' => 'reminder',
        'target_role' => 'student',
        'target_grade_level_id' => $this->grade->id,
        'target_section_id' => $this->section->id,
    ]);

    ClassTodo::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'Submit Lab Worksheet',
        'description' => 'Upload worksheet before Friday.',
        'category' => 'homework',
        'target_grade_level_id' => $this->grade->id,
        'target_section_id' => $this->section->id,
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->student)
        ->getJson(route('student.notifications.index'))
        ->assertOk();

    $types = collect($response->json('notifications'))->pluck('type')->all();
    expect($types)->toContain('announcement')
        ->and($types)->toContain('todo');
});
