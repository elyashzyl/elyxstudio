<?php

use App\Models\Setting;
use App\Models\User;
use App\Services\ActivityAiGraderService;
use App\Services\AiConfigService;
use App\Services\EssayGraderService;
use App\Services\QuizGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create([
        'role' => 'superadmin',
        'email_verified_at' => now(),
    ]);

    $this->teacher = User::factory()->create([
        'role' => 'teacher',
        'email_verified_at' => now(),
    ]);
});

test('superadmin can save 9router settings via ai settings endpoint', function () {
    $response = $this->actingAs($this->admin)
        ->putJson('/teacher/system-settings/ai', [
            'ai_provider' => '9router',
            'ninerouter_api_key' => 'test-9router-key-123',
            'ninerouter_model' => 'claude-3-5-sonnet',
            'ninerouter_base_url' => 'http://localhost:20128/v1',
            'ai_request_timeout' => 45,
            'ai_max_source_chars' => 8000,
            'ai_essay_grading_enabled' => 'true',
            'ai_activity_generation_enabled' => 'true',
            'ai_system_skill' => 'omniroute_terse',
        ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
        ]);

    expect(Setting::get('ai_provider'))->toBe('9router')
        ->and(Setting::get('ninerouter_api_key'))->toBe('test-9router-key-123')
        ->and(Setting::get('ninerouter_model'))->toBe('claude-3-5-sonnet')
        ->and(Setting::get('ninerouter_base_url'))->toBe('http://localhost:20128/v1');
});

test('ai config service resolves 9router settings and active provider', function () {
    Setting::set('ai_provider', '9router');
    Setting::set('ninerouter_api_key', 'router-secret-key');
    Setting::set('ninerouter_model', 'deepseek-chat');
    Setting::set('ninerouter_base_url', 'http://127.0.0.1:20128/v1');

    expect(AiConfigService::resolveActiveProvider())->toBe('9router')
        ->and(AiConfigService::getNineRouterApiKey())->toBe('router-secret-key')
        ->and(AiConfigService::getNineRouterModel())->toBe('deepseek-chat')
        ->and(AiConfigService::getNineRouterBaseUrl())->toBe('http://127.0.0.1:20128/v1');
});

test('test connection successfully verifies 9router endpoint', function () {
    Http::fake([
        'http://localhost:20128/v1/models' => Http::response([
            'data' => [
                ['id' => 'auto'],
                ['id' => 'gpt-4o-mini'],
                ['id' => 'claude-3-5-sonnet'],
            ],
        ], 200),
        'http://localhost:20128/v1/chat/completions' => Http::response([
            'choices' => [
                ['message' => ['content' => 'OK']],
            ],
        ], 200),
    ]);

    $result = AiConfigService::testConnection('9router', [
        'ninerouter_api_key' => 'sk-9router-local',
        'ninerouter_model' => 'gpt-4o-mini',
        'ninerouter_base_url' => 'http://localhost:20128/v1',
    ]);

    expect($result['success'])->toBeTrue()
        ->and($result['provider'])->toBe('9router')
        ->and($result['model'])->toBe('gpt-4o-mini')
        ->and($result['available_models'])->toContain('gpt-4o-mini');
});

test('essay grader uses 9router provider when active', function () {
    Setting::set('ai_provider', '9router');
    Setting::set('ninerouter_api_key', 'sk-router');
    Setting::set('ninerouter_model', 'auto');
    Setting::set('ninerouter_base_url', 'http://localhost:20128/v1');
    Setting::set('ai_essay_grading_enabled', 'true');

    Http::fake([
        'http://localhost:20128/v1/chat/completions' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode([
                            'score' => 9,
                            'feedback' => 'Direct, criteria-grounded answer with accurate points.',
                        ]),
                    ],
                ],
            ],
        ], 200),
    ]);

    $result = EssayGraderService::grade(
        questionText: 'Explain the purpose of the pen tool.',
        criteria: 'Clarity, accuracy of anchor points explanation.',
        maxPoints: 10,
        studentAnswer: 'The pen tool creates vector paths using anchor points.'
    );

    expect($result)->not()->toBeNull()
        ->and($result['score'])->toBe(9)
        ->and($result['feedback'])->toContain('Direct, criteria-grounded answer');
});

test('quiz generator generates questions via 9router', function () {
    Setting::set('ai_provider', '9router');
    Setting::set('ninerouter_api_key', 'sk-router');
    Setting::set('ninerouter_model', 'auto');
    Setting::set('ninerouter_base_url', 'http://localhost:20128/v1');
    Setting::set('ai_activity_generation_enabled', 'true');

    Http::fake([
        'http://localhost:20128/v1/chat/completions' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode([
                            'questions' => [
                                [
                                    'question_text' => 'What is the selection tool keyboard shortcut in InDesign?',
                                    'type' => 'multiple_choice',
                                    'options' => [
                                        ['option_text' => 'V', 'is_correct' => true],
                                        ['option_text' => 'A', 'is_correct' => false],
                                    ],
                                ],
                            ],
                        ]),
                    ],
                ],
            ],
        ], 200),
    ]);

    $result = QuizGeneratorService::generateFor(
        activity: 'quiz',
        text: 'The Selection Tool (shortcut V) is used to select objects in Adobe InDesign.',
        count: 1,
        types: ['multiple_choice']
    );

    expect($result['provider'])->toBe('9router')
        ->and($result['items'])->toHaveCount(1)
        ->and($result['items'][0]['question_text'])->toContain('selection tool');
});
