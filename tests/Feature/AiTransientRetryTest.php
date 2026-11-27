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

test('viaAiDocumentIngestion retries on 503 resource pressure and succeeds on subsequent attempt', function () {
    Setting::set('ai_provider', '9router');
    Setting::set('ninerouter_api_key', 'sk-test');
    Setting::set('ninerouter_model', 'auto');
    Setting::set('ninerouter_base_url', 'http://localhost:20128/v1');
    Setting::set('ai_activity_generation_enabled', 'true');

    $attemptCount = 0;
    Http::fake([
        'http://localhost:20128/v1/chat/completions' => function ($request) use (&$attemptCount) {
            $attemptCount++;
            if ($attemptCount === 1) {
                return Http::response([
                    'error' => [
                        'message' => 'Service temporarily unavailable due to resource pressure. Retry shortly.',
                        'type' => 'server_error',
                        'code' => 'resource_pressure',
                    ],
                ], 503);
            }

            return Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'title' => 'InDesign Essentials',
                                'items' => [
                                    [
                                        'question_text' => 'Which tool moves an already-created rectangle without changing its shape?',
                                        'type' => 'multiple_choice',
                                        'points' => 1,
                                        'options' => [
                                            ['option_text' => 'Selection Tool', 'is_correct' => true],
                                            ['option_text' => 'Direct Selection Tool', 'is_correct' => false],
                                        ],
                                    ],
                                ],
                            ]),
                        ],
                    ],
                ],
            ], 200);
        },
    ]);

    $result = QuizGeneratorService::parseOrGenerateDocument('quiz', 'Sample InDesign text', 1, ['multiple_choice'], 'generate');

    expect($attemptCount)->toBe(2)
        ->and($result['ok'])->toBeTrue()
        ->and($result['provider'])->toBe('9router')
        ->and($result['items'])->toHaveCount(1)
        ->and($result['items'][0]['question_text'])->toContain('Which tool moves');
});

test('activity ai grader retries on 503 resource pressure and succeeds', function () {
    Setting::set('ai_provider', 'omniroute');
    Setting::set('omniroute_api_key', 'sk-omni');
    Setting::set('omniroute_model', 'auto');
    Setting::set('omniroute_base_url', 'http://omniroute.local/v1');

    $attemptCount = 0;
    Http::fake([
        'http://omniroute.local/v1/chat/completions' => function ($request) use (&$attemptCount) {
            $attemptCount++;
            if ($attemptCount === 1) {
                return Http::response([
                    'error' => [
                        'message' => 'Service temporarily unavailable due to resource pressure. Retry shortly.',
                        'type' => 'server_error',
                        'code' => 'resource_pressure',
                    ],
                ], 503);
            }

            return Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'is_correct' => true,
                                'feedback' => 'Selection tool is correct shortcut V.',
                            ]),
                        ],
                    ],
                ],
            ], 200);
        },
    ]);

    $raw = ActivityAiGraderService::callAi('System instructions', 'User input', 256);

    expect($attemptCount)->toBe(2)
        ->and($raw)->not()->toBeNull()
        ->and($raw)->toContain('Selection tool is correct shortcut V');
});

test('essay grader retries on 503 resource pressure and succeeds', function () {
    Setting::set('ai_provider', '9router');
    Setting::set('ninerouter_api_key', 'sk-test');
    Setting::set('ninerouter_model', 'auto');
    Setting::set('ninerouter_base_url', 'http://localhost:20128/v1');
    Setting::set('ai_essay_grading_enabled', 'true');

    $attemptCount = 0;
    Http::fake([
        'http://localhost:20128/v1/chat/completions' => function ($request) use (&$attemptCount) {
            $attemptCount++;
            if ($attemptCount === 1) {
                return Http::response([
                    'error' => [
                        'message' => 'Service temporarily unavailable due to resource pressure. Retry shortly.',
                        'type' => 'server_error',
                        'code' => 'resource_pressure',
                    ],
                ], 503);
            }

            return Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'score' => 8,
                                'feedback' => 'Good explanation of vector paths.',
                            ]),
                        ],
                    ],
                ],
            ], 200);
        },
    ]);

    $grade = EssayGraderService::grade('Explain pen tool', 'Accuracy', 10, 'Pen tool makes bezier curves.');

    expect($attemptCount)->toBe(2)
        ->and($grade['score'])->toBe(8)
        ->and($grade['feedback'])->toContain('Good explanation');
});

test('document ingestion falls back to heuristic if upstream 503 persists after retries', function () {
    Setting::set('ai_provider', '9router');
    Setting::set('ninerouter_api_key', 'sk-test');
    Setting::set('ninerouter_model', 'auto');
    Setting::set('ninerouter_base_url', 'http://localhost:20128/v1');
    Setting::set('ai_activity_generation_enabled', 'true');

    $attemptCount = 0;
    Http::fake([
        'http://localhost:20128/v1/chat/completions' => function ($request) use (&$attemptCount) {
            $attemptCount++;
            return Http::response([
                'error' => [
                    'message' => 'Service temporarily unavailable due to resource pressure. Retry shortly.',
                    'type' => 'server_error',
                    'code' => 'resource_pressure',
                ],
            ], 503);
        },
    ]);

    $text = "1. What tool creates a rectangle?\nA. Rectangle Tool\nB. Pen Tool\nAnswer Key:\n1. A";
    $result = QuizGeneratorService::parseOrGenerateDocument('quiz', $text, 1, ['multiple_choice'], 'extract');

    expect($attemptCount)->toBe(3) // 3 retries executed before fallback
        ->and($result['ok'])->toBeTrue()
        ->and($result['provider'])->toBe('heuristic')
        ->and($result['items'])->not()->toBeEmpty();
});
