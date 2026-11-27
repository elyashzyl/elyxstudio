<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\ActivityLogger;
use App\Services\AiConfigService;
use App\Services\CloudStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SystemSettingController extends Controller
{
    public static function defaultSettings(): array
    {
        return [
            // School Info (optional)
            'school_name' => env('SCHOOL_NAME', ''),
            'school_short' => env('SCHOOL_SHORT', ''),
            'school_id' => env('SCHOOL_ID', ''),
            'school_address' => env('SCHOOL_ADDRESS', ''),
            'school_contact' => env('SCHOOL_CONTACT', ''),
            'school_email' => env('SCHOOL_EMAIL', ''),

            // Platform & Branding
            'app_name' => env('APP_NAME', config('app.name', 'EduPulse')),
            'support_email' => env('SUPPORT_EMAIL', 'ely.ashzyl@gmail.com'),
            'payment_email' => env('PAYMENT_EMAIL', 'ely.ashzyl@gmail.com'),
            'maintenance_mode' => 'false',
            'maintenance_message' => 'System is undergoing brief maintenance. Please check back shortly.',
            'allow_registration' => env('ALLOW_REGISTRATION', false) ? 'true' : 'false',

            // Communication
            'messenger_system_enabled' => 'true',
            'classmate_messaging_enabled' => 'true',
            'allow_chat_attachments' => 'true',

            // Gamification & Scoring
            'xp_passing_percentage' => '60',
            'xp_mastery_percentage' => '80',
            'xp_award_mastery' => '10',
            'xp_award_pass' => '5',
            'xp_award_attempt' => '1',
            'xp_per_level' => (string) config('gamification.xp_per_level', 100),
            'exam_max_score' => (string) config('gamification.exam_max_score', 100),
            'practical_max_attempts' => (string) config('gamification.practical_max_attempts', 3),

            // AI Configuration
            'ai_provider' => (string) config('ai.provider', 'auto'),
            'gemini_api_key' => (string) config('ai.gemini_api_key', ''),
            'gemini_model' => (string) config('ai.gemini_model', 'gemini-1.5-flash'),
            'openai_api_key' => (string) config('ai.openai_api_key', ''),
            'openai_model' => (string) config('ai.openai_model', 'gpt-4o-mini'),
            'openai_base_url' => (string) config('ai.openai_base_url', 'https://api.openai.com/v1'),
            'omniroute_api_key' => (string) config('ai.omniroute_api_key', ''),
            'omniroute_base_url' => (string) config('ai.omniroute_base_url', 'http://admin-omniroute-cec098-100-104-13-10.sslip.io/v1'),
            'omniroute_model' => (string) config('ai.omniroute_model', 'agy/gemini-3.7-flash-high'),
            'ninerouter_api_key' => (string) config('ai.ninerouter_api_key', ''),
            'ninerouter_base_url' => (string) config('ai.ninerouter_base_url', 'http://localhost:20128/v1'),
            'ninerouter_model' => (string) config('ai.ninerouter_model', 'auto'),
            'ai_request_timeout' => (string) config('ai.request_timeout', 60),
            'ai_max_source_chars' => (string) config('ai.max_source_chars', 6000),
            'ai_essay_grading_enabled' => 'true',
            'ai_activity_generation_enabled' => 'true',
            'ai_system_skill' => 'omniroute_terse',
            'ai_grading_instructions' => 'Evaluate student work strictly against provided rubric criteria or expected answers. Prioritize conceptual understanding and factual accuracy. Accept paraphrased or near-synonym answers if the underlying concept is demonstrated.',
            'ai_generation_instructions' => 'Create high-quality assessment items directly grounded in the provided reference text. Ensure distractors are plausible and correct answers are factually irrefutable based on the material.',

            // AI Manager
            'ai_manager_enabled' => config('ai.manager.enabled', false) ? 'true' : 'false',
            'ai_manager_mode' => (string) config('ai.manager.mode', 'advisory'),
            'ai_manager_interval_minutes' => (string) config('ai.manager.interval_minutes', 15),
            'ai_manager_auto_grade' => config('ai.manager.auto_grade', true) ? 'true' : 'false',
            'ai_manager_self_heal_files' => config('ai.manager.self_heal_files', true) ? 'true' : 'false',
            'ai_manager_anomaly_detection' => config('ai.manager.anomaly_detection', true) ? 'true' : 'false',
            'ai_manager_confidence_threshold' => (string) config('ai.manager.confidence_threshold', 0.85),

            // Storage Configuration (S3 / Cloudflare R2)
            'storage_driver' => env('STORAGE_DRIVER', 'local'),
            'storage_s3_key' => env('AWS_ACCESS_KEY_ID', ''),
            'storage_s3_secret' => env('AWS_SECRET_ACCESS_KEY', ''),
            'storage_s3_bucket' => env('AWS_BUCKET', ''),
            'storage_s3_region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'storage_s3_endpoint' => env('AWS_ENDPOINT', ''),
            'storage_s3_public_url' => env('AWS_URL', ''),
            'storage_s3_use_path_style' => env('AWS_USE_PATH_STYLE_ENDPOINT', true) ? 'true' : 'false',

            // Security & System
            'session_lifetime_minutes' => (string) config('session.lifetime', 120),
            'max_login_attempts' => '5',
            'activity_logging_enabled' => 'true',
        ];
    }

    public function index(): Response
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        abort_unless($user->isSuperadmin(), 403);

        $defaults = self::defaultSettings();
        $stored = Setting::all()->pluck('value', 'key')->toArray();

        $merged = array_merge($defaults, $stored);

        return Inertia::render('Teacher/Settings', [
            'settings' => $merged,
            'levelRewards' => \App\Helpers\LevelHelper::allLevelRewards(),
            'environment' => [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'server_time' => now()->format('Y-m-d H:i:s T'),
                'app_env' => config('app.env'),
                'db_connection' => config('database.default'),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        abort_unless($user->isSuperadmin(), 403);

        $validated = $request->validate([
            'school_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'school_short' => ['sometimes', 'nullable', 'string', 'max:50'],
            'school_id' => ['sometimes', 'nullable', 'string', 'max:50'],
            'school_address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'school_contact' => ['sometimes', 'nullable', 'string', 'max:50'],
            'school_email' => ['sometimes', 'nullable', 'email', 'max:255'],

            'app_name' => ['sometimes', 'required', 'string', 'max:100'],
            'support_email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'payment_email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'maintenance_mode' => ['sometimes', 'nullable', 'string', 'in:true,false'],
            'maintenance_message' => ['sometimes', 'nullable', 'string', 'max:500'],
            'allow_registration' => ['sometimes', 'nullable', 'string', 'in:true,false'],

            'messenger_system_enabled' => ['sometimes', 'nullable', 'string', 'in:true,false'],
            'classmate_messaging_enabled' => ['sometimes', 'nullable', 'string', 'in:true,false'],
            'allow_chat_attachments' => ['sometimes', 'nullable', 'string', 'in:true,false'],

            'xp_passing_percentage' => ['sometimes', 'required', 'integer', 'min:1', 'max:100'],
            'xp_mastery_percentage' => ['sometimes', 'required', 'integer', 'min:1', 'max:100'],
            'xp_award_mastery' => ['sometimes', 'required', 'integer', 'min:0', 'max:1000'],
            'xp_award_pass' => ['sometimes', 'required', 'integer', 'min:0', 'max:1000'],
            'xp_award_attempt' => ['sometimes', 'required', 'integer', 'min:0', 'max:1000'],
            'xp_per_level' => ['sometimes', 'required', 'integer', 'min:10', 'max:10000'],
            'level_rewards' => ['sometimes', 'nullable', 'array'],
            'level_rewards_custom' => ['sometimes', 'nullable', 'string'],
            'exam_max_score' => ['sometimes', 'required', 'integer', 'min:1', 'max:10000'],
            'practical_max_attempts' => ['sometimes', 'required', 'integer', 'min:1', 'max:100'],

            'ai_provider' => ['sometimes', 'required', 'string', 'in:auto,gemini,openai,omniroute,9router,ninerouter,heuristic'],
            'gemini_api_key' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'gemini_model' => ['sometimes', 'nullable', 'string', 'max:100'],
            'openai_api_key' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'openai_model' => ['sometimes', 'nullable', 'string', 'max:100'],
            'openai_base_url' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'omniroute_api_key' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'omniroute_base_url' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'omniroute_model' => ['sometimes', 'nullable', 'string', 'max:100'],
            'ninerouter_api_key' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'ninerouter_base_url' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'ninerouter_model' => ['sometimes', 'nullable', 'string', 'max:100'],
            '9router_api_key' => ['sometimes', 'nullable', 'string', 'max:1000'],
            '9router_base_url' => ['sometimes', 'nullable', 'string', 'max:1000'],
            '9router_model' => ['sometimes', 'nullable', 'string', 'max:100'],
            'ai_request_timeout' => ['sometimes', 'required', 'integer', 'min:5', 'max:300'],
            'ai_max_source_chars' => ['sometimes', 'required', 'integer', 'min:500', 'max:30000'],
            'ai_essay_grading_enabled' => ['sometimes', 'nullable', 'string', 'in:true,false'],
            'ai_activity_generation_enabled' => ['sometimes', 'nullable', 'string', 'in:true,false'],
            'ai_system_skill' => ['sometimes', 'nullable', 'string', 'in:omniroute_terse,academic_strict,supportive_mentor,custom'],
            'ai_grading_instructions' => ['sometimes', 'nullable', 'string', 'max:3000'],
            'ai_generation_instructions' => ['sometimes', 'nullable', 'string', 'max:3000'],
            'ai_manager_enabled' => ['sometimes', 'nullable', 'string', 'in:true,false'],
            'ai_manager_mode' => ['sometimes', 'nullable', 'string', 'in:advisory,approval,autonomous'],
            'ai_manager_interval_minutes' => ['sometimes', 'nullable', 'integer', 'min:5', 'max:1440'],
            'ai_manager_auto_grade' => ['sometimes', 'nullable', 'string', 'in:true,false'],
            'ai_manager_self_heal_files' => ['sometimes', 'nullable', 'string', 'in:true,false'],
            'ai_manager_anomaly_detection' => ['sometimes', 'nullable', 'string', 'in:true,false'],
            'ai_manager_confidence_threshold' => ['sometimes', 'nullable', 'numeric', 'min:0.5', 'max:1.0'],

            'storage_driver' => ['sometimes', 'required', 'string', 'in:local,s3,r2'],
            'storage_s3_key' => ['sometimes', 'nullable', 'string', 'max:500'],
            'storage_s3_secret' => ['sometimes', 'nullable', 'string', 'max:500'],
            'storage_s3_bucket' => ['sometimes', 'nullable', 'string', 'max:255'],
            'storage_s3_region' => ['sometimes', 'nullable', 'string', 'max:100'],
            'storage_s3_endpoint' => ['sometimes', 'nullable', 'string', 'max:500'],
            'storage_s3_public_url' => ['sometimes', 'nullable', 'string', 'max:500'],
            'storage_s3_use_path_style' => ['sometimes', 'nullable', 'string', 'in:true,false'],

            'session_lifetime_minutes' => ['sometimes', 'required', 'integer', 'min:1', 'max:1440'],
            'max_login_attempts' => ['sometimes', 'required', 'integer', 'min:1', 'max:20'],
            'activity_logging_enabled' => ['sometimes', 'nullable', 'string', 'in:true,false'],
        ]);

        if (array_key_exists('gemini_model', $validated) && empty($validated['gemini_model'])) {
            $validated['gemini_model'] = 'gemini-1.5-flash';
        }
        if (array_key_exists('openai_model', $validated) && empty($validated['openai_model'])) {
            $validated['openai_model'] = 'gpt-4o-mini';
        }
        if (array_key_exists('openai_base_url', $validated) && empty($validated['openai_base_url'])) {
            $validated['openai_base_url'] = 'https://api.openai.com/v1';
        }
        if (array_key_exists('omniroute_model', $validated) && empty($validated['omniroute_model'])) {
            $validated['omniroute_model'] = 'agy/gemini-3.7-flash-high';
        }
        if (array_key_exists('omniroute_base_url', $validated) && empty($validated['omniroute_base_url'])) {
            $validated['omniroute_base_url'] = 'http://admin-omniroute-cec098-100-104-13-10.sslip.io/v1';
        }
        if (array_key_exists('ninerouter_model', $validated) && empty($validated['ninerouter_model'])) {
            $validated['ninerouter_model'] = 'auto';
        }
        if (array_key_exists('ninerouter_base_url', $validated) && empty($validated['ninerouter_base_url'])) {
            $validated['ninerouter_base_url'] = 'http://localhost:20128/v1';
        }

        if ($request->has('level_rewards')) {
            $rawRewards = (array) $request->input('level_rewards');
            $cleanRewards = [];
            foreach ($rawRewards as $lvl => $item) {
                $lvlNum = (int) $lvl;
                if ($lvlNum >= 1 && is_array($item)) {
                    $cleanRewards[$lvlNum] = [
                        'name' => trim((string) ($item['name'] ?? '')),
                        'description' => trim((string) ($item['description'] ?? '')),
                    ];
                }
            }
            if (!empty($cleanRewards)) {
                Setting::set('level_rewards_custom', json_encode($cleanRewards));
            }
            unset($validated['level_rewards']);
        }

        if ($request->filled('level_rewards_custom')) {
            Setting::set('level_rewards_custom', (string) $request->input('level_rewards_custom'));
            unset($validated['level_rewards_custom']);
        }

        foreach ($validated as $key => $value) {
            Setting::set($key, (string) ($value ?? ''));
        }

        ActivityLogger::log(
            action: 'settings_update',
            description: "Updated system configuration settings",
            user: $user,
            properties: ['updated_keys' => array_keys($validated)]
        );

        return back()->with('success', 'System settings updated successfully.');
    }

    public function updateAi(Request $request): JsonResponse|RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        abort_unless($user->isSuperadmin(), 403);

        $validated = $request->validate([
            'ai_provider' => ['required', 'string', 'in:auto,gemini,openai,omniroute,9router,ninerouter,heuristic'],
            'gemini_api_key' => ['nullable', 'string', 'max:1000'],
            'gemini_model' => ['nullable', 'string', 'max:100'],
            'openai_api_key' => ['nullable', 'string', 'max:1000'],
            'openai_model' => ['nullable', 'string', 'max:100'],
            'openai_base_url' => ['nullable', 'string', 'max:1000'],
            'omniroute_api_key' => ['nullable', 'string', 'max:1000'],
            'omniroute_base_url' => ['nullable', 'string', 'max:1000'],
            'omniroute_model' => ['nullable', 'string', 'max:100'],
            'ninerouter_api_key' => ['nullable', 'string', 'max:1000'],
            'ninerouter_base_url' => ['nullable', 'string', 'max:1000'],
            'ninerouter_model' => ['nullable', 'string', 'max:100'],
            '9router_api_key' => ['nullable', 'string', 'max:1000'],
            '9router_base_url' => ['nullable', 'string', 'max:1000'],
            '9router_model' => ['nullable', 'string', 'max:100'],
            'ai_request_timeout' => ['required', 'integer', 'min:5', 'max:300'],
            'ai_max_source_chars' => ['required', 'integer', 'min:500', 'max:30000'],
            'ai_essay_grading_enabled' => ['nullable', 'string', 'in:true,false'],
            'ai_activity_generation_enabled' => ['nullable', 'string', 'in:true,false'],
            'ai_system_skill' => ['nullable', 'string', 'in:omniroute_terse,academic_strict,supportive_mentor,custom'],
            'ai_grading_instructions' => ['nullable', 'string', 'max:3000'],
            'ai_generation_instructions' => ['nullable', 'string', 'max:3000'],
            'ai_manager_enabled' => ['sometimes', 'nullable', 'string', 'in:true,false'],
            'ai_manager_mode' => ['sometimes', 'nullable', 'string', 'in:advisory,approval,autonomous'],
            'ai_manager_interval_minutes' => ['sometimes', 'nullable', 'integer', 'min:5', 'max:1440'],
            'ai_manager_auto_grade' => ['sometimes', 'nullable', 'string', 'in:true,false'],
            'ai_manager_self_heal_files' => ['sometimes', 'nullable', 'string', 'in:true,false'],
            'ai_manager_anomaly_detection' => ['sometimes', 'nullable', 'string', 'in:true,false'],
            'ai_manager_confidence_threshold' => ['sometimes', 'nullable', 'numeric', 'min:0.5', 'max:1.0'],
        ]);

        if (empty($validated['gemini_model'])) {
            $validated['gemini_model'] = 'gemini-1.5-flash';
        }
        if (empty($validated['openai_model'])) {
            $validated['openai_model'] = 'gpt-4o-mini';
        }
        if (empty($validated['openai_base_url'])) {
            $validated['openai_base_url'] = 'https://api.openai.com/v1';
        }
        if (empty($validated['omniroute_model'])) {
            $validated['omniroute_model'] = 'agy/gemini-3.7-flash-high';
        }
        if (empty($validated['omniroute_base_url'])) {
            $validated['omniroute_base_url'] = 'http://admin-omniroute-cec098-100-104-13-10.sslip.io/v1';
        }
        if (empty($validated['ninerouter_model'])) {
            $validated['ninerouter_model'] = 'auto';
        }
        if (empty($validated['ninerouter_base_url'])) {
            $validated['ninerouter_base_url'] = 'http://localhost:20128/v1';
        }

        foreach ($validated as $key => $value) {
            Setting::set($key, (string) ($value ?? ''));
        }

        ActivityLogger::log(
            action: 'settings_update_ai',
            description: 'Updated AI configuration & system skills',
            user: $user,
            properties: ['updated_keys' => array_keys($validated)]
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'AI configuration and skills saved successfully.',
                'settings' => Setting::all()->pluck('value', 'key')->toArray(),
            ]);
        }

        return back()->with('success', 'AI configuration and skills saved successfully.');
    }

    public function reset(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        abort_unless($user->isSuperadmin(), 403);

        $category = $request->input('category', 'all');
        $defaults = self::defaultSettings();

        if ($category === 'all') {
            foreach ($defaults as $key => $value) {
                Setting::set($key, (string) $value);
            }
            $msg = 'All configuration settings reset to default values.';
        } else {
            // Category-specific reset
            $categoryKeys = match ($category) {
                'school' => ['school_name', 'school_short', 'school_id', 'school_address', 'school_contact', 'school_email'],
                'platform' => ['app_name', 'support_email', 'payment_email', 'maintenance_mode', 'maintenance_message', 'allow_registration'],
                'communication' => ['messenger_system_enabled', 'classmate_messaging_enabled', 'allow_chat_attachments'],
                'gamification' => ['xp_passing_percentage', 'xp_mastery_percentage', 'xp_award_mastery', 'xp_award_pass', 'xp_award_attempt', 'xp_per_level', 'exam_max_score', 'practical_max_attempts'],
                'ai' => [
                    'ai_provider',
                    'gemini_api_key',
                    'gemini_model',
                    'openai_api_key',
                    'openai_model',
                    'openai_base_url',
                    'omniroute_api_key',
                    'omniroute_base_url',
                    'omniroute_model',
                    'ninerouter_api_key',
                    'ninerouter_base_url',
                    'ninerouter_model',
                    'ai_request_timeout',
                    'ai_max_source_chars',
                    'ai_essay_grading_enabled',
                    'ai_activity_generation_enabled',
                    'ai_system_skill',
                    'ai_grading_instructions',
                    'ai_generation_instructions',
                    'ai_manager_enabled',
                    'ai_manager_mode',
                    'ai_manager_interval_minutes',
                    'ai_manager_auto_grade',
                    'ai_manager_self_heal_files',
                    'ai_manager_anomaly_detection',
                    'ai_manager_confidence_threshold',
                ],
                'storage' => [
                    'storage_driver',
                    'storage_s3_key',
                    'storage_s3_secret',
                    'storage_s3_bucket',
                    'storage_s3_region',
                    'storage_s3_endpoint',
                    'storage_s3_public_url',
                    'storage_s3_use_path_style',
                ],
                'security' => ['session_lifetime_minutes', 'max_login_attempts', 'activity_logging_enabled'],
                default => [],
            };

            foreach ($categoryKeys as $k) {
                if (isset($defaults[$k])) {
                    Setting::set($k, (string) $defaults[$k]);
                }
            }
            if ($category === 'gamification') {
                Setting::query()->where('key', 'level_rewards_custom')->delete();
            }
            $msg = "Reset {$category} configuration to default values.";
        }

        if ($category === 'all') {
            Setting::query()->where('key', 'level_rewards_custom')->delete();
        }

        ActivityLogger::log(
            action: 'settings_reset',
            description: "Reset configuration settings ({$category})",
            user: $user,
            properties: ['category' => $category]
        );

        return back()->with('success', $msg);
    }

    public function testAi(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        abort_unless($user->isSuperadmin(), 403);

        @set_time_limit(120);

        $specificProvider = $request->input('provider');
        $result = AiConfigService::testConnection($specificProvider, $request->all());

        ActivityLogger::log(
            action: 'test_ai_connection',
            description: "Tested AI connection (" . ($result['provider'] ?? 'auto') . ")",
            user: $user,
            properties: [
                'provider' => $result['provider'] ?? null,
                'success' => $result['success'] ?? false,
                'latency_ms' => $result['latency_ms'] ?? null,
            ]
        );

        return response()->json($result);
    }

    public function updateStorage(Request $request): JsonResponse|RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        abort_unless($user->isSuperadmin(), 403);

        $validated = $request->validate([
            'storage_driver' => ['required', 'string', 'in:local,s3,r2'],
            'storage_s3_key' => ['nullable', 'string', 'max:500'],
            'storage_s3_secret' => ['nullable', 'string', 'max:500'],
            'storage_s3_bucket' => ['nullable', 'string', 'max:255'],
            'storage_s3_region' => ['nullable', 'string', 'max:100'],
            'storage_s3_endpoint' => ['nullable', 'string', 'max:500'],
            'storage_s3_public_url' => ['nullable', 'string', 'max:500'],
            'storage_s3_use_path_style' => ['nullable', 'string', 'in:true,false'],
        ]);

        foreach ($validated as $key => $value) {
            Setting::set($key, (string) ($value ?? ''));
        }

        ActivityLogger::log(
            action: 'settings_update_storage',
            description: 'Updated cloud storage configuration',
            user: $user,
            properties: ['driver' => $validated['storage_driver']]
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Cloud storage configuration saved successfully.',
                'settings' => Setting::all()->pluck('value', 'key')->toArray(),
            ]);
        }

        return back()->with('success', 'Cloud storage configuration saved successfully.');
    }

    public function testStorage(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        abort_unless($user->isSuperadmin(), 403);

        @set_time_limit(30);

        $result = CloudStorageService::testConnection($request->all());

        ActivityLogger::log(
            action: 'test_storage_connection',
            description: 'Tested storage connection (' . ($request->input('storage_driver') ?: CloudStorageService::getDriver()) . ')',
            user: $user,
            properties: [
                'driver' => $request->input('storage_driver') ?: CloudStorageService::getDriver(),
                'success' => $result['success'] ?? false,
                'latency_ms' => $result['latency_ms'] ?? null,
            ]
        );

        return response()->json($result);
    }
}
