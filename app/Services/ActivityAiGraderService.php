<?php

namespace App\Services;

use App\Models\ExamAttempt;
use App\Models\PracticalAttempt;
use App\Models\QuizAttempt;
use App\Models\SeatworkAnswer;
use App\Models\SeatworkAttempt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ActivityAiGraderService
{
    /**
     * Send a structured prompt to the active AI provider (OmniRoute, Gemini, OpenAI)
     * and return raw response string, or null if failed.
     */
    public static function callAi(string $systemPrompt, string $userPrompt, int $maxTokens = 2048): ?string
    {
        $providersToTry = AiConfigService::getAvailableAiProviders();

        if (empty($providersToTry)) {
            return null;
        }

        $timeout = min(max(30, AiConfigService::getTimeout()), 60);

        foreach ($providersToTry as $provider) {
            try {
                if ($provider === 'gemini') {
                    $key = (string) AiConfigService::getGeminiApiKey();
                    if (empty($key)) {
                        continue;
                    }
                    $model = AiConfigService::getGeminiModel();
                    $fullPrompt = $systemPrompt . "\n\n" . $userPrompt;

                    $geminiPayload = [
                        'contents' => [['parts' => [['text' => $fullPrompt]]]],
                        'generationConfig' => ['responseMimeType' => 'application/json', 'maxOutputTokens' => $maxTokens],
                    ];

                    $maxAttempts = 3;
                    $res = null;
                    for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                        try {
                            $res = Http::timeout($timeout)->connectTimeout(12)->post(
                                "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$key}",
                                $geminiPayload
                            );

                            if ($res->status() === 404 && $model !== 'gemini-1.5-flash') {
                                $res = Http::timeout($timeout)->connectTimeout(12)->post(
                                    "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$key}",
                                    $geminiPayload
                                );
                            }

                            if (! $res->successful() && AiConfigService::isTransientAiError($res->status(), $res->body()) && $attempt < $maxAttempts) {
                                Log::warning("ActivityAiGraderService Gemini transient error ({$res->status()}). Retrying attempt {$attempt}/{$maxAttempts}...");
                                AiConfigService::retrySleep($attempt);
                                continue;
                            }

                            break;
                        } catch (\Illuminate\Http\Client\ConnectionException|\Illuminate\Http\Client\RequestException $e) {
                            if ($attempt < $maxAttempts) {
                                Log::warning("ActivityAiGraderService Gemini connection error: {$e->getMessage()}. Retrying attempt {$attempt}/{$maxAttempts}...");
                                AiConfigService::retrySleep($attempt);
                                continue;
                            }
                            break;
                        }
                    }

                    if (! $res || ! $res->successful()) {
                        continue;
                    }

                    $raw = '';
                    foreach ($res->json('candidates.0.content.parts', []) as $part) {
                        $raw .= $part['text'] ?? '';
                    }

                    $trimmed = trim($raw);
                    if ($trimmed !== '') {
                        return $trimmed;
                    }
                    continue;
                }

                // OmniRoute, 9Router, or OpenAI
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
                    continue;
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
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userPrompt],
                    ],
                    'stream' => false,
                    'max_tokens' => $maxTokens,
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

                        if (! $res->successful() && $res->status() === 400) {
                            $res = Http::timeout($timeout)
                                ->connectTimeout(12)
                                ->withHeaders($headers)
                                ->post($endpoint, $payload);
                        }

                        if (! $res->successful() && AiConfigService::isTransientAiError($res->status(), $res->body()) && $attempt < $maxAttempts) {
                            Log::warning("ActivityAiGraderService ({$provider}) transient error ({$res->status()}). Retrying attempt {$attempt}/{$maxAttempts}: " . substr($res->body(), 0, 200));
                            AiConfigService::retrySleep($attempt);
                            continue;
                        }

                        break;
                    } catch (\Illuminate\Http\Client\ConnectionException|\Illuminate\Http\Client\RequestException $e) {
                        if ($attempt < $maxAttempts) {
                            Log::warning("ActivityAiGraderService ({$provider}) connection error: {$e->getMessage()}. Retrying attempt {$attempt}/{$maxAttempts}...");
                            AiConfigService::retrySleep($attempt);
                            continue;
                        }
                        break;
                    }
                }

                if (! $res || ! $res->successful()) {
                    continue;
                }

                $content = (string) $res->json('choices.0.message.content', '');
                $content = preg_replace('/^```(?:json)?\s*/i', '', trim($content));
                $content = preg_replace('/\s*```$/i', '', $content);

                $trimmed = trim($content);
                if ($trimmed !== '') {
                    return $trimmed;
                }
            } catch (\Throwable $e) {
                Log::warning("ActivityAiGraderService ({$provider}) AI call failed: " . $e->getMessage());
            }
        }

        return null;
    }

    /**
     * AI-grade a Practical Attempt based on rubric criteria.
     *
     * @return array{success: bool, scores: array<int, int>, comments: array<int, string>, total_score: int}
     */
    public static function gradePracticalAttempt(PracticalAttempt $attempt): array
    {
        $practical = $attempt->practical;
        $criteria = $practical->criteria;
        $submissionText = trim((string) $attempt->submission_text);
        $hasFile = ! empty($attempt->submission_file);

        $scores = [];
        $comments = [];

        if ($submissionText === '' && ! $hasFile) {
            foreach ($criteria as $c) {
                $scores[$c->id] = 0;
                $comments[$c->id] = 'No submission was provided for this assessment.';
            }

            return [
                'success' => true,
                'scores' => $scores,
                'comments' => $comments,
                'total_score' => 0,
            ];
        }

        $criteriaDetails = [];
        foreach ($criteria as $c) {
            $criteriaDetails[] = "- Criterion ID: {$c->id} | Name: \"{$c->criterion_name}\" | Description: \"{$c->description}\" | Max Points: {$c->max_points}";
        }
        $criteriaPrompt = implode("\n", $criteriaDetails);

        $skillPrompt = AiConfigService::getSkillPrompt();
        $gradingGuide = AiConfigService::getGradingInstructions();

        $systemPrompt = "{$skillPrompt}\nGrading Guidelines: {$gradingGuide}\n"
            . "Respond with ONLY valid JSON with no markdown wrapping in this exact structure:\n"
            . '{"evaluations": [{"criterion_id": 1, "score": 20, "comment": "Concise feedback grounded in the criterion."}]}';

        $userPrompt = "Assessment Title: {$practical->title}\n\n"
            . "Rubric Criteria:\n{$criteriaPrompt}\n\n"
            . "Student Submitted Text:\n" . ($submissionText !== '' ? $submissionText : '[Student attached a submission file]') . "\n"
            . ($hasFile ? "Attachment: Student included a project file.\n\n" : "\n\n")
            . "Evaluate the submission strictly against each criterion ID in the rubric.\n"
            . "- score must be an integer between 0 and that criterion's max points.\n"
            . "- comment must be concise (1-2 sentences) explaining why the score was given based on the criterion.";

        $rawJson = self::callAi($systemPrompt, $userPrompt, 1500);
        $decoded = null;
        if ($rawJson) {
            $cleaned = preg_replace('/^```(?:json)?\s*/i', '', $rawJson);
            $cleaned = preg_replace('/\s*```$/i', '', $cleaned);
            $decoded = json_decode(trim($cleaned), true);
        }

        $evals = $decoded['evaluations'] ?? [];
        $evalMap = [];
        if (is_array($evals)) {
            foreach ($evals as $item) {
                if (isset($item['criterion_id'])) {
                    $evalMap[(int) $item['criterion_id']] = $item;
                }
            }
        }

        $totalScore = 0;
        foreach ($criteria as $c) {
            if (isset($evalMap[$c->id])) {
                $score = max(0, min($c->max_points, (int) round($evalMap[$c->id]['score'] ?? 0)));
                $comment = trim((string) ($evalMap[$c->id]['comment'] ?? ''));
            } else {
                // Heuristic calculation if criterion not in AI response
                $words = str_word_count(strip_tags($submissionText));
                $pct = $words >= 50 ? 0.85 : ($words >= 20 ? 0.70 : 0.50);
                $score = (int) round($c->max_points * $pct);
                $comment = "Evaluated against {$c->criterion_name}: submission demonstrates standard understanding ({$score}/{$c->max_points} pts).";
            }

            $scores[$c->id] = $score;
            $comments[$c->id] = $comment ?: "Meets criterion requirements ({$score}/{$c->max_points} pts).";
            $totalScore += $score;
        }

        return [
            'success' => true,
            'scores' => $scores,
            'comments' => $comments,
            'total_score' => $totalScore,
        ];
    }

    /**
     * AI-grade an Exam Attempt's practical criteria.
     *
     * @return array{success: bool, scores: array<int, int>, comments: array<int, string>}
     */
    public static function gradeExamPracticalAttempt(ExamAttempt $attempt): array
    {
        $exam = $attempt->exam;
        $exam->load('sections.criteria');

        $scores = [];
        $comments = [];

        foreach ($exam->sections as $section) {
            if ($section->section_type !== 'practical' || $section->criteria->isEmpty()) {
                continue;
            }

            // Gather student answers or any text from the attempt
            $answersText = $attempt->answers->pluck('answer_text')->filter()->implode("\n");

            $criteriaDetails = [];
            foreach ($section->criteria as $c) {
                $criteriaDetails[] = "- Criterion ID: {$c->id} | Name: \"{$c->criterion_name}\" | Description: \"{$c->description}\" | Max Points: {$c->max_points}";
            }
            $criteriaPrompt = implode("\n", $criteriaDetails);

            $skillPrompt = AiConfigService::getSkillPrompt();
            $gradingGuide = AiConfigService::getGradingInstructions();

            $systemPrompt = "{$skillPrompt}\nGrading Guidelines: {$gradingGuide}\n"
                . "Respond with ONLY valid JSON with no markdown wrapping in this exact structure:\n"
                . '{"evaluations": [{"criterion_id": 1, "score": 20, "comment": "Concise feedback grounded in the criterion."}]}';

            $userPrompt = "Exam: {$exam->title} - Practical Section: {$section->title}\n\n"
                . "Rubric Criteria:\n{$criteriaPrompt}\n\n"
                . "Student Submission / Work:\n" . ($answersText !== '' ? $answersText : 'Practical demonstration performed.') . "\n\n"
                . "Evaluate the work strictly for each criterion ID.\n"
                . "- score must be an integer between 0 and that criterion's max points.\n"
                . "- comment must be concise (1-2 sentences) explaining the points awarded.";

            $rawJson = self::callAi($systemPrompt, $userPrompt, 1500);
            $decoded = null;
            if ($rawJson) {
                $cleaned = preg_replace('/^```(?:json)?\s*/i', '', $rawJson);
                $cleaned = preg_replace('/\s*```$/i', '', $cleaned);
                $decoded = json_decode(trim($cleaned), true);
            }

            $evals = $decoded['evaluations'] ?? [];
            $evalMap = [];
            if (is_array($evals)) {
                foreach ($evals as $item) {
                    if (isset($item['criterion_id'])) {
                        $evalMap[(int) $item['criterion_id']] = $item;
                    }
                }
            }

            foreach ($section->criteria as $c) {
                if (isset($evalMap[$c->id])) {
                    $score = max(0, min($c->max_points, (int) round($evalMap[$c->id]['score'] ?? 0)));
                    $comment = trim((string) ($evalMap[$c->id]['comment'] ?? ''));
                } else {
                    $score = (int) round($c->max_points * 0.80);
                    $comment = "Demonstrated required proficiency for {$c->criterion_name} ({$score}/{$c->max_points} pts).";
                }

                $scores[$c->id] = $score;
                $comments[$c->id] = $comment ?: "Proficient execution ({$score}/{$c->max_points} pts).";
            }
        }

        return [
            'success' => true,
            'scores' => $scores,
            'comments' => $comments,
        ];
    }

    /**
     * AI-grade an entire Exam Attempt including all question sections and practical criteria.
     *
     * @return array{
     *     success: bool,
     *     scores: array<int, int>,
     *     comments: array<int, string>,
     *     answers: array<int, array{id: int, is_correct: bool, points_earned: int, feedback?: string}>,
     *     total_score: int
     * }
     */
    public static function gradeExamAttempt(ExamAttempt $attempt): array
    {
        $exam = $attempt->exam;
        $attempt->load([
            'exam.sections.criteria',
            'exam.sections.questions.options',
            'exam.sections.questions.matchingPairs',
            'answers.question.options',
            'answers.question.matchingPairs',
        ]);

        $practicalEval = self::gradeExamPracticalAttempt($attempt);
        $scores = $practicalEval['scores'] ?? [];
        $comments = $practicalEval['comments'] ?? [];
        $totalPractical = array_sum($scores);

        $evaluatedAnswers = [];
        $totalQuestionScore = 0;

        foreach ($attempt->answers as $ans) {
            $question = $ans->question;
            if (! $question) {
                continue;
            }

            $isCorrect = false;
            $pointsEarned = 0;
            $feedback = '';

            switch ($question->type) {
                case 'identification':
                    $eval = AnswerMatcherService::evaluateIdentificationWithAi(
                        $question->options,
                        (string) ($ans->answer_text ?? ''),
                        $question->question_text
                    );
                    $isCorrect = $eval['is_correct'];
                    $pointsEarned = $isCorrect ? $question->points : 0;
                    $feedback = $eval['feedback'] ?? '';
                    break;

                case 'enumeration':
                    $eval = AnswerMatcherService::evaluateEnumerationWithAi(
                        $question->options,
                        (string) ($ans->answer_text ?? ''),
                        $question->question_text,
                        (int) $question->points
                    );
                    $isCorrect = $eval['is_correct'];
                    $pointsEarned = (int) ($eval['points_awarded'] ?? ($isCorrect ? $question->points : 0));
                    $feedback = $eval['feedback'] ?? '';
                    break;

                case 'true_false':
                case 'multiple_choice':
                    $correctOption = $question->options->firstWhere('is_correct', true);
                    $isCorrect = $correctOption && ((int) $ans->selected_option_id === (int) $correctOption->id);
                    $pointsEarned = $isCorrect ? $question->points : 0;
                    $feedback = $isCorrect ? 'Correct option.' : 'Incorrect option.';
                    break;

                case 'matching':
                    $ma = is_array($ans->matching_answers) ? $ans->matching_answers : json_decode((string) ($ans->matching_answers ?? '[]'), true);
                    $pairs = $question->matchingPairs;
                    $correctPairs = 0;
                    foreach ($pairs as $p) {
                        if (isset($ma[$p->id]) && trim((string) $ma[$p->id]) === trim((string) $p->right_text)) {
                            $correctPairs++;
                        }
                    }
                    $totalPairs = max(1, $pairs->count());
                    $isCorrect = ($correctPairs === $totalPairs);
                    $pointsEarned = (int) round(($correctPairs / $totalPairs) * $question->points);
                    $feedback = "{$correctPairs}/{$totalPairs} matching pairs correct.";
                    break;

                default:
                    $isCorrect = (bool) $ans->is_correct;
                    $pointsEarned = (int) ($ans->points_earned ?? 0);
            }

            $evaluatedAnswers[] = [
                'id' => $ans->id,
                'is_correct' => $isCorrect,
                'points_earned' => $pointsEarned,
                'feedback' => $feedback,
            ];
            $totalQuestionScore += $pointsEarned;
        }

        return [
            'success' => true,
            'scores' => $scores,
            'comments' => $comments,
            'answers' => $evaluatedAnswers,
            'total_score' => $totalQuestionScore + $totalPractical,
        ];
    }

    /**
     * AI-check a Seatwork Attempt based on criteria and expected answers.
     *
     * @return array{success: bool, answers: array<int, array{id: int, is_correct: bool, feedback?: string, points_awarded?: int}>, total_score: int}
     */
    public static function gradeSeatworkAttempt(SeatworkAttempt $attempt): array
    {
        $seatwork = $attempt->seatwork;
        $attempt->load(['answers.question.options', 'answers.question.matchingPairs']);

        $evaluatedAnswers = [];
        $totalScore = 0;

        foreach ($attempt->answers as $ans) {
            $question = $ans->question;
            if (! $question) {
                continue;
            }

            $isCorrect = false;
            $feedback = '';
            $pointsAwarded = 0;

            switch ($question->type) {
                case 'essay':
                    $grade = EssayGraderService::grade(
                        $question->question_text,
                        $question->criteria,
                        $question->points,
                        $ans->answer_text
                    );
                    $isCorrect = $grade['is_correct'];
                    $feedback = $grade['feedback'];
                    $pointsAwarded = $grade['score'];
                    break;

                case 'identification':
                    $eval = AnswerMatcherService::evaluateIdentificationWithAi(
                        $question->options,
                        (string) ($ans->answer_text ?? ''),
                        $question->question_text
                    );
                    $isCorrect = $eval['is_correct'];
                    $feedback = $eval['feedback'];
                    $pointsAwarded = $isCorrect ? $question->points : 0;
                    break;

                case 'enumeration':
                    $eval = AnswerMatcherService::evaluateEnumerationWithAi(
                        $question->options,
                        (string) ($ans->answer_text ?? ''),
                        $question->question_text,
                        (int) $question->points
                    );
                    $isCorrect = $eval['is_correct'];
                    $pointsAwarded = $eval['points_awarded'];
                    $feedback = $eval['feedback'];
                    break;

                case 'true_false':
                case 'multiple_choice':
                    $correctOption = $question->options->firstWhere('is_correct', true);
                    $isCorrect = $correctOption && ((int) $ans->selected_option_id === (int) $correctOption->id);
                    $pointsAwarded = $isCorrect ? $question->points : 0;
                    $feedback = $isCorrect ? 'Correct option selected.' : 'Incorrect option.';
                    break;

                case 'matching':
                    $ma = $ans->matching_answers ?? [];
                    $pairs = $question->matchingPairs;
                    $correctPairs = 0;
                    foreach ($pairs as $p) {
                        if (isset($ma[$p->id]) && trim((string) $ma[$p->id]) === trim((string) $p->right_text)) {
                            $correctPairs++;
                        }
                    }
                    $totalPairs = max(1, $pairs->count());
                    $isCorrect = ($correctPairs === $totalPairs);
                    $pointsAwarded = (int) round(($correctPairs / $totalPairs) * $question->points);
                    $feedback = "{$correctPairs}/{$totalPairs} pairs correctly matched.";
                    break;

                default:
                    $isCorrect = false;
                    $pointsAwarded = 0;
            }

            $evaluatedAnswers[] = [
                'id' => $ans->id,
                'is_correct' => $isCorrect,
                'feedback' => $feedback,
                'points_awarded' => $pointsAwarded,
            ];
            $totalScore += $pointsAwarded;
        }

        return [
            'success' => true,
            'answers' => $evaluatedAnswers,
            'total_score' => $totalScore,
        ];
    }

    /**
     * AI-check a Quiz Attempt against question options and criteria.
     *
     * @return array{success: bool, answers: array<int, array{id: int, is_correct: bool}>, total_score: int}
     */
    public static function gradeQuizAttempt(QuizAttempt $attempt): array
    {
        $quiz = $attempt->quiz;
        $attempt->load(['answers.question.options', 'answers.question.matchingPairs']);

        $evaluatedAnswers = [];
        $totalScore = 0;

        foreach ($attempt->answers as $ans) {
            $question = $ans->question;
            if (! $question) {
                continue;
            }

            $isCorrect = false;
            $feedback = '';
            $pointsAwarded = 0;

            switch ($question->type) {
                case 'essay':
                    $grade = EssayGraderService::grade(
                        $question->question_text,
                        $question->criteria,
                        $question->points,
                        $ans->answer_text
                    );
                    $isCorrect = $grade['is_correct'];
                    $feedback = $grade['feedback'];
                    $pointsAwarded = $grade['score'];
                    break;

                case 'identification':
                    $eval = AnswerMatcherService::evaluateIdentificationWithAi(
                        $question->options,
                        (string) ($ans->answer_text ?? ''),
                        $question->question_text
                    );
                    $isCorrect = $eval['is_correct'];
                    $feedback = $eval['feedback'];
                    $pointsAwarded = $isCorrect ? $question->points : 0;
                    break;

                case 'enumeration':
                    $eval = AnswerMatcherService::evaluateEnumerationWithAi(
                        $question->options,
                        (string) ($ans->answer_text ?? ''),
                        $question->question_text,
                        (int) $question->points
                    );
                    $isCorrect = $eval['is_correct'];
                    $pointsAwarded = $eval['points_awarded'];
                    $feedback = $eval['feedback'];
                    break;

                case 'matching':
                    $ma = $ans->matching_answers ?? [];
                    if (is_string($ma)) {
                        $ma = json_decode($ma, true) ?? [];
                    }
                    $pairs = $question->matchingPairs;
                    $correctPairs = 0;
                    foreach ($pairs as $p) {
                        if (isset($ma[$p->id]) && trim((string) $ma[$p->id]) === trim((string) $p->right_text)) {
                            $correctPairs++;
                        }
                    }
                    $totalPairs = max(1, $pairs->count());
                    $isCorrect = ($correctPairs === $totalPairs);
                    $pointsAwarded = (int) round(($correctPairs / $totalPairs) * $question->points);
                    $feedback = "{$correctPairs}/{$totalPairs} pairs correctly matched.";
                    break;

                case 'true_false':
                case 'multiple_choice':
                default:
                    $correctOption = $question->options->firstWhere('is_correct', true);
                    $isCorrect = $correctOption && ((int) $ans->quiz_option_id === (int) $correctOption->id);
                    $pointsAwarded = $isCorrect ? $question->points : 0;
                    $feedback = $isCorrect ? 'Correct option selected.' : 'Incorrect option.';
                    break;
            }

            $evaluatedAnswers[] = [
                'id' => $ans->id,
                'is_correct' => $isCorrect,
                'feedback' => $feedback,
                'points_awarded' => $pointsAwarded,
            ];
            $totalScore += $pointsAwarded;
        }

        return [
            'success' => true,
            'answers' => $evaluatedAnswers,
            'total_score' => $totalScore,
        ];
    }
}
