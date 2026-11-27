<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;

class AiConfigService
{
    public static function getProvider(): string
    {
        return (string) Setting::get('ai_provider', config('ai.provider', 'auto'));
    }

    public static function getGeminiApiKey(?string $override = null): ?string
    {
        if (! empty($override)) {
            return trim($override);
        }

        $val = Setting::get('gemini_api_key');
        if (! empty($val)) {
            return trim((string) $val);
        }

        $configKey = config('ai.gemini_api_key');

        return ! empty($configKey) ? trim((string) $configKey) : null;
    }

    public static function getGeminiModel(?string $override = null): string
    {
        if (! empty($override)) {
            $cleaned = trim($override);
            return $cleaned === 'gemini-2.0-flash' ? 'gemini-1.5-flash' : $cleaned;
        }

        $model = (string) Setting::get('gemini_model', config('ai.gemini_model', 'gemini-1.5-flash'));
        if ($model === 'gemini-2.0-flash' || empty($model)) {
            Setting::set('gemini_model', 'gemini-1.5-flash');
            return 'gemini-1.5-flash';
        }

        return $model;
    }

    public static function getOpenAiApiKey(?string $override = null): ?string
    {
        if (! empty($override)) {
            return trim($override);
        }

        $val = Setting::get('openai_api_key');
        if (! empty($val)) {
            return trim((string) $val);
        }

        $configKey = config('ai.openai_api_key');

        return ! empty($configKey) ? trim((string) $configKey) : null;
    }

    public static function getOpenAiModel(?string $override = null): string
    {
        if (! empty($override)) {
            return trim($override);
        }

        return (string) Setting::get('openai_model', config('ai.openai_model', 'gpt-4o-mini'));
    }

    public static function getOpenAiBaseUrl(?string $override = null): string
    {
        if (! empty($override)) {
            return rtrim(trim($override), '/');
        }

        return rtrim((string) Setting::get('openai_base_url', config('ai.openai_base_url', 'https://api.openai.com/v1')), '/');
    }

    public static function getOmniRouteApiKey(?string $override = null): ?string
    {
        if (! empty($override)) {
            return trim($override);
        }

        $val = Setting::get('omniroute_api_key');
        if (! empty($val)) {
            return trim((string) $val);
        }

        $configKey = config('ai.omniroute_api_key');

        return ! empty($configKey) ? trim((string) $configKey) : null;
    }

    public static function getOmniRouteBaseUrl(?string $override = null): string
    {
        $url = ! empty($override)
            ? trim($override)
            : (string) Setting::get('omniroute_base_url', config('ai.omniroute_base_url', 'http://admin-omniroute-cec098-100-104-13-10.sslip.io/v1'));

        $url = rtrim($url, '/');
        if (str_ends_with($url, '/chat/completions')) {
            $url = substr($url, 0, -strlen('/chat/completions'));
        }
        if (! str_ends_with($url, '/v1')) {
            $url .= '/v1';
        }

        return $url;
    }

    public static function getOmniRouteModel(?string $override = null): string
    {
        if (! empty($override)) {
            return trim($override);
        }

        return (string) Setting::get('omniroute_model', config('ai.omniroute_model', 'agy/gemini-3.7-flash-high'));
    }

    public static function getNineRouterApiKey(?string $override = null): ?string
    {
        if (! empty($override)) {
            return trim($override);
        }

        $val = Setting::get('ninerouter_api_key', Setting::get('9router_api_key'));
        if (! empty($val)) {
            return trim((string) $val);
        }

        $configKey = config('ai.ninerouter_api_key');

        return ! empty($configKey) ? trim((string) $configKey) : null;
    }

    public static function getNineRouterBaseUrl(?string $override = null): string
    {
        $url = ! empty($override)
            ? trim($override)
            : (string) Setting::get('ninerouter_base_url', Setting::get('9router_base_url', config('ai.ninerouter_base_url', 'http://localhost:20128/v1')));

        $url = rtrim($url, '/');
        if (str_ends_with($url, '/chat/completions')) {
            $url = substr($url, 0, -strlen('/chat/completions'));
        }
        if (! str_ends_with($url, '/v1')) {
            $url .= '/v1';
        }

        return $url;
    }

