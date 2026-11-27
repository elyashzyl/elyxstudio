<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EssayGraderService
{
    /**
     * Grade a student's essay answer using AI against teacher criteria.
     *
     * @return array{score: int, feedback: string, is_correct: bool}
     */
    public static function grade(string $questionText, ?string $criteria, int $maxPoints, ?string $studentAnswer): array
    {
        $answer = trim((string) $studentAnswer);

        if ($answer === '') {
            return [
                'score' => 0,
                'feedback' => 'No essay was submitted.',
                'is_correct' => false,
            ];
        }

        $criteriaText = trim((string) $criteria);
        if (AiConfigService::isEssayGradingEnabled()) {
            $providersToTry = AiConfigService::getAvailableAiProviders();
            foreach ($providersToTry as $provider) {
                try {
                    $result = self::gradeViaAi($provider, $questionText, $criteriaText, $maxPoints, $answer);
                    if ($result !== null) {
                        return $result;
                    }
                } catch (\Throwable $e) {
                    Log::warning("AI essay grading with {$provider} failed: " . $e->getMessage());
                }
            }
        }

        return self::gradeViaHeuristic($questionText, $criteriaText, $maxPoints, $answer);
    }

    private static function gradeViaAi(string $provider, string $questionText, string $criteria, int $maxPoints, string $answer): ?array
    {
        $skillPrompt = AiConfigService::getSkillPrompt();
        $gradingGuide = AiConfigService::getGradingInstructions();

        $prompt = "{$skillPrompt}\nGrading Guidelines: {$gradingGuide}\n\n"
            . "Question / Prompt:\n{$questionText}\n\n"
            . "Maximum Points: {$maxPoints}\n\n"
            . "Grading Criteria (set by the teacher):\n" . ($criteria !== '' ? $criteria : "Coherence, relevance to the prompt, and depth of explanation.") . "\n\n"
            . "Student Submission:\n{$answer}\n\n"
            . "Evaluate the student's submission strictly based on the grading criteria. Drop pleasantries and fluff.\n"
            . "Respond with ONLY valid JSON in this exact structure, with no markdown code blocks or other text:\n"
            . '{"score": 0, "feedback": "Concise, criteria-anchored feedback stating exactly why points were awarded or deducted."}' . "\n\n"
            . "Rules:\n"
            . "- score must be an integer between 0 and {$maxPoints}.\n"
            . "- If the answer is completely off-topic, gibberish, or irrelevant, score 0.\n"
            . "- feedback must be concise, objective, and specific to the criteria.";

        $timeout = AiConfigService::getTimeout();

        if ($provider === 'gemini') {
            $key = (string) AiConfigService::getGeminiApiKey();
            if (empty($key)) {
                return null;
            }
            $model = AiConfigService::getGeminiModel();
            $geminiPayload = [
                'contents' => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => ['responseMimeType' => 'application/json', 'maxOutputTokens' => 1024],
            ];

            $maxAttempts = 3;
            $res = null;
            for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                try {
                    $res = Http::timeout($timeout)
                        ->connectTimeout(12)
                        ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$key}", $geminiPayload);

                    if ($res->status() === 404 && $model !== 'gemini-1.5-flash') {
                        $res = Http::timeout($timeout)
                            ->connectTimeout(12)
                            ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$key}", $geminiPayload);
                    }

                    if (! $res->successful() && AiConfigService::isTransientAiError($res->status(), $res->body()) && $attempt < $maxAttempts) {
                        Log::warning("EssayGraderService Gemini transient error ({$res->status()}). Retrying attempt {$attempt}/{$maxAttempts}...");
                        AiConfigService::retrySleep($attempt);
                        continue;
                    }

                    break;
                } catch (\Illuminate\Http\Client\ConnectionException|\Illuminate\Http\Client\RequestException $e) {
                    if ($attempt < $maxAttempts) {
                        Log::warning("EssayGraderService Gemini connection error: {$e->getMessage()}. Retrying attempt {$attempt}/{$maxAttempts}...");
                        AiConfigService::retrySleep($attempt);
                        continue;
                    }
                    return null;
                }
            }

            if (! $res || ! $res->successful()) {
                return null;
            }

            $raw = '';
            foreach ($res->json('candidates.0.content.parts', []) as $part) {
                $raw .= $part['text'] ?? '';
            }
            $decoded = json_decode($raw, true);
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
                return null;
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
                    ['role' => 'system', 'content' => 'You are an objective academic essay grader adhering strictly to OmniRoute style: concise, direct, zero pleasantries, criteria-anchored evaluation. Always return valid JSON only.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'stream' => false,
                'max_tokens' => 1500,
            ];

            $tryPayload = $payload;
            $tryPayload['response_format'] = ['type' => 'json_object'];

            $maxAttempts = 3;
            $res = null;
            for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                try {
                    $res = Http::timeout($timeout)
                        ->connectTimeout(15)
                        ->withHeaders($headers)
                        ->post($endpoint, $tryPayload);

                    if (! $res->successful() && $res->status() === 400) {
                        $res = Http::timeout($timeout)
                            ->connectTimeout(15)
                            ->withHeaders($headers)
                            ->post($endpoint, $payload);
                    }

                    if (! $res->successful() && AiConfigService::isTransientAiError($res->status(), $res->body()) && $attempt < $maxAttempts) {
                        Log::warning("EssayGraderService ({$provider}) transient error ({$res->status()}). Retrying attempt {$attempt}/{$maxAttempts}: " . substr($res->body(), 0, 200));
                        AiConfigService::retrySleep($attempt);
                        continue;
                    }

                    break;
                } catch (\Illuminate\Http\Client\ConnectionException|\Illuminate\Http\Client\RequestException $e) {
                    if ($attempt < $maxAttempts) {
                        Log::warning("EssayGraderService ({$provider}) connection error: {$e->getMessage()}. Retrying attempt {$attempt}/{$maxAttempts}...");
                        AiConfigService::retrySleep($attempt);
                        continue;
                    }
                    return null;
                }
            }

            if (! $res || ! $res->successful()) {
                return null;
            }

            $content = (string) $res->json('choices.0.message.content', '');
            $content = preg_replace('/^```(?:json)?\s*/i', '', trim($content));
            $content = preg_replace('/\s*```$/i', '', $content);
            $decoded = json_decode(trim($content), true);
        }

        if (!is_array($decoded) || !isset($decoded['score'])) {
            return null;
        }

        $score = max(0, min($maxPoints, (int) round($decoded['score'])));
        $feedback = trim((string) ($decoded['feedback'] ?? ''));

        return [
            'score' => $score,
            'feedback' => $feedback ?: "AI Evaluation: Graded according to criteria ({$score}/{$maxPoints} pts).",
            'is_correct' => $score >= ceil($maxPoints / 2),
        ];
    }

    private static function gradeViaHeuristic(string $questionText, string $criteria, int $maxPoints, string $answer): array
    {
        $words = str_word_count(strip_tags($answer));

        if ($words < 5) {
            return [
                'score' => 0,
                'feedback' => 'The submission is too short to evaluate against the grading criteria.',
                'is_correct' => false,
            ];
        }

        $refText = strtolower($criteria . ' ' . $questionText);
        preg_match_all('/\b[a-z]{4,}\b/i', $refText, $matches);
        $stopWords = ['this', 'that', 'with', 'from', 'have', 'were', 'what', 'when', 'where', 'which', 'their', 'there', 'about', 'would', 'could', 'should', 'point', 'points', 'criteria', 'grade', 'question', 'essay'];
        $keywords = array_unique(array_diff($matches[0] ?? [], $stopWords));

        $answerLower = strtolower($answer);
        $matchedKeywords = 0;
        foreach ($keywords as $kw) {
            if (str_contains($answerLower, $kw)) {
                $matchedKeywords++;
            }
        }

        $kwRatio = count($keywords) > 0 ? ($matchedKeywords / count($keywords)) : 0.5;
        $lengthFactor = min(1.0, $words / 80);
        $quality = (0.6 * min(1.0, $kwRatio * 1.5)) + (0.4 * $lengthFactor);
        $score = (int) round($maxPoints * max(0.2, min(1.0, $quality)));

        $feedback = "AI Criterion Assessment: Submission contains {$words} words. ";
        if ($score >= ceil($maxPoints * 0.8)) {
            $feedback .= "Strong essay addressing the key aspects of the designated criteria comprehensively.";
        } elseif ($score >= ceil($maxPoints * 0.5)) {
            $feedback .= "Adequate response meeting several criteria; expanding further with more specific details would improve depth.";
        } else {
            $feedback .= "Needs further development. Consider reviewing the criteria and elaborating more thoroughly.";
        }

        return [
            'score' => $score,
            'feedback' => $feedback,
            'is_correct' => $score >= ceil($maxPoints / 2),
        ];
    }
}
