<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class QuizGeneratorService
{
    public const ACTIVITIES = ['quiz', 'seatwork', 'practical', 'exam', 'game'];

    public const GAME_TYPES = [
        'flashcard', 'quiz', 'truefalse', 'fillblank',
        'fourpics', 'crossword', 'wordsearch', 'codepuzzle',
        'wordjumble', 'colorharmony', 'memorymatch', 'hangman',
        'speedquiz', 'dragdrop', 'ordering',
    ];

    /**
     * Generate quiz questions from source text.
     * Returns ['provider' => string, 'questions' => array].
     * Always falls back to the offline heuristic generator.
     */
    public static function generate(string $text, int $count = 5, array $types = ['multiple_choice', 'true_false']): array
    {
        return self::generateFor('quiz', $text, $count, $types);
    }

    /**
     * Generate draft content for any activity type.
     * Returns ['provider' => string, 'items' => array] where items are
     * shaped for that activity's store endpoint.
     */
    public static function generateFor(string $activity, string $text, int $count = 5, array $types = [], string $gameType = 'flashcard'): array
    {
        if (!in_array($activity, self::ACTIVITIES, true)) {
            $activity = 'quiz';
        }

        $count = max(1, min(50, $count));
        $text = trim(mb_substr($text, 0, AiConfigService::getMaxSourceChars()));

        if ($text === '') {
            return ['provider' => 'none', 'items' => []];
        }

        // For curriculum activities, use rich document ingestion & curriculum generation engine
        if (in_array($activity, ['quiz', 'seatwork', 'practical', 'exam'], true)) {
            $parsed = self::parseOrGenerateDocument($activity, $text, $count, $types, 'generate');
            if (!empty($parsed['items'])) {
                return ['provider' => $parsed['provider'], 'items' => $parsed['items']];
            }
        }

        if (AiConfigService::isActivityGenerationEnabled()) {
            $providersToTry = AiConfigService::getAvailableAiProviders();
            foreach ($providersToTry as $prov) {
                try {
                    $items = self::viaAi($prov, $activity, $text, $count, $types, $gameType);
                    $items = self::normalizeFor($activity, $items, $gameType, $count);
                    if ($items !== []) {
                        return ['provider' => $prov, 'items' => $items];
                    }
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        }

        return ['provider' => 'heuristic', 'items' => self::viaHeuristicFor($activity, $text, $count, $types, $gameType)];
    }

    public static function resolveProvider(): string
    {
        return AiConfigService::resolveActiveProvider();
    }

    private static function prompt(string $text, int $count, array $types): string
    {
        return self::promptFor('quiz', $text, $count, $types, 'flashcard');
    }

    private static function promptFor(string $activity, string $text, int $count, array $types, string $gameType): string
    {
        $customGuide = AiConfigService::getGenerationInstructions();

        $head = "You are an expert curriculum and assessment designer.\n\n"
            . "CRITICAL INSTRUCTIONS:\n"
            . "1. STRICT GROUNDING ON REFERENCE MATERIAL: You MUST create all content STRICTLY and DIRECTLY using the provided reference material below. Every question, correct answer, distractor, explanation, and criterion must be factually supported by the text. Do NOT hallucinate, assume external information, or generate generic questions unrelated to the text.\n"
            . "2. EXACT QUANTITY: Generate EXACTLY {$count} {$activity} items. Do not generate fewer or more than {$count} items.\n"
            . "3. ACCURACY: Ensure the correct answer is undeniably accurate according to the provided material.\n"
            . "4. CLEAN OUTPUT: Return ONLY a valid JSON object matching the exact schema specified below. Zero commentary, zero markdown code fences, zero conversational preamble.\n"
            . "5. CURRICULUM GUIDELINES: {$customGuide}\n\n";

        if ($activity === 'practical') {
            return $head . "Task: Create EXACTLY {$count} rubric criteria for a hands-on practical assessment covering the skills taught in the reference material.\n"
                . "JSON Schema:\n"
                . '{"criteria": [{"criterion_name": "...", "description": "...", "max_points": 25}]}' . "\n\n"
                . "Rules:\n"
                . "- criterion_name: short, specific skill title from the material.\n"
                . "- description: one clear sentence explaining what the student must demonstrate based on the material.\n"
                . "- max_points: positive integer points.\n"
                . "- Array 'criteria' must contain exactly {$count} items.\n\n"
                . "Reference Material:\n{$text}";
        }

        if ($activity === 'game') {
            $shapes = [
                'flashcard' => '{"question": "Key concept or question from material", "answer": "Exact definition or fact from material"}',
                'quiz' => '{"question": "Question based on material", "answer": "Correct answer", "options": ["Option 1", "Option 2", "Option 3", "Option 4"]}',
                'truefalse' => '{"question": "Factual statement from material", "answer": "True|False"}',
                'fillblank' => '{"question": "Statement from material with ____ blank", "answer": "Missing term"}',
                'fourpics' => '{"question": "Concept clue", "answer": "TARGETWORD", "options": ["Visual hint 1", "Visual hint 2", "Visual hint 3", "Visual hint 4"]}',
                'crossword' => '{"question": "Crossword clue description", "answer": "WORD"}',
                'wordsearch' => '{"question": "Word search clue or category", "answer": "KEYWORD"}',
                'codepuzzle' => '{"question": "Sequential code step description", "answer": "line of code"}',
                'wordjumble' => '{"question": "Clue for jumbled term", "answer": "WORD"}',
                'memorymatch' => '{"question": "Term or concept", "answer": "Corresponding definition or formula"}',
                'hangman' => '{"question": "Category or hint", "answer": "SECRETWORD"}',
                'speedquiz' => '{"question": "Fast-paced question", "answer": "Correct answer", "options": ["Option 1", "Option 2", "Option 3", "Option 4"]}',
                'dragdrop' => '{"question": "Item to place", "answer": "Target category"}',
                'ordering' => '{"question": "Process step", "answer": "Step 1"}',
            ];
            $shape = $shapes[$gameType] ?? $shapes['flashcard'];

            return $head . "Task: Create EXACTLY {$count} {$gameType} game cards based on the reference material.\n"
                . "JSON Schema:\n"
                . '{"cards": [' . $shape . ']}' . "\n\n"
                . "Rules:\n"
                . "- For quiz and speedquiz cards: include exactly 4 options with the answer being one of them.\n"
                . "- For fourpics cards: answer must be a single clean word in UPPERCASE and options must contain 4 distinct descriptive hints or image keywords.\n"
                . "- For crossword, wordsearch, wordjumble, and hangman cards: answer must be a single UPPERCASE word without spaces.\n"
                . "- For codepuzzle cards: answer should be a realistic line of code or step in execution order.\n"
                . "- For truefalse cards: answer must be 'True' or 'False'.\n"
                . "- Array 'cards' must contain exactly {$count} items.\n\n"
                . "Reference Material:\n{$text}";
        }

        if ($activity === 'seatwork') {
            $typeList = implode(', ', $types);

            return $head . "Task: Create EXACTLY {$count} seatwork assessment questions using these types: {$typeList}.\n"
                . "JSON Schema:\n"
                . '{"questions": [{"question_text": "...", "type": "identification|enumeration|true_false|multiple_choice|matching|essay", "points": 1, '
                . '"correct_answer": "exact answer for identification", "enum_items": ["item1", "item2"], "criteria": "rubric for essay", '
                . '"options": [{"option_text": "...", "is_correct": true|false}], '
                . '"matching_pairs": [{"left_text": "...", "right_text": "..."}]}]}' . "\n\n"
                . "Rules:\n"
                . "- multiple_choice must have exactly 4 options with exactly 1 marked is_correct: true.\n"
                . "- true_false must have True and False options with exactly 1 marked is_correct: true.\n"
                . "- identification must provide the exact correct_answer from the text.\n"
                . "- enumeration must list 2-4 items in enum_items.\n"
                . "- matching must list 2-4 pairs in matching_pairs.\n"
                . "- essay must provide specific grading criteria in criteria.\n"
                . "- Array 'questions' must contain exactly {$count} items.\n\n"
                . "Reference Material:\n{$text}";
        }

        // quiz + exam share the multiple_choice / true_false / identification shape
        $typeList = implode(' and ', $types);

        return $head . "Task: Create EXACTLY {$count} assessment questions using these types: {$typeList}.\n"
            . "JSON Schema:\n"
            . '{"questions": [{"question_text": "...", "type": "multiple_choice|true_false|identification", "points": 1, '
            . '"correct_answer": "exact answer for identification", '
            . '"options": [{"option_text": "...", "is_correct": true|false}]}]}' . "\n\n"
            . "Rules:\n"
            . "- multiple_choice must have exactly 4 plausible options with exactly 1 marked is_correct: true.\n"
            . "- true_false must have options True and False with exactly 1 marked is_correct: true.\n"
            . "- identification must provide the exact concept or answer in correct_answer or options with is_correct: true.\n"
            . "- Every question must test specific facts, terms, or concepts present in the reference material.\n"
            . "- Array 'questions' must contain exactly {$count} items.\n\n"
            . "Reference Material:\n{$text}";
    }

    private static function viaAi(string $provider, string $activity, string $text, int $count, array $types, string $gameType): array
    {
        $prompt = self::promptFor($activity, $text, $count, $types, $gameType);
        $timeout = max(60, min(120, (int) AiConfigService::getTimeout()));
        $decoded = null;

        if ($provider === 'gemini') {
            $key = (string) AiConfigService::getGeminiApiKey();
            if (empty($key)) {
                return [];
            }
            $model = AiConfigService::getGeminiModel();
            $geminiPayload = [
                'contents' => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                    'maxOutputTokens' => 8192,
                    'temperature' => 0.2,
                ],
            ];
            $maxAttempts = 3;
            $res = null;
            for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                try {
                    $res = Http::timeout($timeout)
                        ->connectTimeout(15)
                        ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$key}", $geminiPayload);

                    if ($res->status() === 404 && $model !== 'gemini-1.5-flash') {
                        $res = Http::timeout($timeout)
                            ->connectTimeout(15)
                            ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$key}", $geminiPayload);
                    }

                    if (! $res->successful() && AiConfigService::isTransientAiError($res->status(), $res->body()) && $attempt < $maxAttempts) {
                        \Illuminate\Support\Facades\Log::warning("QuizGeneratorService Gemini transient error ({$res->status()}). Retrying attempt {$attempt}/{$maxAttempts}...");
                        AiConfigService::retrySleep($attempt);
                        continue;
                    }

                    break;
                } catch (\Illuminate\Http\Client\ConnectionException|\Illuminate\Http\Client\RequestException $e) {
                    if ($attempt < $maxAttempts) {
                        \Illuminate\Support\Facades\Log::warning("QuizGeneratorService Gemini connection error: {$e->getMessage()}. Retrying attempt {$attempt}/{$maxAttempts}...");
                        AiConfigService::retrySleep($attempt);
                        continue;
                    }
                    report($e);
                    return [];
                }
            }

            if (! $res || ! $res->successful()) {
                $status = $res ? $res->status() : 'unknown';
                $body = $res ? $res->body() : 'no response';
                \Illuminate\Support\Facades\Log::warning("QuizGeneratorService Gemini request failed ({$status}): {$body}");
                return [];
            }

            $raw = '';
            foreach ($res->json('candidates.0.content.parts', []) as $part) {
                $raw .= $part['text'] ?? '';
            }
            $raw = preg_replace('/^```(?:json)?\s*/i', '', trim($raw));
            $raw = preg_replace('/\s*```$/i', '', $raw);
            $decoded = json_decode(trim($raw), true);
            if (! is_array($decoded) && preg_match('/\{[\s\S]*\}/', $raw, $m)) {
                $decoded = json_decode($m[0], true);
            }
        } else {
            $isOmni = ($provider === 'omniroute');
            $is9Router = ($provider === '9router' || $provider === 'ninerouter');
            if ($is9Router) {
                $key = (string) (AiConfigService::getNineRouterApiKey() ?? 'sk-9router');
                $model = AiConfigService::getNineRouterModel();
                $baseUrl = AiConfigService::getNineRouterBaseUrl();
            } elseif ($isOmni) {
                $key = (string) AiConfigService::getOmniRouteApiKey();
                $model = AiConfigService::getOmniRouteModel();
                $baseUrl = AiConfigService::getOmniRouteBaseUrl();
            } else {
                $key = (string) AiConfigService::getOpenAiApiKey();
                $model = AiConfigService::getOpenAiModel();
                $baseUrl = AiConfigService::getOpenAiBaseUrl();
            }
            if (empty($key)) {
                return [];
            }
            $endpoint = rtrim($baseUrl, '/') . '/chat/completions';

            $headers = [
                'Authorization' => 'Bearer ' . $key,
                'Accept' => 'application/json',
            ];
            if ($isOmni || $is9Router) {
                $headers['x-api-key'] = $key;
            }

            $payload = [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an expert curriculum assessment creator. You strictly ground every question in the provided reference material and generate the exact requested number of items. Output valid JSON only with zero conversational filler.',
                    ],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'stream' => false,
                'max_tokens' => 8192,
                'temperature' => 0.2,
            ];

            $tryPayload = $payload;
            $tryPayload['response_format'] = ['type' => 'json_object'];

            $maxAttempts = 3;
            $res = null;
            for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                try {
                    $res = Http::timeout($timeout)
                        ->connectTimeout(12)
                        ->withHeaders($headers)
                        ->post($endpoint, $tryPayload);

                    if (! $res->successful() && in_array($res->status(), [400, 422], true)) {
                        $res = Http::timeout($timeout)
                            ->connectTimeout(12)
                            ->withHeaders($headers)
                            ->post($endpoint, $payload);
                    }

                    if (! $res->successful() && AiConfigService::isTransientAiError($res->status(), $res->body()) && $attempt < $maxAttempts) {
                        \Illuminate\Support\Facades\Log::warning("QuizGeneratorService viaAi ({$provider}) transient error ({$res->status()}). Retrying attempt {$attempt}/{$maxAttempts}: " . substr($res->body(), 0, 200));
                        AiConfigService::retrySleep($attempt);
                        continue;
                    }

                    break;
                } catch (\Illuminate\Http\Client\ConnectionException|\Illuminate\Http\Client\RequestException $e) {
                    if ($attempt < $maxAttempts) {
                        \Illuminate\Support\Facades\Log::warning("QuizGeneratorService viaAi ({$provider}) connection error: {$e->getMessage()}. Retrying attempt {$attempt}/{$maxAttempts}...");
                        AiConfigService::retrySleep($attempt);
                        continue;
                    }
                    report($e);
                    return [];
                }
            }

            if (! $res || ! $res->successful()) {
                $status = $res ? $res->status() : 'unknown';
                $body = $res ? $res->body() : 'no response';
                \Illuminate\Support\Facades\Log::warning("QuizGeneratorService viaAi failed ({$status}): {$body}");
                return [];
            }

            $content = (string) $res->json('choices.0.message.content', '');
            $content = preg_replace('/^```(?:json)?\s*/i', '', trim($content));
            $content = preg_replace('/\s*```$/i', '', $content);
            $decoded = json_decode(trim($content), true);
            if (! is_array($decoded) && preg_match('/\{[\s\S]*\}/', $content, $m)) {
                $decoded = json_decode($m[0], true);
            }
        }

        if (!is_array($decoded)) {
            return [];
        }

        return match ($activity) {
            'practical' => $decoded['criteria'] ?? $decoded['items'] ?? $decoded['rubric'] ?? [],
            'game' => $decoded['cards'] ?? $decoded['game'] ?? $decoded['items'] ?? [],
            'seatwork' => $decoded['questions'] ?? $decoded['items'] ?? $decoded['seatwork'] ?? [],
            default => $decoded['questions'] ?? $decoded['items'] ?? $decoded['quiz'] ?? [],
        };
    }

    private static function viaGemini(string $text, int $count, array $types): array
    {
        return self::viaAi('gemini', 'quiz', $text, $count, $types, 'flashcard');
    }

    private static function viaOpenAi(string $text, int $count, array $types): array
    {
        return self::viaAi('openai', 'quiz', $text, $count, $types, 'flashcard');
    }

    /**
     * Normalize any provider output into valid quiz-store questions.
     */
    public static function normalize(array $questions, int $limit = 50): array
    {
        $out = [];

        foreach ($questions as $q) {
            if (!is_array($q)) {
                continue;
            }

            $text = trim((string) ($q['question_text'] ?? ''));
            $rawType = (string) ($q['type'] ?? '');
            $type = in_array($rawType, ['true_false', 'identification'], true) ? $rawType : 'multiple_choice';

            if ($text === '') {
                continue;
            }

            $options = [];

            if ($type === 'identification') {
                $correct = trim((string) ($q['correct_answer'] ?? ''));
                if ($correct === '') {
                    foreach ((array) ($q['options'] ?? []) as $o) {
                        if (!is_array($o)) {
                            continue;
                        }
                        $label = trim((string) ($o['option_text'] ?? ''));
                        if ($label !== '' && !empty($o['is_correct'])) {
                            $correct = $label;
                            break;
                        }
                    }
                }
                if ($correct === '') {
                    $firstOpt = (array) ($q['options'] ?? []);
                    if (!empty($firstOpt[0])) {
                        $correct = trim((string) (is_array($firstOpt[0]) ? ($firstOpt[0]['option_text'] ?? '') : $firstOpt[0]));
                    }
                }
                if ($correct === '') {
                    continue;
                }
                $options = [
                    ['option_text' => mb_substr($correct, 0, 255), 'is_correct' => true],
                ];
            } elseif ($type === 'true_false') {
                $correctTrue = false;
                foreach ((array) ($q['options'] ?? []) as $o) {
                    if (!is_array($o)) {
                        continue;
                    }
                    $label = trim((string) ($o['option_text'] ?? ''));
                    if (strcasecmp($label, 'true') === 0 && !empty($o['is_correct'])) {
                        $correctTrue = true;
                    }
                }
                $options = [
                    ['option_text' => 'True', 'is_correct' => $correctTrue],
                    ['option_text' => 'False', 'is_correct' => !$correctTrue],
                ];
            } else {
                foreach ((array) ($q['options'] ?? []) as $o) {
                    if (!is_array($o)) {
                        continue;
                    }
                    $label = trim((string) ($o['option_text'] ?? ''));
                    if ($label === '') {
                        continue;
                    }
                    $options[] = ['option_text' => mb_substr($label, 0, 255), 'is_correct' => !empty($o['is_correct'])];
                }

                $options = array_values($options);

                if (count($options) < 2) {
                    continue;
                }

                $options = array_slice($options, 0, 4);

                if (!collect($options)->contains(fn ($o) => $o['is_correct'])) {
                    $options[0]['is_correct'] = true;
                }

                $first = true;
                foreach ($options as &$o) {
                    if ($o['is_correct'] && !$first) {
                        $o['is_correct'] = false;
                    }
                    if ($o['is_correct']) {
                        $first = false;
                    }
                }
                unset($o);
            }

            $out[] = [
                'question_text' => $text,
                'type' => $type,
                'points' => max(1, (int) ($q['points'] ?? 1)),
                'options' => $options,
            ];

            if (count($out) >= $limit) {
                break;
            }
        }

        return array_values($out);
    }

    /**
     * Offline generator: no API key needed. Builds identification-style
     * multiple choice (keyword blanks) and true/false statements.
     */
    public static function viaHeuristic(string $text, int $count, array $types): array
    {
        $sentences = self::splitSentences($text);
        $keywords = self::keywords($text);

        if ($sentences === [] || $keywords === []) {
            return [];
        }

        $out = [];
        $seen = [];
        $si = 0;
        $guard = 0;
        $maxAttempts = max(100, $count * 30);

        while (count($out) < $count && $guard < $maxAttempts && !empty($sentences)) {
            $guard++;
            $sentence = $sentences[$si % count($sentences)];
            $si++;

            $type = $types[count($out) % count($types)];

            if ($type === 'identification') {
                $rawId = self::makeIdentification($sentence, $keywords, $guard);
                $q = $rawId !== null ? [
                    'question_text' => $rawId['question_text'],
                    'type' => 'identification',
                    'points' => 1,
                    'options' => [
                        ['option_text' => $rawId['correct_answer'], 'is_correct' => true],
                    ],
                ] : null;
            } elseif ($type === 'true_false') {
                $q = self::makeTf($sentence, $keywords);
            } else {
                $mc = self::makeMc($sentence, $keywords, $guard);
                $q = $mc !== null ? [
                    'question_text' => $mc['question_text'],
                    'type' => 'multiple_choice',
                    'points' => 1,
                    'options' => $mc['options'],
                ] : null;
            }

            if ($q !== null) {
                $key = mb_strtolower(trim($q['question_text']));
                if (!isset($seen[$key])) {
                    $seen[$key] = true;
                    $out[] = $q;
                }
            }
        }

        return array_slice($out, 0, $count);
    }

    private static function splitSentences(string $text): array
    {
        // Split by line breaks, sentence delimiters, and bullet points
        $rawSegments = preg_split('/[\r\n]+|(?<=[.!?])\s+(?=[A-Z0-9"\'])|[;•\-\*]\s+/u', $text);

        $out = [];
        foreach ((array) $rawSegments as $seg) {
            $cleaned = trim(preg_replace('/\s+/u', ' ', (string) $seg));
            if ($cleaned === '') {
                continue;
            }
            // Strip leading bullet marks, numbering, or dashes
            $cleaned = preg_replace('/^(?:[\d]+[\.\)]|[-*•–—])\s*/u', '', $cleaned);
            $words = preg_split('/\s+/u', $cleaned);
            $wordCount = count($words);

            if (mb_strlen($cleaned) >= 15 && $wordCount >= 3 && $wordCount <= 50) {
                $out[] = rtrim($cleaned, ".:;!?") . '.';
            }
        }

        // Fallback: If no segments matched, chunk by words (10 to 18 words per chunk)
        if (empty($out)) {
            $allWords = preg_split('/\s+/u', trim($text));
            $chunks = array_chunk($allWords, 12);
            foreach ($chunks as $chunk) {
                if (count($chunk) >= 3) {
                    $out[] = implode(' ', $chunk) . '.';
                }
            }
        }

        return array_values(array_unique($out));
    }

    private static function keywords(string $text): array
    {
        $stop = ['about', 'after', 'again', 'against', 'being', 'because', 'before', 'between', 'could', 'every', 'first', 'great', 'learn', 'learning', 'lesson', 'means', 'other', 'their', 'there', 'these', 'those', 'through', 'under', 'using', 'while', 'with', 'within', 'study', 'student', 'students', 'teacher', 'school', 'class', 'lesson', 'topic', 'chapter', 'page', 'example', 'using', 'often', 'always', 'never', 'every', 'many', 'much', 'more', 'most', 'such', 'than', 'then', 'them', 'they', 'this', 'that', 'these', 'those', 'when', 'where', 'which', 'while', 'will', 'would', 'your', 'have', 'from', 'were', 'been', 'also', 'overview', 'material', 'content', 'document'];

        preg_match_all('/\b\p{L}[\p{L}\'-]{3,}\b/u', mb_strtolower($text), $m);

        $freq = [];
        foreach ($m[0] as $w) {
            $w = trim($w, "'-");
            if (mb_strlen($w) < 4 || in_array($w, $stop, true)) {
                continue;
            }
            $freq[$w] = ($freq[$w] ?? 0) + 1;
        }

        arsort($freq);

        $keys = array_keys(array_slice($freq, 0, 50, true));
        if (empty($keys)) {
            // Absolute fallback: extract any non-stopwords
            foreach ($m[0] as $w) {
                $w = trim($w, "'-");
                if (mb_strlen($w) >= 3 && !in_array($w, $stop, true)) {
                    $keys[] = $w;
                }
            }
            $keys = array_values(array_unique($keys));
        }

        return $keys;
    }

    private static function makeMc(string $sentence, array $keywords, int $keywordOffset = 0): ?array
    {
        $correct = null;
        $kwCount = count($keywords);
        if ($kwCount === 0) {
            return null;
        }

        foreach ([false, true] as $allowLeading) {
            for ($k = 0; $k < $kwCount; $k++) {
                $kw = $keywords[($k + $keywordOffset) % $kwCount];
                $pos = stripos($sentence, $kw);
                if ($pos === false) {
                    continue;
                }
                if (!$allowLeading && $pos === 0) {
                    continue;
                }
                $correct = $kw;
                break 2;
            }
        }

        if ($correct === null) {
            return null;
        }

        $blanked = preg_replace('/\b' . preg_quote($correct, '/') . '\b/iu', '____', $sentence, 1);

        $distractors = [];
        foreach ($keywords as $kw) {
            if ($kw !== $correct && stripos($sentence, $kw) === false) {
                $distractors[] = ucfirst($kw);
            }
            if (count($distractors) >= 3) {
                break;
            }
        }

        if (count($distractors) < 3) {
            return null;
        }

        $options = [['option_text' => ucfirst($correct), 'is_correct' => true]];
        foreach ($distractors as $d) {
            $options[] = ['option_text' => $d, 'is_correct' => false];
        }
        shuffle($options);

        return [
            'question_text' => 'Complete the statement: ' . $blanked,
            'type' => 'multiple_choice',
            'points' => 1,
            'options' => $options,
        ];
    }

    private static function makeTf(string $sentence, array $keywords): ?array
    {
        $statement = rtrim($sentence, '.');
        $isTrue = (crc32($statement) % 2) === 0;

        if (!$isTrue) {
            $swapped = false;
            foreach ($keywords as $kw) {
                if (stripos($statement, $kw) !== false) {
                    $replacement = null;
                    foreach ($keywords as $other) {
                        if ($other !== $kw && stripos($statement, $other) === false) {
                            $replacement = $other;
                            break;
                        }
                    }
                    if ($replacement === null) {
                        return null;
                    }
                    $statement = preg_replace('/\b' . preg_quote($kw, '/') . '\b/iu', ucfirst($replacement), $statement, 1);
                    $swapped = true;
                    break;
                }
            }
            if (!$swapped) {
                return null;
            }
        }

        return [
            'question_text' => $statement . '.',
            'type' => 'true_false',
            'points' => 1,
            'options' => [
                ['option_text' => 'True', 'is_correct' => $isTrue],
                ['option_text' => 'False', 'is_correct' => !$isTrue],
            ],
        ];
    }

    /**
     * Normalize provider output for the target activity's store endpoint.
     */
    public static function normalizeFor(string $activity, array $items, string $gameType = 'flashcard', int $limit = 50): array
    {
        if ($activity === 'practical') {
            return self::normalizeCriteria($items, $limit);
        }

        if ($activity === 'game') {
            return self::normalizeCards($items, $gameType, $limit);
        }

        if ($activity === 'seatwork') {
            return self::normalizeSeatwork($items, $limit);
        }

        return self::normalize($items, $limit);
    }

    public static function normalizeCriteria(array $items, int $limit = 50): array
    {
        $out = [];

        foreach ($items as $c) {
            if (!is_array($c)) {
                continue;
            }

            $name = trim((string) ($c['criterion_name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $out[] = [
                'criterion_name' => mb_substr($name, 0, 255),
                'description' => mb_substr(trim((string) ($c['description'] ?? '')), 0, 500),
                'max_points' => max(1, min(100, (int) ($c['max_points'] ?? 10))),
            ];

            if (count($out) >= $limit) {
                break;
            }
        }

        return array_values($out);
    }

    public static function normalizeCards(array $items, string $gameType, int $limit = 50): array
    {
        if (!in_array($gameType, self::GAME_TYPES, true)) {
            $gameType = 'flashcard';
        }

        $out = [];

        foreach ($items as $c) {
            if (!is_array($c)) {
                continue;
            }

            $question = trim((string) ($c['question'] ?? ''));
            $answer = trim((string) ($c['answer'] ?? ''));

            if ($question === '' || $answer === '') {
                continue;
            }

            $card = [
                'question' => mb_substr($question, 0, 500),
                'answer' => mb_substr($answer, 0, 500),
            ];

            if ($gameType === 'quiz' || $gameType === 'speedquiz') {
                $opts = [];
                foreach ((array) ($c['options'] ?? []) as $o) {
                    $label = trim((string) (is_array($o) ? ($o['option_text'] ?? $o['text'] ?? '') : $o));
                    if ($label !== '') {
                        $opts[] = $label;
                    }
                }
                $opts = array_values(array_unique($opts));
                if (!in_array($answer, $opts, true)) {
                    $opts[] = $answer;
                }
                while (count($opts) < 2) {
                    $opts[] = 'Option ' . (count($opts) + 1);
                }
                shuffle($opts);
                $card['options'] = array_slice(array_values($opts), 0, 4);
            }

            if ($gameType === 'fourpics') {
                $cleanAnswer = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $answer));
                $card['answer'] = $cleanAnswer !== '' ? $cleanAnswer : 'ANSWER';
                $card['question'] = $card['question'] ?: '4 Pics 1 Word';
                $opts = [];
                foreach ((array) ($c['options'] ?? []) as $o) {
                    $label = trim((string) (is_array($o) ? ($o['option_text'] ?? $o['text'] ?? '') : $o));
                    if ($label !== '') {
                        $opts[] = $label;
                    }
                }
                while (count($opts) < 4) {
                    $opts[] = 'Visual clue ' . (count($opts) + 1);
                }
                $card['options'] = array_slice(array_values($opts), 0, 4);
            }

            if (in_array($gameType, ['crossword', 'wordsearch', 'wordjumble', 'hangman'], true)) {
                $cleanAnswer = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $answer));
                $card['answer'] = $cleanAnswer !== '' ? $cleanAnswer : strtoupper($answer);
            }

            if ($gameType === 'codepuzzle') {
                $card['question'] = $card['question'] ?: 'Code Step';
            }

            if ($gameType === 'truefalse') {
                $card['answer'] = strcasecmp($answer, 'true') === 0 ? 'True' : 'False';
            }

            $out[] = $card;

            if (count($out) >= $limit) {
                break;
            }
        }

        return array_values($out);
    }

    public static function normalizeSeatwork(array $items, int $limit = 50): array
    {
        $allowed = ['identification', 'enumeration', 'true_false', 'multiple_choice', 'matching', 'essay'];
        $out = [];

        foreach ($items as $q) {
            if (!is_array($q)) {
                continue;
            }

            $text = trim((string) ($q['question_text'] ?? ''));
            $type = (string) ($q['type'] ?? 'identification');

            if ($text === '' || !in_array($type, $allowed, true)) {
                continue;
            }

            $row = [
                'question_text' => $text,
                'type' => $type,
                'points' => max(1, (int) ($q['points'] ?? 1)),
                'correct_answer' => '',
                'criteria' => '',
                'enum_items' => [],
                'options' => [],
                'matching_pairs' => [],
            ];

            if ($type === 'identification') {
                $ans = trim((string) ($q['correct_answer'] ?? $q['answer'] ?? $q['expected_answer'] ?? ''));
                if ($ans === '' && !empty($q['options'][0]['option_text'])) {
                    $ans = trim((string) $q['options'][0]['option_text']);
                }
                $row['correct_answer'] = mb_substr($ans, 0, 255);
            } elseif ($type === 'essay') {
                $row['criteria'] = mb_substr(trim((string) ($q['criteria'] ?? $q['rubric'] ?? 'Answer should demonstrate clear understanding of the concept.')), 0, 500);
                $row['points'] = max(1, min(50, (int) ($q['points'] ?? 5)));
            } elseif ($type === 'enumeration') {
                $list = [];
                $rawItems = (array) ($q['enum_items'] ?? $q['items'] ?? $q['answers'] ?? $q['options'] ?? []);
                foreach ($rawItems as $e) {
                    $val = trim((string) (is_array($e) ? ($e['option_text'] ?? $e['text'] ?? '') : $e));
                    if ($val !== '') {
                        $list[] = mb_substr($val, 0, 255);
                    }
                }
                if (count($list) < 2) {
                    continue;
                }
                $row['enum_items'] = array_slice(array_values($list), 0, 6);
            } elseif ($type === 'true_false') {
                $correctTrue = false;
                foreach ((array) ($q['options'] ?? []) as $o) {
                    if (!is_array($o)) {
                        continue;
                    }
                    if (strcasecmp(trim((string) ($o['option_text'] ?? '')), 'true') === 0 && !empty($o['is_correct'])) {
                        $correctTrue = true;
                    }
                }
                $row['options'] = [
                    ['option_text' => 'True', 'is_correct' => $correctTrue],
                    ['option_text' => 'False', 'is_correct' => !$correctTrue],
                ];
            } elseif ($type === 'multiple_choice') {
                $mc = self::normalize([[
                    'question_text' => $text,
                    'type' => 'multiple_choice',
                    'points' => $row['points'],
                    'options' => $q['options'] ?? [],
                ]], 1);
                if ($mc === []) {
                    continue;
                }
                $row['options'] = $mc[0]['options'];
            } elseif ($type === 'matching') {
                $pairs = [];
                foreach ((array) ($q['matching_pairs'] ?? []) as $p) {
                    if (!is_array($p)) {
                        continue;
                    }
                    $left = trim((string) ($p['left_text'] ?? ''));
                    $right = trim((string) ($p['right_text'] ?? ''));
                    if ($left !== '' && $right !== '') {
                        $pairs[] = ['left_text' => mb_substr($left, 0, 255), 'right_text' => mb_substr($right, 0, 255)];
                    }
                }
                if (count($pairs) < 2) {
                    continue;
                }
                $row['matching_pairs'] = array_slice($pairs, 0, 6);
            }

            $out[] = $row;

            if (count($out) >= $limit) {
                break;
            }
        }

        return array_values($out);
    }

    /**
     * Offline generator for every activity type.
     */
    public static function viaHeuristicFor(string $activity, string $text, int $count, array $types, string $gameType): array
    {
        if ($activity === 'practical') {
            return self::heuristicCriteria($text, $count);
        }

        if ($activity === 'game') {
            return self::heuristicCards($text, $count, $gameType);
        }

        if ($activity === 'seatwork') {
            $allowed = array_values(array_intersect($types, ['identification', 'enumeration', 'true_false', 'multiple_choice', 'matching']));
            if ($allowed === []) {
                $allowed = ['identification', 'multiple_choice', 'true_false'];
            }

            return self::heuristicSeatwork($text, $count, $allowed);
        }

        // quiz + exam share MC/TF/identification
        $allowed = array_values(array_intersect($types, ['multiple_choice', 'true_false', 'identification']));
        if ($allowed === []) {
            $allowed = ['multiple_choice', 'true_false'];
        }

        $questions = self::viaHeuristic($text, $count, $allowed);

        if ($activity === 'exam') {
            return [[
                'title' => 'Part 1 - Questions',
                'section_type' => 'questions',
                'instructions' => '',
                'questions' => $questions,
            ]];
        }

        return $questions;
    }

    private static function heuristicSeatwork(string $text, int $count, array $types): array
    {
        $sentences = self::splitSentences($text);
        $keywords = self::keywords($text);

        if ($sentences === [] || $keywords === []) {
            return [];
        }

        $out = [];
        $seen = [];
        $si = 0;
        $guard = 0;
        $maxAttempts = max(100, $count * 30);

        while (count($out) < $count && $guard < $maxAttempts && !empty($sentences)) {
            $guard++;
            $sentence = $sentences[$si % count($sentences)];
            $si++;
            $type = $types[count($out) % count($types)];
            $q = null;

            if ($type === 'multiple_choice') {
                $mc = self::makeMc($sentence, $keywords, $guard);
                if ($mc !== null) {
                    $q = [
                        'question_text' => $mc['question_text'],
                        'type' => 'multiple_choice',
                        'points' => 1,
                        'options' => $mc['options'],
                    ];
                }
            } elseif ($type === 'true_false') {
                $tf = self::makeTf($sentence, $keywords);
                if ($tf !== null) {
                    $q = $tf;
                }
            } elseif ($type === 'identification') {
                $q = self::makeIdentification($sentence, $keywords, $guard);
            } elseif ($type === 'enumeration') {
                $q = self::makeEnumeration($keywords, count($out));
            } elseif ($type === 'matching') {
                $q = self::makeMatching($sentences, $keywords, count($out));
            }

            if ($q !== null) {
                $key = mb_strtolower(trim($q['question_text']));
                if (!isset($seen[$key]) || $type === 'matching' || $type === 'enumeration') {
                    $seen[$key] = true;
                    $out[] = $q;
                }
            }
        }

        return array_slice($out, 0, $count);
    }

    private static function makeIdentification(string $sentence, array $keywords, int $keywordOffset = 0): ?array
    {
        $answer = null;
        $kwCount = count($keywords);
        if ($kwCount === 0) {
            return null;
        }

        foreach ([false, true] as $allowLeading) {
            for ($k = 0; $k < $kwCount; $k++) {
                $kw = $keywords[($k + $keywordOffset) % $kwCount];
                $pos = stripos($sentence, $kw);
                if ($pos === false) {
                    continue;
                }
                if (!$allowLeading && $pos === 0) {
                    continue;
                }
                $answer = $kw;
                break 2;
            }
        }

        if ($answer === null) {
            return null;
        }

        $blanked = preg_replace('/\b' . preg_quote($answer, '/') . '\b/iu', '____', $sentence, 1);

        return [
            'question_text' => 'What term completes the statement: ' . $blanked,
            'type' => 'identification',
            'points' => 1,
            'correct_answer' => ucfirst($answer),
        ];
    }

    private static function makeEnumeration(array $keywords, int $offset = 0): ?array
    {
        $kwCount = count($keywords);
        if ($kwCount < 2) {
            return null;
        }

        $start = ($offset * 2) % $kwCount;
        $items = array_slice($keywords, $start, 4);
        if (count($items) < 2) {
            $items = array_slice($keywords, 0, 4);
        }
        $items = array_map('ucfirst', $items);

        return [
            'question_text' => 'List ' . count($items) . ' key concepts discussed in the material.',
            'type' => 'enumeration',
            'points' => 1,
            'enum_items' => array_values($items),
        ];
    }

    private static function makeMatching(array $sentences, array $keywords, int $offset): ?array
    {
        $pairs = [];
        $used = [];

        foreach ($keywords as $kw) {
            if (in_array($kw, $used, true)) {
                continue;
            }
            foreach ($sentences as $s) {
                if (stripos($s, $kw) === false) {
                    continue;
                }
                $words = preg_split('/\s+/u', trim($s));
                $snippet = implode(' ', array_slice($words, 0, 6));
                if (mb_strlen($snippet) < 10) {
                    continue;
                }
                $pairs[] = ['left_text' => ucfirst($kw), 'right_text' => $snippet . '...'];
                $used[] = $kw;
                break;
            }
            if (count($pairs) >= 4) {
                break;
            }
        }

        $pairs = array_slice($pairs, ($offset * 2) % max(1, count($pairs)), 4);

        if (count($pairs) < 2) {
            return null;
        }

        return [
            'question_text' => 'Match each term to the statement it belongs to.',
            'type' => 'matching',
            'points' => 1,
            'matching_pairs' => array_values($pairs),
        ];
    }

    private static function heuristicCriteria(string $text, int $count): array
    {
        $keywords = self::keywords($text);

        if ($keywords === []) {
            return [];
        }

        $count = max(1, min(25, $count));
        $topics = array_slice($keywords, 0, $count);
        if ($topics === []) {
            $topics = array_slice($keywords, 0, 1);
        }
        $each = (int) floor(100 / count($topics));
        $out = [];

        foreach ($topics as $i => $kw) {
            $points = $i === count($topics) - 1 ? 100 - $each * (count($topics) - 1) : $each;
            $out[] = [
                'criterion_name' => 'Understanding of ' . ucfirst($kw),
                'description' => 'Explain ' . $kw . ' and its practical application based on the material.',
                'max_points' => max(1, $points),
            ];
        }

        return $out;
    }

    private static function heuristicCards(string $text, int $count, string $gameType): array
    {
        if (!in_array($gameType, self::GAME_TYPES, true)) {
            $gameType = 'flashcard';
        }

        $sentences = self::splitSentences($text);
        $keywords = self::keywords($text);

        if ($sentences === [] || $keywords === []) {
            return [];
        }

        $out = [];
        $seen = [];
        $si = 0;
        $guard = 0;
        $maxAttempts = max(100, $count * 30);

        while (count($out) < $count && $guard < $maxAttempts && !empty($sentences)) {
            $guard++;
            $sentence = rtrim($sentences[$si % count($sentences)], '.');
            $si++;

            $answer = null;
            $kwCount = count($keywords);
            for ($k = 0; $k < $kwCount; $k++) {
                $kw = $keywords[($k + $guard) % $kwCount];
                if (stripos($sentence, $kw) !== false) {
                    $answer = ucfirst($kw);
                    break;
                }
            }

            if ($answer === null) {
                continue;
            }

            if ($gameType === 'truefalse') {
                $isTrue = (crc32($sentence) % 2) === 0;
                $card = ['question' => $sentence . '.', 'answer' => $isTrue ? 'True' : 'False'];
                $key = $card['question'];
                if (!isset($seen[$key])) {
                    $seen[$key] = true;
                    $out[] = $card;
                }
                continue;
            }

            if ($gameType === 'fillblank') {
                $blanked = preg_replace('/\b' . preg_quote($answer, '/') . '\b/iu', '____', $sentence, 1);
                if ($blanked === $sentence) {
                    continue;
                }
                $card = ['question' => $blanked . '.', 'answer' => $answer];
                $key = $card['question'];
                if (!isset($seen[$key])) {
                    $seen[$key] = true;
                    $out[] = $card;
                }
                continue;
            }

            $card = ['question' => $sentence . '.', 'answer' => $answer];

            if ($gameType === 'fourpics') {
                $cleanAns = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $answer));
                $picOpts = [];
                foreach ($keywords as $kw) {
                    if ($kw !== $answer) {
                        $picOpts[] = ucfirst($kw);
                    }
                    if (count($picOpts) >= 4) break;
                }
                while (count($picOpts) < 4) {
                    $picOpts[] = 'Clue ' . (count($picOpts) + 1);
                }
                $card = [
                    'question' => 'Concept clue: ' . $sentence . '.',
                    'answer' => $cleanAns !== '' ? $cleanAns : 'CONCEPT',
                    'options' => array_slice($picOpts, 0, 4),
                ];
            } elseif (in_array($gameType, ['crossword', 'wordsearch', 'wordjumble', 'hangman'], true)) {
                $cleanAns = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $answer));
                $card = [
                    'question' => $sentence . '.',
                    'answer' => $cleanAns !== '' ? $cleanAns : strtoupper($answer),
                ];
            } elseif ($gameType === 'codepuzzle') {
                $card = [
                    'question' => 'Step ' . (count($out) + 1) . ': ' . $sentence . '.',
                    'answer' => '// ' . $sentence,
                ];
            } elseif ($gameType === 'quiz' || $gameType === 'speedquiz') {
                $opts = [$answer];
                foreach ($keywords as $kw) {
                    $kw = ucfirst($kw);
                    if ($kw !== $answer && stripos($sentence, $kw) === false) {
                        $opts[] = $kw;
                    }
                    if (count($opts) >= 4) {
                        break;
                    }
                }
                if (count($opts) < 2) {
                    continue;
                }
                shuffle($opts);
                $card['options'] = array_slice($opts, 0, 4);
            }

            $key = $card['question'];
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $out[] = $card;
            }
        }

        return array_slice($out, 0, $count);
    }

    /**
     * Ingest or generate activity items from document text.
     * Auto-detects pre-existing assessments vs general lesson notes,
     * extracts titles, instructions, question options, criteria, and answer keys.
     *
     * @return array{
     *   ok: bool,
     *   provider: string,
     *   detected_mode: string,
     *   title: string,
     *   instructions: string,
     *   time_limit_minutes: int|null,
     *   max_score: int|null,
     *   activity: string,
     *   items: array
     * }
     */
    public static function parseOrGenerateDocument(
        string $activity,
        string $text,
        int $count = 10,
        array $types = [],
        string $mode = 'auto'
    ): array {
        if (!in_array($activity, self::ACTIVITIES, true)) {
            $activity = 'quiz';
        }

        $count = max(1, min(50, $count));
        $text = trim(mb_substr($text, 0, AiConfigService::getMaxSourceChars()));

        if ($text === '') {
            return [
                'ok' => false,
                'provider' => 'none',
                'detected_mode' => 'empty',
                'title' => '',
                'instructions' => '',
                'time_limit_minutes' => null,
                'max_score' => null,
                'activity' => $activity,
                'items' => [],
            ];
        }

        $isExisting = ($mode === 'extract') || ($mode === 'auto' && self::detectIsExistingAssessment($text));
        $detectedMode = $isExisting ? 'extracted_test' : 'generated_from_content';

        if (AiConfigService::isActivityGenerationEnabled()) {
            $providersToTry = AiConfigService::getAvailableAiProviders();
            foreach ($providersToTry as $prov) {
                try {
                    $aiResult = self::viaAiDocumentIngestion($prov, $activity, $text, $count, $types, $isExisting);
                    if (!empty($aiResult['items'])) {
                        return [
                            'ok' => true,
                            'provider' => $prov,
                            'detected_mode' => $aiResult['detected_mode'] ?? $detectedMode,
                            'title' => $aiResult['title'] ?? self::extractHeuristicTitle($text, $activity),
                            'instructions' => $aiResult['instructions'] ?? '',
                            'time_limit_minutes' => $aiResult['time_limit_minutes'] ?? null,
                            'max_score' => $aiResult['max_score'] ?? ($activity === 'practical' ? 100 : null),
                            'activity' => $activity,
                            'items' => $aiResult['items'],
                        ];
                    }
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        }

        $heuristicResult = self::viaHeuristicDocumentIngestion($activity, $text, $count, $types, $isExisting);

        return [
            'ok' => !empty($heuristicResult['items']),
            'provider' => 'heuristic',
            'detected_mode' => $detectedMode,
            'title' => $heuristicResult['title'],
            'instructions' => $heuristicResult['instructions'],
            'time_limit_minutes' => $heuristicResult['time_limit_minutes'],
            'max_score' => $heuristicResult['max_score'],
            'activity' => $activity,
            'items' => $heuristicResult['items'],
        ];
    }

    public static function detectIsExistingAssessment(string $text): bool
    {
        if (preg_match('/answer\s*key/i', $text)) {
            return true;
        }

        if (preg_match_all('/(?:^|\n)\s*\d+[\.\)]\s+[^\n]+/m', $text, $matches) && count($matches[0]) >= 3) {
            if (preg_match('/[A-D][\.\)]\s+/i', $text) || preg_match('/true\s*(?:or|\/)\s*false/i', $text)) {
                return true;
            }
        }

        if (preg_match('/rubric|criteria\s*for\s*grading|scoring\s*guide/i', $text) && preg_match('/\b\d+\s*(?:pts|points|%)/i', $text)) {
            return true;
        }

        return false;
    }

    public static function extractHeuristicTitle(string $text, string $activity): string
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($text));
        $firstFew = array_filter(array_map('trim', array_slice($lines, 0, 8)));

        foreach ($firstFew as $line) {
            if (preg_match('/^(?:instructions|direction|read each|name|date|grade|section|test [I|V|X])/i', $line)) {
                continue;
            }
            if (mb_strlen($line) >= 4 && mb_strlen($line) <= 120 && !preg_match('/^\d+[\.\)]/', $line)) {
                return $line;
            }
        }

        return ucfirst($activity) . ' Assessment - ' . date('M j, Y');
    }

    private static function viaAiDocumentIngestion(
        string $provider,
        string $activity,
        string $text,
        int $count,
        array $types,
        bool $isExisting
    ): array {
        $prompt = self::buildDocumentIngestionPrompt($activity, $text, $count, $types, $isExisting);
        $timeout = max(60, min(120, (int) AiConfigService::getTimeout()));
        $decoded = null;

        if ($provider === 'gemini') {
            $key = (string) AiConfigService::getGeminiApiKey();
            if (empty($key)) {
                return [];
            }
            $model = AiConfigService::getGeminiModel();
            $geminiPayload = [
                'contents' => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                    'maxOutputTokens' => 8192,
                    'temperature' => 0.1,
                ],
            ];
            $maxAttempts = 3;
            $res = null;
            for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                try {
                    $res = \Illuminate\Support\Facades\Http::timeout($timeout)
                        ->connectTimeout(15)
                        ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$key}", $geminiPayload);

                    if ($res->status() === 404 && $model !== 'gemini-1.5-flash') {
                        $res = \Illuminate\Support\Facades\Http::timeout($timeout)
                            ->connectTimeout(15)
                            ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$key}", $geminiPayload);
                    }

                    if (! $res->successful() && AiConfigService::isTransientAiError($res->status(), $res->body()) && $attempt < $maxAttempts) {
                        \Illuminate\Support\Facades\Log::warning("viaAiDocumentIngestion Gemini transient error ({$res->status()}). Retrying attempt {$attempt}/{$maxAttempts}...");
                        AiConfigService::retrySleep($attempt);
                        continue;
                    }

                    break;
                } catch (\Illuminate\Http\Client\ConnectionException|\Illuminate\Http\Client\RequestException $e) {
                    if ($attempt < $maxAttempts) {
                        \Illuminate\Support\Facades\Log::warning("viaAiDocumentIngestion Gemini connection error: {$e->getMessage()}. Retrying attempt {$attempt}/{$maxAttempts}...");
                        AiConfigService::retrySleep($attempt);
                        continue;
                    }
                    report($e);
                    return [];
                }
            }

            if (! $res || ! $res->successful()) {
                $status = $res ? $res->status() : 'unknown';
                $body = $res ? $res->body() : 'no response';
                \Illuminate\Support\Facades\Log::warning("viaAiDocumentIngestion Gemini failed ({$status}): {$body}");
                return [];
            }

            $raw = '';
            foreach ($res->json('candidates.0.content.parts', []) as $part) {
                $raw .= $part['text'] ?? '';
            }
            $raw = preg_replace('/^```(?:json)?\s*/i', '', trim($raw));
            $raw = preg_replace('/\s*```$/i', '', $raw);
            $decoded = json_decode(trim($raw), true);
            if (! is_array($decoded) && preg_match('/\{[\s\S]*\}/', $raw, $m)) {
                $decoded = json_decode($m[0], true);
            }
        } else {
            $isOmni = ($provider === 'omniroute');
            $is9Router = ($provider === '9router' || $provider === 'ninerouter');
            if ($is9Router) {
                $key = (string) (AiConfigService::getNineRouterApiKey() ?? 'sk-9router');
                $model = AiConfigService::getNineRouterModel();
                $baseUrl = AiConfigService::getNineRouterBaseUrl();
            } elseif ($isOmni) {
                $key = (string) AiConfigService::getOmniRouteApiKey();
                $model = AiConfigService::getOmniRouteModel();
                $baseUrl = AiConfigService::getOmniRouteBaseUrl();
            } else {
                $key = (string) AiConfigService::getOpenAiApiKey();
                $model = AiConfigService::getOpenAiModel();
                $baseUrl = AiConfigService::getOpenAiBaseUrl();
            }
            if (empty($key)) {
                return [];
            }
            $endpoint = rtrim($baseUrl, '/') . '/chat/completions';

            $headers = [
                'Authorization' => 'Bearer ' . $key,
                'Accept' => 'application/json',
            ];
            if ($isOmni || $is9Router) {
                $headers['x-api-key'] = $key;
            }

            $payload = [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an expert curriculum ingestion engine. Return only a valid JSON object matching the required schema with zero conversational filler or markdown fences.',
                    ],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'stream' => false,
                'max_tokens' => 8192,
                'temperature' => 0.1,
            ];

            $tryPayload = $payload;
            $tryPayload['response_format'] = ['type' => 'json_object'];

            $maxAttempts = 3;
            $res = null;
            for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                try {
                    $res = \Illuminate\Support\Facades\Http::timeout($timeout)
                        ->connectTimeout(15)
                        ->withHeaders($headers)
                        ->post($endpoint, $tryPayload);

                    if (! $res->successful() && in_array($res->status(), [400, 422], true)) {
                        $res = \Illuminate\Support\Facades\Http::timeout($timeout)
                            ->connectTimeout(15)
                            ->withHeaders($headers)
                            ->post($endpoint, $payload);
                    }

                    if (! $res->successful() && AiConfigService::isTransientAiError($res->status(), $res->body()) && $attempt < $maxAttempts) {
                        \Illuminate\Support\Facades\Log::warning("viaAiDocumentIngestion viaAi ({$provider}) transient error ({$res->status()}). Retrying attempt {$attempt}/{$maxAttempts}: " . substr($res->body(), 0, 200));
                        AiConfigService::retrySleep($attempt);
                        continue;
                    }

                    break;
                } catch (\Illuminate\Http\Client\ConnectionException|\Illuminate\Http\Client\RequestException $e) {
                    if ($attempt < $maxAttempts) {
                        \Illuminate\Support\Facades\Log::warning("viaAiDocumentIngestion viaAi ({$provider}) connection error: {$e->getMessage()}. Retrying attempt {$attempt}/{$maxAttempts}...");
                        AiConfigService::retrySleep($attempt);
                        continue;
                    }
                    report($e);
                    return [];
                }
            }

            if (! $res || ! $res->successful()) {
                $status = $res ? $res->status() : 'unknown';
                $body = $res ? $res->body() : 'no response';
                \Illuminate\Support\Facades\Log::warning("viaAiDocumentIngestion viaAi failed ({$status}): {$body}");
                return [];
            }

            $content = (string) $res->json('choices.0.message.content', '');
            $content = preg_replace('/^```(?:json)?\s*/i', '', trim($content));
            $content = preg_replace('/\s*```$/i', '', $content);
            $decoded = json_decode(trim($content), true);
            if (! is_array($decoded) && preg_match('/\{[\s\S]*\}/', $content, $m)) {
                $decoded = json_decode($m[0], true);
            }
        }

        if (!is_array($decoded)) {
            return [];
        }

        $title = trim((string) ($decoded['title'] ?? ''));
        $instructions = trim((string) ($decoded['instructions'] ?? $decoded['description'] ?? ''));
        $timeLimit = isset($decoded['time_limit_minutes']) && is_numeric($decoded['time_limit_minutes'])
            ? (int) $decoded['time_limit_minutes']
            : null;
        $maxScore = isset($decoded['max_score']) && is_numeric($decoded['max_score'])
            ? (int) $decoded['max_score']
            : null;

        $rawItems = match ($activity) {
            'practical' => $decoded['criteria'] ?? $decoded['items'] ?? $decoded['rubric'] ?? [],
            'seatwork' => $decoded['questions'] ?? $decoded['items'] ?? $decoded['seatwork'] ?? [],
            default => $decoded['questions'] ?? $decoded['items'] ?? $decoded['quiz'] ?? [],
        };

        $items = self::normalizeFor($activity, is_array($rawItems) ? $rawItems : [], 'flashcard', 50);

        return [
            'title' => $title ?: self::extractHeuristicTitle($text, $activity),
            'instructions' => $instructions,
            'time_limit_minutes' => $timeLimit,
            'max_score' => $maxScore,
            'detected_mode' => $decoded['detected_mode'] ?? ($isExisting ? 'extracted_test' : 'generated_from_content'),
            'items' => $items,
        ];
    }

    private static function buildDocumentIngestionPrompt(
        string $activity,
        string $text,
        int $count,
        array $types,
        bool $isExisting
    ): string {
        $task = $isExisting
            ? "TASK: The document below is an EXISTING assessment or quiz sheet. FAITHFULLY EXTRACT all questions, choices, answers, points, and instructions exactly as written. If an Answer Key or correct markings are provided anywhere in the text, you MUST MATCH EVERY QUESTION TO ITS EXACT CORRECT ANSWER."
            : "TASK: The document below is learning material or notes. GENERATE EXACTLY {$count} high-quality {$activity} items strictly grounded in the document text.";

        $head = "You are an expert assessment parser and curriculum designer.\n\n"
            . "CRITICAL INSTRUCTIONS:\n"
            . "1. {$task}\n"
            . "2. ACCURACY: The correct answer must be 100% accurate according to the provided material and answer key.\n"
            . "3. STRIP NUMBERING: In question_text, omit leading numbers like '1.', '2.'. In option_text, omit leading letters like 'A.', 'B.', 'C.'.\n"
            . "4. CLEAN OUTPUT: Return ONLY a valid JSON object matching the exact schema specified below. Zero commentary, zero markdown code fences.\n\n";

        if ($activity === 'practical') {
            return $head . "JSON Schema:\n"
                . '{"title": "Title of Practical Assessment", "instructions": "Comprehensive instructions for what student must perform or submit", '
                . '"time_limit_minutes": 60, "max_score": 100, "detected_mode": "' . ($isExisting ? 'extracted_test' : 'generated_from_content') . '", '
                . '"criteria": [{"criterion_name": "Short specific skill name", "description": "What student demonstrates to earn points", "max_points": 25}]}' . "\n\n"
                . "Document Content:\n{$text}";
        }

        if ($activity === 'seatwork') {
            $typeList = !empty($types) ? implode(', ', $types) : 'identification, enumeration, true_false, multiple_choice, matching, essay';
            return $head . "Target Question Types: {$typeList}.\n"
                . "JSON Schema:\n"
                . '{"title": "Title of Seatwork", "instructions": "Clear directions for students", '
                . '"time_limit_minutes": null, "detected_mode": "' . ($isExisting ? 'extracted_test' : 'generated_from_content') . '", '
                . '"questions": [{"question_text": "...", "type": "identification|enumeration|true_false|multiple_choice|matching|essay", "points": 1, '
                . '"correct_answer": "exact answer for identification", "enum_items": ["item1", "item2"], "criteria": "rubric for essay", '
                . '"options": [{"option_text": "...", "is_correct": true|false}], '
                . '"matching_pairs": [{"left_text": "...", "right_text": "..."}]}]}' . "\n\n"
                . "Document Content:\n{$text}";
        }

        $typeList = !empty($types) ? implode(', ', $types) : 'multiple_choice, true_false, identification';
        return $head . "Target Question Types: {$typeList}.\n"
            . "JSON Schema:\n"
            . '{"title": "Title of Quiz", "instructions": "Instructions for students", '
            . '"time_limit_minutes": null, "detected_mode": "' . ($isExisting ? 'extracted_test' : 'generated_from_content') . '", '
            . '"questions": [{"question_text": "...", "type": "multiple_choice|true_false|identification", "points": 1, '
            . '"correct_answer": "exact answer for identification", '
            . '"options": [{"option_text": "...", "is_correct": true|false}]}]}' . "\n\n"
            . "Document Content:\n{$text}";
    }

    private static function viaHeuristicDocumentIngestion(
        string $activity,
        string $text,
        int $count,
        array $types,
        bool $isExisting
    ): array {
        $title = self::extractHeuristicTitle($text, $activity);

        $instructions = '';
        if (preg_match('/(?:instructions|directions|guidelines)[:\s]+([^\n]+(?:\n[^\n]+){0,4})/i', $text, $instMatch)) {
            $instructions = trim($instMatch[1]);
        }

        if ($isExisting) {
            $extracted = self::extractTestHeuristically($text, $activity);
            if (!empty($extracted['items'])) {
                return [
                    'title' => $extracted['title'] ?: $title,
                    'instructions' => $extracted['instructions'] ?: $instructions,
                    'time_limit_minutes' => $extracted['time_limit_minutes'] ?? null,
                    'max_score' => $activity === 'practical' ? 100 : null,
                    'items' => $extracted['items'],
                ];
            }
        }

        $items = self::viaHeuristicFor($activity, $text, $count, $types, 'flashcard');

        return [
            'title' => $title,
            'instructions' => $instructions,
            'time_limit_minutes' => null,
            'max_score' => $activity === 'practical' ? 100 : null,
            'items' => $items,
        ];
    }

    public static function extractTestHeuristically(string $text, string $activity): array
    {
        $title = self::extractHeuristicTitle($text, $activity);
        $instructions = '';

        if (preg_match('/(?:instructions|directions)[:\s]+(.*?)(?=\n\s*(?:1[\.\)]|Part|Test\s+I)|$)/is', $text, $im)) {
            $instructions = trim(preg_replace('/\s+/', ' ', $im[1]));
        }

        // 1. Extract Answer Key if present
        $answerMap = [];
        if (preg_match('/Answer\s*Key[:\s]+([\s\S]+)$/i', $text, $akMatch)) {
            $akText = $akMatch[1];
            if (preg_match_all('/(\d{1,3})[\.\:\s\t\-]+([A-D]|True|False)\b/i', $akText, $akPairs, PREG_SET_ORDER)) {
                foreach ($akPairs as $pair) {
                    $num = (int) $pair[1];
                    $ans = strtoupper(trim($pair[2]));
                    $answerMap[$num] = $ans;
                }
            }
        }

        if ($activity === 'practical') {
            $criteria = [];
            if (preg_match_all('/(?:^|\n)\s*(?:\d+[\.\)]|\-|\*)\s*([^\n\(\:\-]+)[\:\-\(]\s*(?:(\d+)\s*(?:pts|points|%))?\s*([^\n]*)/i', $text, $critMatches, PREG_SET_ORDER)) {
                foreach ($critMatches as $cm) {
                    $cName = trim($cm[1]);
                    $pts = !empty($cm[2]) ? (int) $cm[2] : 20;
                    $desc = trim($cm[3]);
                    if (mb_strlen($cName) >= 3 && !preg_match('/^(?:note|instruction|step|total)/i', $cName)) {
                        $criteria[] = [
                            'criterion_name' => mb_substr($cName, 0, 100),
                            'description' => mb_substr($desc ?: "Demonstrate mastery of {$cName}", 0, 300),
                            'max_points' => max(1, min(100, $pts)),
                        ];
                    }
                }
            }

            return [
                'title' => $title,
                'instructions' => $instructions,
                'time_limit_minutes' => 60,
                'items' => $criteria,
            ];
        }

        // 2. Extract numbered questions
        $questions = [];
        // Match numbered question chunks: "1. Question ... A. ... B. ..."
        $cleanText = preg_replace('/Answer\s*Key[:\s]+[\s\S]+$/i', '', $text);
        $parts = preg_split('/(?:\r\n|\r|\n)\s*(?=\d{1,3}[\.\)]\s+)/', $cleanText);

        foreach ($parts as $part) {
            $part = trim($part);
            if (!preg_match('/^(\d{1,3})[\.\)]\s+([\s\S]+)$/', $part, $pm)) {
                continue;
            }

            $qNum = (int) $pm[1];
            $body = trim($pm[2]);

            // Check if options A, B, C, D exist
            if (preg_match_all('/(?:\b|^)([A-D])[\.\)]\s+([\s\S]*?)(?=(?:\s+[A-D][\.\)]|$))/i', $body, $optMatches, PREG_SET_ORDER)) {
                // Question text is before the first option
                $firstOptPos = false;
                foreach ($optMatches as $om) {
                    $pos = strpos($body, $om[0]);
                    if ($pos !== false && ($firstOptPos === false || $pos < $firstOptPos)) {
                        $firstOptPos = $pos;
                    }
                }

                $qText = $firstOptPos !== false ? trim(substr($body, 0, $firstOptPos)) : $body;
                $qText = trim(preg_replace('/\s+/', ' ', $qText));

                $targetAnswerLetter = $answerMap[$qNum] ?? null;
                $options = [];

                foreach ($optMatches as $om) {
                    $letter = strtoupper(trim($om[1]));
                    $optText = trim(preg_replace('/\s+/', ' ', $om[2]));
                    if ($optText === '') {
                        continue;
                    }

                    $isCorrect = false;
                    if ($targetAnswerLetter !== null) {
                        $isCorrect = ($letter === $targetAnswerLetter);
                    } elseif (preg_match('/(?:\*|\(correct\)|\bcorrect\b)/i', $optText)) {
                        $isCorrect = true;
                        $optText = trim(preg_replace('/(?:\*|\(correct\)|\bcorrect\b)/i', '', $optText));
                    }

                    $options[] = [
                        'option_text' => $optText,
                        'is_correct' => $isCorrect,
                        '_letter' => $letter,
                    ];
                }

                // If no option marked correct, mark the first one as default
                if (!collect($options)->contains(fn ($o) => $o['is_correct']) && count($options) > 0) {
                    $options[0]['is_correct'] = true;
                }

                $cleanOptions = array_map(function ($o) {
                    return [
                        'option_text' => $o['option_text'],
                        'is_correct' => (bool) $o['is_correct'],
                    ];
                }, $options);

                if (count($cleanOptions) >= 2) {
                    $questions[] = [
                        'question_text' => $qText,
                        'type' => count($cleanOptions) === 2 && strcasecmp($cleanOptions[0]['option_text'], 'true') === 0 ? 'true_false' : 'multiple_choice',
                        'points' => 1,
                        'options' => array_slice($cleanOptions, 0, 4),
                    ];
                }
            } elseif ($activity === 'seatwork') {
                // Freeform question for seatwork
                $lines = explode("\n", $body);
                $qText = trim($lines[0]);
                $targetAnswer = $answerMap[$qNum] ?? null;

                $questions[] = [
                    'question_text' => $qText,
                    'type' => 'identification',
                    'points' => 1,
                    'correct_answer' => $targetAnswer ?: '',
                    'options' => [],
                ];
            }
        }

        return [
            'title' => $title,
            'instructions' => $instructions,
            'time_limit_minutes' => null,
            'items' => $questions,
        ];
    }
}