    public static function getNineRouterModel(?string $override = null): string
    {
        if (! empty($override)) {
            return trim($override);
        }

        $val = Setting::get('ninerouter_model', Setting::get('9router_model'));
        if (! empty($val)) {
            return trim((string) $val);
        }

        return (string) config('ai.ninerouter_model', 'auto');
    }

    public static function getTimeout(): int
    {
        return (int) Setting::get('ai_request_timeout', config('ai.request_timeout', 60));
    }

    public static function getMaxSourceChars(): int
    {
        return (int) Setting::get('ai_max_source_chars', config('ai.max_source_chars', 30000));
    }

    public static function isEssayGradingEnabled(): bool
    {
        return Setting::get('ai_essay_grading_enabled', 'true') !== 'false';
    }

    public static function isActivityGenerationEnabled(): bool
    {
        return Setting::get('ai_activity_generation_enabled', 'true') !== 'false';
    }

    public static function getSystemSkill(): string
    {
        return (string) Setting::get('ai_system_skill', 'omniroute_terse');
    }

    public static function getGradingInstructions(): string
    {
        $custom = Setting::get('ai_grading_instructions');
        if (! empty($custom)) {
            return trim((string) $custom);
        }

        return 'Evaluate student work with priority on conceptual understanding. Accept symbols, keyboard shortcuts, alternate terms, near-synonyms, minor typos, and paraphrased answers as correct if the underlying concept is demonstrated.';
    }

    public static function getGenerationInstructions(): string
    {
        $custom = Setting::get('ai_generation_instructions');
        if (! empty($custom)) {
            return trim((string) $custom);
        }

        return 'Create high-quality assessment items directly grounded in the provided reference text. Ensure distractors are plausible and correct answers are factually irrefutable based on the material.';
    }

    public static function getSkillPrompt(): string
    {
        $skill = self::getSystemSkill();

        return match ($skill) {
            'academic_strict' => 'You are an academic evaluator. Accept alternate terminology and symbols that represent the expected concept.',
            'supportive_mentor' => 'You are an encouraging, supportive educator. Provide constructive feedback while fairly evaluating against criteria, approving symbols and near-synonyms.',
            'custom' => self::getGradingInstructions(),
            default => 'You are an objective educator using OmniRoute style: concise, direct, zero pleasantries or fluff, accepting near concepts, alternate terms, and symbols that align with the criteria.',
        };
    }

    public static function resolveActiveProvider(): string
    {
        $configured = strtolower(self::getProvider());

        if (in_array($configured, ['gemini', 'openai', 'omniroute', '9router', 'ninerouter', 'heuristic'], true)) {
            return ($configured === 'ninerouter') ? '9router' : $configured;
        }

        if (self::getOmniRouteApiKey()) {
            return 'omniroute';
        }

        if (self::getNineRouterApiKey()) {
            return '9router';
        }

        if (self::getGeminiApiKey()) {
            return 'gemini';
        }

        if (self::getOpenAiApiKey()) {
            return 'openai';
        }

        return 'heuristic';
    }

    /**
     * Return list of configured AI providers that have credentials,
     * prioritizing the active/preferred provider first.
     *
     * @return array<int, string>
     */
    public static function getAvailableAiProviders(): array
    {
        $active = self::resolveActiveProvider();
        $providers = [];

        if ($active !== 'heuristic') {
            $providers[] = $active;
        }

        $allProviders = ['omniroute', '9router', 'gemini', 'openai'];
        foreach ($allProviders as $candidate) {
            if (in_array($candidate, $providers, true)) {
                continue;
            }
            $hasKey = match ($candidate) {
                'gemini' => ! empty(self::getGeminiApiKey()),
                'openai' => ! empty(self::getOpenAiApiKey()),
                'omniroute' => ! empty(self::getOmniRouteApiKey()),
                '9router' => ! empty(self::getNineRouterApiKey()),
                default => false,
            };
            if ($hasKey) {
                $providers[] = $candidate;
            }
        }

        return $providers;
    }

    /**
     * Check if an HTTP status code or response body represents a transient upstream failure
     * (e.g. 503 resource pressure, 429 rate limit, 502/504 gateway issues).
     */
    public static function isTransientAiError(int $statusCode, ?string $body = null): bool
    {
        if (in_array($statusCode, [429, 500, 502, 503, 504], true)) {
            return true;
        }

        if ($body !== null && $body !== '') {
            $lower = strtolower($body);
            if (str_contains($lower, 'resource_pressure')
                || str_contains($lower, 'service temporarily unavailable')
                || str_contains($lower, 'temporarily unavailable')
                || str_contains($lower, 'rate limit')
                || str_contains($lower, 'rate_limit')
                || str_contains($lower, 'too many requests')
                || str_contains($lower, 'server_error')
                || str_contains($lower, 'overloaded')
                || str_contains($lower, 'retry shortly')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sleep with backoff between AI retries. Drops to 10ms in test environment.
     */
    public static function retrySleep(int $attempt): void
    {
        if (app()->runningUnitTests()) {
            usleep(10000);
            return;
        }

        $ms = max(500, min(3000, $attempt * 1000));
        usleep($ms * 1000);
    }

    /**
     * Test connection to configured or specified provider with custom form overrides.
     */
    public static function testConnection(?string $specificProvider = null, array $overrides = []): array
    {
        $provider = $specificProvider ?: self::resolveActiveProvider();
        $startTime = microtime(true);

        if ($provider === 'heuristic') {
            return [
                'success' => true,
                'provider' => 'heuristic',
                'model' => 'offline-rules',
                'latency_ms' => 1,
                'message' => 'Built-in offline heuristic generator is operational (no API key required).',
            ];
        }

        $timeout = min(max(15, (int) ($overrides['ai_request_timeout'] ?? self::getTimeout())), 45);

        try {
            if ($provider === 'gemini') {
                $key = self::getGeminiApiKey($overrides['gemini_api_key'] ?? null);
                if (! $key) {
                    return [
                        'success' => false,
                        'provider' => 'gemini',
                        'model' => 'N/A',
                        'message' => 'Google Gemini API key is missing. Please enter your Gemini API key from AI Studio.',
                    ];
                }

                $model = self::getGeminiModel($overrides['gemini_model'] ?? null);

                $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$key}";
                $payload = [
                    'contents' => [['parts' => [['text' => 'Respond with JSON: {"status": "ok", "message": "EduPulse AI connection verified"}']]]],
                    'generationConfig' => ['responseMimeType' => 'application/json', 'maxOutputTokens' => 128],
                ];

                $res = Http::timeout($timeout)->post($endpoint, $payload);

                // If chosen model returned 404 (e.g. deprecated preview), try fallback to gemini-1.5-flash
                if ($res->status() === 404 && $model !== 'gemini-1.5-flash') {
                    $fallbackEndpoint = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$key}";
                    $fallbackRes = Http::timeout($timeout)->post($fallbackEndpoint, $payload);

                    if ($fallbackRes->successful()) {
                        Setting::set('gemini_model', 'gemini-1.5-flash');
                        $duration = round((microtime(true) - $startTime) * 1000);

                        return [
                            'success' => true,
                            'provider' => 'gemini',
                            'model' => 'gemini-1.5-flash',
                            'switched_model' => 'gemini-1.5-flash',
                            'latency_ms' => $duration,
                            'message' => "Model '{$model}' is no longer available from Google. Successfully auto-switched to 'gemini-1.5-flash' ({$duration}ms).",
                        ];
                    }
                }

                if (! $res->successful()) {
                    $errorMsg = $res->json('error.message') ?? $res->body();
                    $duration = round((microtime(true) - $startTime) * 1000);

                    return [
                        'success' => false,
                        'provider' => 'gemini',
                        'model' => $model,
                        'latency_ms' => $duration,
                        'message' => "Gemini API error ({$res->status()}): {$errorMsg}",
                    ];
                }

                $duration = round((microtime(true) - $startTime) * 1000);

                return [
                    'success' => true,
                    'provider' => 'gemini',
                    'model' => $model,
                    'latency_ms' => $duration,
                    'message' => "Successfully connected to Google Gemini ({$model}) in {$duration}ms.",
                ];
            }

            if ($provider === '9router' || $provider === 'ninerouter') {
                $key = self::getNineRouterApiKey($overrides['ninerouter_api_key'] ?? ($overrides['9router_api_key'] ?? null));
                $model = self::getNineRouterModel($overrides['ninerouter_model'] ?? ($overrides['9router_model'] ?? null));
                $baseUrl = self::getNineRouterBaseUrl($overrides['ninerouter_base_url'] ?? ($overrides['9router_base_url'] ?? null));
                $endpoint = rtrim($baseUrl, '/') . '/chat/completions';

                $headers = [
                    'Accept' => 'application/json',
                ];
                if (! empty($key)) {
                    $headers['Authorization'] = 'Bearer ' . $key;
                    $headers['x-api-key'] = $key;
                } else {
                    $headers['Authorization'] = 'Bearer sk-9router';
                }

                // Step 1: Query /models to verify connectivity & authentication fast (max 10s)
                $availableModels = [];
                try {
                    $modelsEndpoint = rtrim($baseUrl, '/') . '/models';
                    $modelsRes = Http::timeout(10)
                        ->connectTimeout(8)
                        ->withHeaders($headers)
                        ->get($modelsEndpoint);

                    if ($modelsRes->status() === 401) {
                        $errorMsg = $modelsRes->json('error.message') ?? 'Invalid API key';
                        return [
                            'success' => false,
                            'provider' => '9router',
                            'model' => $model,
                            'latency_ms' => round((microtime(true) - $startTime) * 1000),
                            'message' => "9Router API error (401): {$errorMsg}",
                        ];
                    }

                    if ($modelsRes->status() === 403) {
                        return [
                            'success' => false,
                            'provider' => '9router',
                            'model' => $model,
                            'latency_ms' => round((microtime(true) - $startTime) * 1000),
                            'message' => '9Router API error (403): Forbidden. Check API key permissions.',
                        ];
                    }

                    if ($modelsRes->successful()) {
                        $rawModels = $modelsRes->json('data', []);
                        if (is_array($rawModels)) {
                            foreach ($rawModels as $m) {
                                if (is_array($m) && isset($m['id'])) {
                                    $availableModels[] = (string) $m['id'];
                                } elseif (is_string($m)) {
                                    $availableModels[] = $m;
                                }
                            }
                        }
                    }
                } catch (\Throwable $me) {
                    $errMsg = $me->getMessage();
                    if (str_contains($errMsg, 'cURL error 28') || str_contains($errMsg, 'Connection refused')) {
                        return [
                            'success' => false,
                            'provider' => '9router',
                            'model' => $model,
                            'latency_ms' => round((microtime(true) - $startTime) * 1000),
                            'message' => "Could not reach 9Router host at {$baseUrl}. Check if 9Router server is running: {$errMsg}",
                        ];
                    }
                }

                // If models list was returned, verify if requested model exists
                if (! empty($availableModels) && $model !== 'auto' && ! in_array($model, $availableModels, true)) {
                    $duration = round((microtime(true) - $startTime) * 1000);
                    $modelsSample = implode(', ', array_slice($availableModels, 0, 8));

                    return [
                        'success' => false,
                        'provider' => '9router',
                        'model' => $model,
                        'available_models' => $availableModels,
                        'latency_ms' => $duration,
                        'message' => "9Router connected successfully, but model '{$model}' is not registered on your router. Available models: {$modelsSample}",
                    ];
                }

                // Step 2: Test /chat/completions (use 25s timeout for fast response)
                $chatTimeout = min(max(20, (int) ($overrides['ai_request_timeout'] ?? 25)), 35);

                try {
                    $res = Http::timeout($chatTimeout)
                        ->connectTimeout(8)
                        ->withHeaders($headers)
                        ->post($endpoint, [
                            'model' => $model,
                            'messages' => [
                                ['role' => 'user', 'content' => 'Say OK.'],
                            ],
                            'max_tokens' => 16,
                            'stream' => false,
                        ]);

                    if (! $res->successful() && self::isTransientAiError($res->status(), $res->body())) {
                        self::retrySleep(1);
                        $res = Http::timeout($chatTimeout)
                            ->connectTimeout(8)
                            ->withHeaders($headers)
                            ->post($endpoint, [
                                'model' => $model,
                                'messages' => [
                                    ['role' => 'user', 'content' => 'Say OK.'],
                                ],
                                'max_tokens' => 16,
                                'stream' => false,
                            ]);
                    }

                    if (! $res->successful()) {
                        $errorMsg = $res->json('error.message') ?? $res->body();
                        $duration = round((microtime(true) - $startTime) * 1000);

                        $msg = self::isTransientAiError($res->status(), $res->body())
                            ? "9Router API is reachable, but temporarily experiencing resource pressure ({$res->status()}: {$errorMsg}). Please retry shortly."
                            : "9Router API error ({$res->status()}): {$errorMsg}";

                        return [
                            'success' => false,
                            'provider' => '9router',
                            'model' => $model,
                            'available_models' => $availableModels,
                            'latency_ms' => $duration,
                            'message' => $msg,
                        ];
                    }

                    $duration = round((microtime(true) - $startTime) * 1000);

                    return [
                        'success' => true,
                        'provider' => '9router',
                        'model' => $model,
                        'available_models' => $availableModels,
                        'latency_ms' => $duration,
                        'message' => "Successfully connected to 9Router ({$model}) in {$duration}ms.",
                    ];
                } catch (\Throwable $ce) {
                    $duration = round((microtime(true) - $startTime) * 1000);
                    $modelsInfo = ! empty($availableModels)
                        ? ' Available models: ' . implode(', ', array_slice($availableModels, 0, 6))
                        : '';

                    return [
                        'success' => false,
                        'provider' => '9router',
                        'model' => $model,
                        'available_models' => $availableModels,
                        'latency_ms' => $duration,
                        'message' => "9Router online and authenticated, but model '{$model}' timed out after {$chatTimeout}s with 0 bytes. The model backend or agent worker is not responding.{$modelsInfo}",
                    ];
                }
            }

            if ($provider === 'omniroute') {
                $key = self::getOmniRouteApiKey($overrides['omniroute_api_key'] ?? null);
                if (! $key) {
                    return [
                        'success' => false,
                        'provider' => 'omniroute',
                        'model' => 'N/A',
                        'message' => 'OmniRoute API key is missing. Please enter your OmniRoute API key.',
                    ];
                }

                $model = self::getOmniRouteModel($overrides['omniroute_model'] ?? null);
                $baseUrl = self::getOmniRouteBaseUrl($overrides['omniroute_base_url'] ?? null);
                $endpoint = rtrim($baseUrl, '/') . '/chat/completions';

                $headers = [
                    'Authorization' => 'Bearer ' . $key,
                    'x-api-key' => $key,
                    'Accept' => 'application/json',
                ];

                // Step 1: Query /models to verify connectivity & authentication fast (max 10s)
                $availableModels = [];
                try {
                    $modelsEndpoint = rtrim($baseUrl, '/') . '/models';
                    $modelsRes = Http::timeout(10)
                        ->connectTimeout(8)
                        ->withHeaders($headers)
                        ->get($modelsEndpoint);

                    if ($modelsRes->status() === 401) {
                        $errorMsg = $modelsRes->json('error.message') ?? 'Invalid API key';
                        return [
                            'success' => false,
                            'provider' => 'omniroute',
                            'model' => $model,
                            'latency_ms' => round((microtime(true) - $startTime) * 1000),
                            'message' => "OmniRoute API error (401): {$errorMsg}",
                        ];
                    }

                    if ($modelsRes->status() === 403) {
                        return [
                            'success' => false,
                            'provider' => 'omniroute',
                            'model' => $model,
                            'latency_ms' => round((microtime(true) - $startTime) * 1000),
                            'message' => 'OmniRoute API error (403): Forbidden. Check API key permissions.',
                        ];
                    }

                    if ($modelsRes->successful()) {
                        $rawModels = $modelsRes->json('data', []);
                        if (is_array($rawModels)) {
                            foreach ($rawModels as $m) {
                                if (is_array($m) && isset($m['id'])) {
                                    $availableModels[] = (string) $m['id'];
                                } elseif (is_string($m)) {
                                    $availableModels[] = $m;
                                }
                            }
                        }
                    }
                } catch (\Throwable $me) {
                    $errMsg = $me->getMessage();
                    if (str_contains($errMsg, 'cURL error 28') || str_contains($errMsg, 'Connection refused')) {
                        return [
                            'success' => false,
                            'provider' => 'omniroute',
                            'model' => $model,
                            'latency_ms' => round((microtime(true) - $startTime) * 1000),
                            'message' => "Could not reach OmniRoute host at {$baseUrl}. Check network/Tailscale connection: {$errMsg}",
                        ];
                    }
                }

                // If models list was returned, verify if requested model exists
                if (! empty($availableModels) && ! in_array($model, $availableModels, true)) {
                    $duration = round((microtime(true) - $startTime) * 1000);
                    $modelsSample = implode(', ', array_slice($availableModels, 0, 8));

                    return [
                        'success' => false,
                        'provider' => 'omniroute',
                        'model' => $model,
                        'available_models' => $availableModels,
                        'latency_ms' => $duration,
                        'message' => "OmniRoute connected successfully, but model '{$model}' is not registered on your server. Available models: {$modelsSample}",
                    ];
                }

                // Step 2: Test /chat/completions (use 25s timeout for fast response)
                $chatTimeout = min(max(20, (int) ($overrides['ai_request_timeout'] ?? 25)), 35);

                try {
                    $res = Http::timeout($chatTimeout)
                        ->connectTimeout(8)
                        ->withHeaders($headers)
                        ->post($endpoint, [
                            'model' => $model,
                            'messages' => [
                                ['role' => 'user', 'content' => 'Say OK.'],
                            ],
                            'max_tokens' => 16,
                            'stream' => false,
                        ]);

                    if (! $res->successful() && self::isTransientAiError($res->status(), $res->body())) {
                        self::retrySleep(1);
                        $res = Http::timeout($chatTimeout)
                            ->connectTimeout(8)
                            ->withHeaders($headers)
                            ->post($endpoint, [
                                'model' => $model,
                                'messages' => [
                                    ['role' => 'user', 'content' => 'Say OK.'],
                                ],
                                'max_tokens' => 16,
                                'stream' => false,
                            ]);
                    }

                    if (! $res->successful()) {
                        $errorMsg = $res->json('error.message') ?? $res->body();
                        $duration = round((microtime(true) - $startTime) * 1000);

                        $msg = self::isTransientAiError($res->status(), $res->body())
                            ? "OmniRoute API is reachable, but temporarily experiencing resource pressure ({$res->status()}: {$errorMsg}). Please retry shortly."
                            : "OmniRoute API error ({$res->status()}): {$errorMsg}";

                        return [
                            'success' => false,
                            'provider' => 'omniroute',
                            'model' => $model,
                            'available_models' => $availableModels,
                            'latency_ms' => $duration,
                            'message' => $msg,
                        ];
                    }

                    $duration = round((microtime(true) - $startTime) * 1000);

                    return [
                        'success' => true,
                        'provider' => 'omniroute',
                        'model' => $model,
                        'available_models' => $availableModels,
                        'latency_ms' => $duration,
                        'message' => "Successfully connected to OmniRoute ({$model}) in {$duration}ms.",
                    ];
                } catch (\Throwable $ce) {
                    $duration = round((microtime(true) - $startTime) * 1000);
                    $modelsInfo = ! empty($availableModels)
                        ? ' Available models: ' . implode(', ', array_slice($availableModels, 0, 6))
                        : '';

                    return [
                        'success' => false,
                        'provider' => 'omniroute',
                        'model' => $model,
                        'available_models' => $availableModels,
                        'latency_ms' => $duration,
                        'message' => "OmniRoute online and authenticated, but model '{$model}' timed out after {$chatTimeout}s with 0 bytes. The model backend or agent worker is not responding.{$modelsInfo}",
                    ];
                }
            }

            if ($provider === 'openai') {
                $key = self::getOpenAiApiKey($overrides['openai_api_key'] ?? null);
                if (! $key) {
                    return [
                        'success' => false,
                        'provider' => 'openai',
                        'model' => 'N/A',
                        'message' => 'OpenAI API key is missing. Please enter your API key from platform.openai.com.',
                    ];
                }

                $model = self::getOpenAiModel($overrides['openai_model'] ?? null);
                $baseUrl = self::getOpenAiBaseUrl($overrides['openai_base_url'] ?? null);
                $endpoint = rtrim($baseUrl, '/') . '/chat/completions';

                $res = Http::timeout($timeout)
                    ->withToken($key)
                    ->post($endpoint, [
                        'model' => $model,
                        'response_format' => ['type' => 'json_object'],
                        'messages' => [
                            ['role' => 'user', 'content' => 'Respond with JSON: {"status": "ok", "message": "EduPulse AI connection verified"}'],
                        ],
                        'max_tokens' => 128,
                    ]);

                if (! $res->successful()) {
                    $errorMsg = $res->json('error.message') ?? $res->body();
                    $duration = round((microtime(true) - $startTime) * 1000);

                    return [
                        'success' => false,
                        'provider' => 'openai',
                        'model' => $model,
                        'latency_ms' => $duration,
                        'message' => "OpenAI API error ({$res->status()}): {$errorMsg}",
                    ];
                }

                $duration = round((microtime(true) - $startTime) * 1000);

                return [
                    'success' => true,
                    'provider' => 'openai',
                    'model' => $model,
                    'latency_ms' => $duration,
                    'message' => "Successfully connected to OpenAI ({$model}) in {$duration}ms.",
                ];
            }

            return [
                'success' => false,
                'provider' => $provider,
                'model' => 'N/A',
                'message' => "Unknown AI provider '{$provider}'.",
            ];
        } catch (\Throwable $e) {
            $duration = round((microtime(true) - $startTime) * 1000);
            $fallbackModel = match ($provider) {
                'omniroute' => self::getOmniRouteModel($overrides['omniroute_model'] ?? null),
                '9router', 'ninerouter' => self::getNineRouterModel($overrides['ninerouter_model'] ?? ($overrides['9router_model'] ?? null)),
                'gemini' => self::getGeminiModel($overrides['gemini_model'] ?? null),
                'openai' => self::getOpenAiModel($overrides['openai_model'] ?? null),
                default => 'N/A',
            };

            return [
                'success' => false,
                'provider' => $provider,
                'model' => $fallbackModel,
                'latency_ms' => $duration,
                'message' => "Connection test failed: {$e->getMessage()}",
            ];
        }
    }

    public static function isManagerEnabled(): bool
    {
        $val = Setting::get('ai_manager_enabled');
        if ($val !== null) {
            return filter_var($val, FILTER_VALIDATE_BOOLEAN);
        }

        return (bool) config('ai.manager.enabled', false);
    }

    public static function getManagerMode(): string
    {
        $mode = (string) Setting::get('ai_manager_mode', config('ai.manager.mode', 'advisory'));
        if (! in_array($mode, ['advisory', 'approval', 'autonomous'], true)) {
            return 'advisory';
        }

        return $mode;
    }

    public static function getManagerInterval(): int
    {
        return max(5, (int) Setting::get('ai_manager_interval_minutes', config('ai.manager.interval_minutes', 15)));
    }

    public static function isManagerAutoGradeEnabled(): bool
    {
        $val = Setting::get('ai_manager_auto_grade');
        if ($val !== null) {
            return filter_var($val, FILTER_VALIDATE_BOOLEAN);
        }

        return (bool) config('ai.manager.auto_grade', true);
    }

    public static function isManagerSelfHealEnabled(): bool
    {
        $val = Setting::get('ai_manager_self_heal_files');
        if ($val !== null) {
            return filter_var($val, FILTER_VALIDATE_BOOLEAN);
        }

        return (bool) config('ai.manager.self_heal_files', true);
    }

    public static function isManagerAnomalyDetectionEnabled(): bool
    {
        $val = Setting::get('ai_manager_anomaly_detection');
        if ($val !== null) {
            return filter_var($val, FILTER_VALIDATE_BOOLEAN);
        }

        return (bool) config('ai.manager.anomaly_detection', true);
    }

    public static function isManagerGameReopenEnabled(): bool
    {
        $val = Setting::get('ai_manager_game_reopen');
        if ($val !== null) {
            return filter_var($val, FILTER_VALIDATE_BOOLEAN);
        }

        return (bool) config('ai.manager.game_reopen', true);
    }

    public static function getManagerConfidenceThreshold(): float
    {
        $val = Setting::get('ai_manager_confidence_threshold');
        if ($val !== null && is_numeric($val)) {
            return min(1.0, max(0.5, (float) $val));
        }

        return (float) config('ai.manager.confidence_threshold', 0.85);
    }
}
