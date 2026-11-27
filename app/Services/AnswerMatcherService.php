<?php

namespace App\Services;

class AnswerMatcherService
{
    /**
     * Map of common number words to digits.
     */
    private const NUMBER_MAP = [
        'zero' => '0',
        'one' => '1',
        'two' => '2',
        'three' => '3',
        'four' => '4',
        'five' => '5',
        'six' => '6',
        'seven' => '7',
        'eight' => '8',
        'nine' => '9',
        'ten' => '10',
        'first' => '1',
        'second' => '2',
        'third' => '3',
        'fourth' => '4',
        'fifth' => '5',
    ];

    /**
     * Fillers and generic noun suffixes in school test contexts
     * that do not change the core concept/idea of the answer.
     */
    private const FILLERS = [
        'tool',
        'tools',
        'feature',
        'features',
        'property',
        'properties',
        'button',
        'buttons',
        'panel',
        'panels',
        'option',
        'options',
        'menu',
        'menus',
        'command',
        'commands',
        'element',
        'elements',
        'mode',
        'modes',
        'key',
        'keys',
        'shortcut',
        'shortcuts',
        'bar',
        'bars',
        'box',
        'boxes',
        'icon',
        'icons',
        'field',
        'fields',
        'window',
        'windows',
        'type',
        'types',
        'method',
        'methods',
        'item',
        'items',
    ];

    /**
     * Map common symbols to textual equivalents and vice-versa.
     */
    private const SYMBOL_MAP = [
        '&' => 'and',
        '+' => 'plus',
        '%' => 'percent',
        '#' => 'number',
        '@' => 'at',
        '=' => 'equals',
        '/' => 'slash',
        '*' => 'asterisk',
        '<' => 'less than',
        '>' => 'greater than',
        '~' => 'tilde',
        '^' => 'caret',
    ];

    /**
     * Known tool & concept synonyms (two-way conceptual equivalence).
     */
    private const SYNONYM_GROUPS = [
        ['selection tool', 'black arrow', 'selection', 'pointer', 'arrow pointer', 'v'],
        ['direct selection tool', 'white arrow', 'direct selection', 'anchor point tool', 'a'],
        ['type tool', 'text tool', 'text', 'type', 't'],
        ['pen tool', 'bezier pen', 'path tool', 'pen', 'p'],
        ['pencil tool', 'pencil', 'n'],
        ['rectangle tool', 'box tool', 'rectangle', 'rect', 'm'],
        ['ellipse tool', 'circle tool', 'oval tool', 'ellipse', 'l'],
        ['polygon tool', 'polygon'],
        ['line tool', 'line', 'backslash'],
        ['hand tool', 'pan tool', 'pan', 'hand', 'h', 'spacebar'],
        ['zoom tool', 'magnifier', 'magnifying glass', 'zoom', 'z'],
        ['eyedropper tool', 'eyedropper', 'color picker', 'i'],
        ['gradient tool', 'gradient', 'g'],
        ['scissors tool', 'scissors', 'c'],
        ['eraser tool', 'eraser', 'shift e'],
        ['rotate tool', 'rotate', 'r'],
        ['scale tool', 'scale', 's'],
        ['free transform tool', 'free transform', 'e'],
        ['undo', 'ctrl z', 'control z', 'cmd z', 'command z'],
        ['redo', 'ctrl y', 'control y', 'cmd y', 'command y', 'ctrl shift z', 'cmd shift z'],
        ['copy', 'ctrl c', 'control c', 'cmd c', 'command c'],
        ['paste', 'ctrl v', 'control v', 'cmd v', 'command v'],
        ['cut', 'ctrl x', 'control x', 'cmd x', 'command x'],
        ['save', 'ctrl s', 'control s', 'cmd s', 'command s'],
        ['save as', 'ctrl shift s', 'cmd shift s'],
        ['select all', 'ctrl a', 'control a', 'cmd a', 'command a'],
        ['deselect', 'ctrl shift a', 'cmd shift a', 'ctrl d', 'cmd d'],
        ['group', 'ctrl g', 'control g', 'cmd g', 'command g'],
        ['ungroup', 'ctrl shift g', 'cmd shift g'],
        ['print', 'ctrl p', 'control p', 'cmd p', 'command p'],
        ['new document', 'ctrl n', 'cmd n'],
        ['open document', 'ctrl o', 'cmd o'],
        ['zoom in', 'ctrl plus', 'cmd plus', 'plus', '+', 'ctrl +'],
        ['zoom out', 'ctrl minus', 'cmd minus', 'minus', '-', 'ctrl -'],
        ['stroke', 'outline', 'border', 'line weight'],
        ['fill', 'interior color', 'inside color', 'fill color', 'swatch'],
        ['cmyk', 'cyan magenta yellow black', 'cyan magenta yellow key'],
        ['rgb', 'red green blue'],
        ['dpi', 'dots per inch', 'ppi', 'pixels per inch'],
        ['jpeg', 'jpg'],
        ['png', 'portable network graphics'],
        ['pdf', 'portable document format'],
        ['gif', 'graphics interchange format'],
        ['transparency', 'opacity', 'transparent', 'see through', 'partly see through', 'semi transparent', 'translucent'],
        ['readability', 'legibility', 'readable', 'legible', 'reading ease'],
        ['consistency', 'uniformity', 'visual consistency', 'consistent', 'harmony'],
    ];

    /**
     * Stop words / function words to ignore when comparing conceptual phrase answers.
     */
    private const STOP_WORDS = [
        'a', 'an', 'the',
        'is', 'are', 'was', 'were', 'be', 'been', 'being',
        'to', 'of', 'in', 'for', 'on', 'with', 'at', 'by', 'from', 'up', 'about', 'into', 'over', 'after', 'under', 'above',
        'and', 'or', 'so', 'but', 'while', 'which', 'that', 'this', 'these', 'those',
        'it', 'its', 'they', 'them', 'their', 'we', 'us', 'our', 'you', 'your', 'he', 'him', 'his', 'she', 'her',
        'can', 'could', 'should', 'would', 'may', 'might', 'must', 'will', 'shall',
        'as', 'if', 'because', 'than', 'then', 'all', 'any', 'every', 'some', 'when', 'where', 'what', 'why', 'how',
        'i', 'my', 'me', 'think', 'answer',
    ];

    /**
     * Conflicting pairs: if expected has one and student has the other, they must NEVER match.
     */
    private const EXCLUSIVE_PAIRS = [
        ['stroke', 'fill'],
        ['selection', 'direct selection'],
        ['pen', 'pencil'],
        ['rectangle', 'ellipse'],
        ['rectangle', 'circle'],
        ['rectangle', 'polygon'],
        ['ellipse', 'polygon'],
        ['circle', 'polygon'],
        ['zoom in', 'zoom out'],
        ['undo', 'redo'],
        ['copy', 'paste'],
        ['cmyk', 'rgb'],
        ['vector', 'raster'],
        ['lossless', 'lossy'],
    ];

    /**
     * Evaluate if a student answer matches the expected answer closely
     * (exact, typo tolerance, singular/plural, substring, or conceptual near match).
     */
    public static function isNearMatch(?string $expected, ?string $student, ?string $questionText = null): bool
    {
        if ($expected === null || $student === null) {
            return false;
        }

        $normExpected = self::normalize($expected);
        $normStudent = self::normalize($student);

        if ($normExpected === '' || $normStudent === '') {
            return false;
        }

        // 0. Safety check against known contradictory/exclusive terms
        if (self::hasConflictingExclusivePair($normExpected, $normStudent)) {
            return false;
        }

        // 1. Direct normalized equality
        if ($normExpected === $normStudent) {
            return true;
        }

        // 1a. Conversational wrapper stripping equality ("use selection tool" == "selection tool")
        $cleanExp = self::stripConversationalWrappers($normExpected);
        $cleanStu = self::stripConversationalWrappers($normStudent);
        if ($cleanExp !== '' && $cleanExp === $cleanStu) {
            return true;
        }

        // 1b. Symbol & textual translation equivalence ("&" == "and", "+" == "plus")
        if (self::replaceSymbols($normExpected) === self::replaceSymbols($normStudent)) {
            return true;
        }
        if ($cleanExp !== '' && self::replaceSymbols($cleanExp) === self::replaceSymbols($cleanStu)) {
            return true;
        }

        // 1c. Keyboard shortcut normalization ("Ctrl+Z" == "Ctrl Z" == "Cmd Z")
        if (self::normalizeShortcuts($normExpected) === self::normalizeShortcuts($normStudent)) {
            return true;
        }
        if ($cleanExp !== '' && self::normalizeShortcuts($cleanExp) === self::normalizeShortcuts($cleanStu)) {
            return true;
        }

        // 1d. Conceptual synonyms & alternate terms (e.g. "Selection Tool" == "Black Arrow" == "V")
        if (self::areSynonyms($normExpected, $normStudent) || ($cleanExp !== '' && self::areSynonyms($cleanExp, $cleanStu))) {
            return true;
        }

        // 2. Boolean check (True / False / T / F)
        if (self::isBoolean($normExpected)) {
            return self::toBooleanString($normExpected) === self::toBooleanString($normStudent);
        }

        // 3. Number word to digit normalization
        $numExpected = self::normalizeNumbers($normExpected);
        $numStudent = self::normalizeNumbers($normStudent);
        if ($numExpected === $numStudent) {
            return true;
        }

        // 4. Strip filler words ("tool", "feature", etc.)
        $strippedExpected = self::stripFillers($cleanExp !== '' ? $cleanExp : $normExpected);
        $strippedStudent = self::stripFillers($cleanStu !== '' ? $cleanStu : $normStudent);
        if ($strippedExpected !== '' && $strippedExpected === $strippedStudent) {
            return true;
        }

        // 5. Morphological / concept root multiset and phrase analysis
        $expRoots = self::getConceptRoots($cleanExp !== '' ? $cleanExp : $normExpected);
        $stuRoots = self::getConceptRoots($cleanStu !== '' ? $cleanStu : $normStudent);

        if (! empty($expRoots) && ! empty($stuRoots)) {
            $sortedExp = $expRoots;
            $sortedStu = $stuRoots;
            sort($sortedExp);
            sort($sortedStu);

            // A. Exact root multiset equality (e.g. "consistency and readability" == "readable and consistent")
            if ($sortedExp === $sortedStu) {
                return true;
            }

            // B. Student contains ALL expected concepts in their phrase
            // (e.g. Expected: "Selection Tool" -> Student: "use selection tool to move it")
            $diff = array_diff($expRoots, $stuRoots);
            if (empty($diff)) {
                return true;
            }

            // C. Multi-concept phrase overlap
            // (e.g. Expected: "Adjusting color or transparency to improve readability" -> Student: "adjust color or transparency")
            $commonRoots = array_intersect($expRoots, $stuRoots);
            $commonCount = count($commonRoots);
            $expCount = count($expRoots);
            $stuCount = count($stuRoots);

            if ($expCount >= 2) {
                // At least 2 concept matches and >= 50% of expected concepts captured
                if ($commonCount >= 2 && ($commonCount / $expCount) >= 0.50) {
                    return true;
                }
                // High Jaccard similarity across concepts (>= 50%)
                if ($commonCount / max($expCount, $stuCount) >= 0.50) {
                    return true;
                }
                // If student provided a concise answer that is >= 66% matched with expected roots
                if ($stuCount >= 2 && ($commonCount / $stuCount) >= 0.66 && $commonCount >= 2) {
                    return true;
                }
            } elseif ($expCount === 1) {
                // Single concept: student phrase contains the concept and isn't contradictory
                if ($commonCount === 1 && $stuCount <= 4) {
                    return true;
                }
            }
        }

        // 6. Substring / Core concept containment
        $targetExp = $strippedExpected !== '' ? $strippedExpected : ($cleanExp !== '' ? $cleanExp : $normExpected);
        $targetStu = $strippedStudent !== '' ? $strippedStudent : ($cleanStu !== '' ? $cleanStu : $normStudent);

        if (mb_strlen($targetExp) >= 3) {
            if ($targetStu === $targetExp) {
                return true;
            }
            if (self::containsWholePhrase($targetStu, $targetExp)
                || (mb_strlen($targetStu) >= 4 && self::containsWholePhrase($targetExp, $targetStu))) {
                return true;
            }
        }

        // 7. Token bag / Word overlap
        $expectedWords = self::getWords($targetExp);
        $studentWords = self::getWords($targetStu);
        if (! empty($expectedWords) && ! empty($studentWords)) {
            $intersection = array_intersect($expectedWords, $studentWords);
            if (count($intersection) === count($expectedWords)) {
                return true;
            }
            $overlap = count($intersection) / max(count($expectedWords), count($studentWords));
            if ($overlap >= 0.50) {
                return true;
            }
        }

        // 8. Edit distance / Typo tolerance (Levenshtein)
        $len = max(mb_strlen($targetExp), mb_strlen($targetStu));
        if ($len >= 4) {
            $lev = levenshtein($targetExp, $targetStu);
            $maxDistance = $len >= 20 ? 4 : ($len >= 8 ? 2 : 1);
            if ($lev <= $maxDistance) {
                return true;
            }
        }

        // 9. Percentage similarity
        similar_text($targetExp, $targetStu, $percent);
        if ($percent >= 75.0) {
            return true;
        }

        // 10. Phonetic equivalence (metaphone) for single words
        if (! str_contains($targetExp, ' ') && ! str_contains($targetStu, ' ')) {
            if (mb_strlen($targetExp) >= 4 && mb_strlen($targetStu) >= 4) {
                if (metaphone($targetExp) === metaphone($targetStu)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if student answer matches any option in an options collection/array.
     */
    public static function matchesAny($options, ?string $student, ?string $questionText = null): bool
    {
        if (empty($options) || $student === null) {
            return false;
        }

        $items = is_array($options) ? $options : (is_iterable($options) ? iterator_to_array($options) : []);

        foreach ($items as $option) {
            $text = is_object($option) ? ($option->option_text ?? '') : (is_array($option) ? ($option['option_text'] ?? '') : (string) $option);
            $isCorrect = is_object($option) ? ($option->is_correct ?? true) : (is_array($option) ? ($option['is_correct'] ?? true) : true);

            if ($isCorrect && self::isNearMatch($text, $student, $questionText)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Evaluate with AI semantic check when AI is active, falling back to heuristic.
     *
     * @return array{is_correct: bool, feedback: string}
     */
    public static function evaluateWithAi(string $expected, string $student, string $questionText = ''): array
    {
        return self::evaluateIdentificationWithAi([$expected], $student, $questionText);
    }

    /**
     * Evaluate identification answer against options collection/array with near matching & AI semantic check.
     *
     * @return array{is_correct: bool, feedback: string}
     */
    public static function evaluateIdentificationWithAi($options, ?string $student, string $questionText = ''): array
    {
        $studentText = trim((string) ($student ?? ''));
        if ($studentText === '') {
            return [
                'is_correct' => false,
                'feedback' => 'No answer provided.',
            ];
        }

        // 1. Fast near-match against all accepted options
        if (self::matchesAny($options, $studentText, $questionText)) {
            return [
                'is_correct' => true,
                'feedback' => 'Correct identification (near match accepted).',
            ];
        }

        // Extract list of all expected answers
        $items = is_array($options) ? $options : (is_iterable($options) ? iterator_to_array($options) : []);
        $expectedList = [];
        foreach ($items as $option) {
            $t = is_object($option) ? ($option->option_text ?? '') : (is_array($option) ? ($option['option_text'] ?? '') : (string) $option);
            $t = trim((string) $t);
            if ($t !== '' && ! in_array($t, $expectedList, true)) {
                $expectedList[] = $t;
            }
        }
        $expectedDisplay = implode(' / ', $expectedList);

        // 2. AI semantic alignment check
        $provider = AiConfigService::resolveActiveProvider();
        if ($provider !== 'heuristic') {
            $skillPrompt = AiConfigService::getSkillPrompt();
            $gradingGuide = AiConfigService::getGradingInstructions();
            $systemPrompt = "{$skillPrompt}\nGrading Guidelines: {$gradingGuide}\n"
                . "You are an educator evaluating a student's answer for a school assessment.\n"
                . "CRITICAL INSTRUCTION - ACCEPT NEAR MATCHES, ALTERNATE TERMS, AND SYMBOLS:\n"
                . "1. SYMBOLS & PUNCTUATION: Accept symbol equivalents (e.g. '&' for 'and', '+' for 'plus' or 'zoom in', '-' for 'minus', '/' for 'or' or 'slash', '%' for 'percent', '#' for 'number', '=' for 'equals').\n"
                . "2. KEYBOARD SHORTCUTS & SYMBOL KEYS: Accept shortcuts as valid answers for tools and commands (e.g. 'V' for Selection Tool, 'A' for Direct Selection Tool, 'P' for Pen Tool, 'T' for Type Tool, 'Ctrl+Z' or 'Cmd+Z' for Undo, 'Spacebar' for Hand Tool).\n"
                . "3. ALTERNATE NAMES & SYNONYMS: Accept common alternate terminology or descriptive names (e.g. 'black arrow' for 'Selection Tool', 'white arrow' for 'Direct Selection Tool', 'text tool' for 'Type Tool', 'magnifier' for 'Zoom Tool', 'pan' for 'Hand Tool', 'outline' for 'Stroke', 'border' for 'Stroke', 'box' for 'Rectangle').\n"
                . "4. PHRASING & TYPOS: Accept typos, missing articles, singular/plural differences, or informal descriptions that identify the same tool, concept, or feature.\n"
                . "If the student's answer is near, uses a different term, symbol, abbreviation, or shortcut for the expected concept, you MUST APPROVE IT (is_correct: true).\n"
                . "Only reject (is_correct: false) if it is factually incorrect for an entirely different concept (e.g., saying 'Pen Tool' when the answer is 'Selection Tool'), contradictory, or empty.\n"
                . "Respond with ONLY JSON: {\"is_correct\": true|false, \"feedback\": \"brief explanation\"}";

            $userPrompt = "Question: {$questionText}\n"
                . "Expected Answer(s): {$expectedDisplay}\n"
                . "Student Answer: {$studentText}\n\n"
                . "Is the student answer acceptable if the idea is there, or if it uses a symbol, shortcut, or alternate term? Approve if aligned.";

            $raw = ActivityAiGraderService::callAi($systemPrompt, $userPrompt, 256);
            if ($raw) {
                $decoded = json_decode(preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $raw), true);
                if (is_array($decoded) && isset($decoded['is_correct'])) {
                    $isCorrect = (bool) $decoded['is_correct'];
                    return [
                        'is_correct' => $isCorrect,
                        'feedback' => $decoded['feedback'] ?? ($isCorrect ? 'Accepted (conceptually aligned).' : "Expected: {$expectedDisplay}"),
                    ];
                }
            }
        }

        return [
            'is_correct' => false,
            'feedback' => "Expected: {$expectedDisplay}",
        ];
    }

    /**
     * Evaluate enumeration answer against options collection/array with near matching & AI semantic check.
     *
     * @return array{is_correct: bool, points_awarded: int, match_count: int, total_expected: int, feedback: string}
     */
    public static function evaluateEnumerationWithAi($options, ?string $studentAnswer, string $questionText = '', int $points = 1): array
    {
        $items = is_array($options) ? $options : (is_iterable($options) ? iterator_to_array($options) : []);
        $expectedItems = [];
        foreach ($items as $option) {
            $t = is_object($option) ? ($option->option_text ?? '') : (is_array($option) ? ($option['option_text'] ?? '') : (string) $option);
            $t = trim((string) $t);
            if ($t !== '' && ! in_array($t, $expectedItems, true)) {
                $expectedItems[] = $t;
            }
        }

        $totalExpected = max(1, count($expectedItems));
        $rawAnswer = trim((string) ($studentAnswer ?? ''));

        if ($rawAnswer === '') {
            return [
                'is_correct' => false,
                'points_awarded' => 0,
                'match_count' => 0,
                'total_expected' => $totalExpected,
                'feedback' => 'No answer provided.',
            ];
        }

        // Parse student entries: handle lines, commas, semicolons, bullets, and numbers
        $rawLines = preg_split('/[\r\n]+/', $rawAnswer);
        $studentItems = [];
        foreach ($rawLines as $line) {
            $parts = preg_split('/(?<=\w)[,;]+(?=\s*\w)/', $line);
            foreach ($parts as $p) {
                $clean = trim(preg_replace('/^\s*(?:[0-9]+[.)\-:]|[-*•])\s*/', '', trim($p)));
                if ($clean !== '') {
                    $studentItems[] = $clean;
                }
            }
        }

        // Pass 1: Heuristic near-matching
        $matchedExpectedIndices = [];
        $unmatchedStudentItems = [];

        foreach ($studentItems as $sItem) {
            $matched = false;
            foreach ($expectedItems as $idx => $exp) {
                if (! in_array($idx, $matchedExpectedIndices, true)) {
                    if (self::isNearMatch($exp, $sItem, $questionText)) {
                        $matchedExpectedIndices[] = $idx;
                        $matched = true;
                        break;
                    }
                }
            }
            if (! $matched) {
                $unmatchedStudentItems[] = $sItem;
            }
        }

        $aiMatchedCount = 0;
        $unmatchedExpectedItems = [];
        foreach ($expectedItems as $idx => $exp) {
            if (! in_array($idx, $matchedExpectedIndices, true)) {
                $unmatchedExpectedItems[] = $exp;
            }
        }

        // Pass 2: AI semantic check if there are remaining unmatched items
        $provider = AiConfigService::resolveActiveProvider();
        if ($provider !== 'heuristic' && count($unmatchedStudentItems) > 0 && count($unmatchedExpectedItems) > 0) {
            $skillPrompt = AiConfigService::getSkillPrompt();
            $gradingGuide = AiConfigService::getGradingInstructions();
            $systemPrompt = "{$skillPrompt}\nGrading Guidelines: {$gradingGuide}\n"
                . "You are an educator evaluating a student's answer for an enumeration assessment item.\n"
                . "CRITICAL INSTRUCTION - ACCEPT NEAR MATCHES, ALTERNATE TERMS, AND SYMBOLS:\n"
                . "1. SYMBOLS & PUNCTUATION: Accept symbol equivalents (e.g. '&' for 'and', '+' for 'plus', '/' for 'or', etc.).\n"
                . "2. KEYBOARD SHORTCUTS: Accept shortcuts representing tools or actions (e.g. 'V' for Selection Tool, 'Ctrl+Z' for Undo, 'T' for Type Tool).\n"
                . "3. ALTERNATE NAMES & SYNONYMS: Accept common alternate terms (e.g. 'black arrow' for 'Selection Tool', 'text tool' for 'Type Tool', 'magnifier' for 'Zoom Tool', 'pan' for 'Hand Tool', 'border' for 'Stroke').\n"
                . "4. PHRASING & TYPOS: Accept typos, partial phrasing, singular/plural, or descriptive words that capture the same item.\n"
                . "Count any student item as matched if it aligns with, represents (by symbol or shortcut), or names one of the remaining expected items.\n"
                . "Respond with ONLY JSON: {\"matched_count\": <integer between 0 and " . count($unmatchedExpectedItems) . ">, \"feedback\": \"brief explanation\"}";

            $userPrompt = "Question: {$questionText}\n"
                . "Remaining Expected Items: " . implode(', ', $unmatchedExpectedItems) . "\n"
                . "Student Items to Check: " . implode(', ', $unmatchedStudentItems) . "\n\n"
                . "How many distinct remaining expected items did the student align with, name, or represent (including symbols or shortcuts)?";

            $raw = ActivityAiGraderService::callAi($systemPrompt, $userPrompt, 256);
            if ($raw) {
                $decoded = json_decode(preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $raw), true);
                if (is_array($decoded) && isset($decoded['matched_count'])) {
                    $aiMatchedCount = min(count($unmatchedExpectedItems), max(0, (int) $decoded['matched_count']));
                }
            }
        }

        $totalMatches = min($totalExpected, count($matchedExpectedIndices) + $aiMatchedCount);
        $pointsAwarded = (int) round(($totalMatches / $totalExpected) * $points);
        $isCorrect = ($totalMatches === $totalExpected || ($totalMatches > 0 && $totalMatches >= ceil($totalExpected / 2)));
        $feedback = "{$totalMatches}/{$totalExpected} items identified (near and conceptual matches accepted).";

        return [
            'is_correct' => $isCorrect,
            'points_awarded' => $pointsAwarded,
            'match_count' => $totalMatches,
            'total_expected' => $totalExpected,
            'feedback' => $feedback,
        ];
    }

    /**
     * Standardize string for comparison: lowercase, trim, remove punctuation, strip articles.
     */
    public static function normalize(string $str): string
    {
        $raw = trim($str);
        // Preserve standalone single symbols (e.g. "+", "-", "&", "%", "/", "#", "@")
        if (mb_strlen($raw) === 1 && ! ctype_alnum($raw)) {
            return mb_strtolower($raw);
        }

        $str = mb_strtolower($raw);

        // Normalize unicode whitespace and accents
        $str = preg_replace('/\s+/u', ' ', $str);

        // Separate symbols with spaces so "Fill & Stroke" becomes "fill & stroke"
        // and "Ctrl+Z" becomes "ctrl + z"
        $str = preg_replace('/([+&%#@=\/*<>])/u', ' $1 ', $str);

        // Strip leading/trailing quotes, periods, commas, colons
        $str = trim($str, " \t\n\r\0\x0B\"'.,:;!?");

        // Strip leading English articles
        $str = preg_replace('/^(?:the|a|an)\s+/i', '', $str);

        // Remove interior punctuation like dashes, periods, quotes to single space
        $str = preg_replace('/[.,:;!?"\']/u', '', $str);
        $str = preg_replace('/[-_]/u', ' ', $str);

        return trim(preg_replace('/\s+/', ' ', $str));
    }

    public static function replaceSymbols(string $str): string
    {
        $result = $str;
        foreach (self::SYMBOL_MAP as $symbol => $word) {
            $result = str_replace($symbol, ' ' . $word . ' ', $result);
        }

        return trim(preg_replace('/\s+/', ' ', $result));
    }

    public static function normalizeShortcuts(string $str): string
    {
        $s = mb_strtolower(trim($str));
        $s = str_replace(['control', 'command', 'cmd'], 'ctrl', $s);
        $s = str_replace(['+', '-'], ' ', $s);

        return trim(preg_replace('/\s+/', ' ', $s));
    }

    public static function areSynonyms(string $a, string $b): bool
    {
        $normA = self::normalizeShortcuts($a);
        $normB = self::normalizeShortcuts($b);

        if ($normA !== '' && $normA === $normB) {
            return true;
        }

        $strippedA = self::stripFillers($a);
        $strippedB = self::stripFillers($b);

        foreach (self::SYNONYM_GROUPS as $group) {
            $inA = in_array($a, $group, true) || in_array($normA, $group, true) || ($strippedA !== '' && in_array($strippedA, $group, true));
            $inB = in_array($b, $group, true) || in_array($normB, $group, true) || ($strippedB !== '' && in_array($strippedB, $group, true));
            if ($inA && $inB) {
                return true;
            }
        }

        return false;
    }

    private static function stripFillers(string $str): string
    {
        $words = explode(' ', $str);
        $filtered = array_filter($words, fn ($w) => ! in_array($w, self::FILLERS, true));

        return trim(implode(' ', $filtered));
    }

    private static function stem(string $str): string
    {
        $words = explode(' ', $str);
        $stemmed = array_map(function ($w) {
            if (str_ends_with($w, 'ies') && mb_strlen($w) > 4) {
                return substr($w, 0, -3) . 'y';
            }
            if (str_ends_with($w, 'es') && mb_strlen($w) > 4) {
                return substr($w, 0, -2);
            }
            if (str_ends_with($w, 's') && ! str_ends_with($w, 'ss') && mb_strlen($w) > 3) {
                return substr($w, 0, -1);
            }

            return $w;
        }, $words);

        return implode(' ', $stemmed);
    }

    private static function getWords(string $str): array
    {
        $words = array_filter(explode(' ', $str), fn ($w) => mb_strlen($w) >= 2);

        return array_values(array_unique($words));
    }

    private static function containsWholePhrase(string $haystack, string $needle): bool
    {
        if ($needle === '') {
            return false;
        }

        return (bool) preg_match('/\b' . preg_quote($needle, '/') . '\b/iu', $haystack);
    }

    private static function normalizeNumbers(string $str): string
    {
        $words = explode(' ', $str);
        foreach ($words as &$w) {
            if (isset(self::NUMBER_MAP[$w])) {
                $w = self::NUMBER_MAP[$w];
            }
        }

        return implode(' ', $words);
    }

    private static function isBoolean(string $str): bool
    {
        return in_array($str, ['true', 'false', 't', 'f', 'yes', 'no', '1', '0'], true);
    }

    private static function toBooleanString(string $str): string
    {
        return in_array($str, ['true', 't', 'yes', '1'], true) ? 'true' : 'false';
    }

    public static function hasConflictingExclusivePair(string $a, string $b): bool
    {
        foreach (self::EXCLUSIVE_PAIRS as [$x, $y]) {
            $aHasX = self::hasTerm($a, $x);
            $aHasY = self::hasTerm($a, $y);
            $bHasX = self::hasTerm($b, $x);
            $bHasY = self::hasTerm($b, $y);

            if (($aHasX && ! $aHasY && $bHasY && ! $bHasX) ||
                ($aHasY && ! $aHasX && $bHasX && ! $bHasY)) {
                return true;
            }
        }

        return false;
    }

    private static function hasTerm(string $str, string $term): bool
    {
        if ($term === 'selection') {
            $withoutDirect = preg_replace('/\bdirect\s+selection\b/iu', '', $str);
            return (bool) preg_match('/\bselection\b/iu', $withoutDirect);
        }

        return (bool) preg_match('/\b' . preg_quote($term, '/') . '\b/iu', $str);
    }

    public static function stripConversationalWrappers(string $str): string
    {
        $s = trim($str);
        if ($s === '') {
            return '';
        }

        $leadingPatterns = [
            '/^(?:i\s+(?:think|believe)\s+(?:that\s+)?(?:it\s+is\s+)?)/iu',
            '/^(?:(?:the|my)\s+answer\s+is\s+)/iu',
            '/^(?:it\s+is\s+|it\'s\s+|its\s+)/iu',
            '/^(?:you\s+can\s+use\s+|you\s+should\s+use\s+|we\s+can\s+use\s+|we\s+should\s+use\s+|one\s+can\s+use\s+)/iu',
            '/^(?:by\s+using\s+|by\s+|using\s+|use\s+)/iu',
            '/^(?:it\s+should\s+have\s+|it\s+must\s+have\s+|it\s+has\s+)/iu',
            '/^(?:it\s+should\s+be\s+|it\s+must\s+be\s+|it\s+can\s+be\s+|should\s+be\s+)/iu',
            '/^(?:to\s+use\s+|to\s+make\s+|to\s+create\s+)/iu',
            '/^(?:the\s+|a\s+|an\s+)/iu',
        ];

        $changed = true;
        while ($changed) {
            $changed = false;
            foreach ($leadingPatterns as $pattern) {
                $replaced = preg_replace($pattern, '', $s);
                if ($replaced !== null && $replaced !== $s) {
                    $s = trim($replaced);
                    $changed = true;
                }
            }
        }

        $trailingPatterns = [
            '/\s+(?:is\s+used|can\s+be\s+used|should\s+be\s+used|to\s+be\s+used)$/iu',
            '/\s+(?:is\s+the\s+answer|is\s+the\s+tool|is\s+needed)$/iu',
            '/\s+(?:tool\s+is\s+used|feature\s+is\s+used)$/iu',
        ];

        foreach ($trailingPatterns as $pattern) {
            $s = trim((string) preg_replace($pattern, '', $s));
        }

        return trim($s);
    }

    public static function stemWord(string $w): string
    {
        $w = mb_strtolower(trim($w));
        $len = mb_strlen($w);
        if ($len <= 3) {
            return $w;
        }

        if (str_ends_with($w, 'ability') && $len > 7) {
            return substr($w, 0, -7);
        }
        if (str_ends_with($w, 'ibility') && $len > 7) {
            return substr($w, 0, -7);
        }
        if (str_ends_with($w, 'ency') && $len > 5) {
            return substr($w, 0, -4);
        }
        if (str_ends_with($w, 'ancy') && $len > 5) {
            return substr($w, 0, -4);
        }
        if (str_ends_with($w, 'ent') && $len > 4) {
            return substr($w, 0, -3);
        }
        if (str_ends_with($w, 'able') && $len > 5) {
            return substr($w, 0, -4);
        }
        if (str_ends_with($w, 'ible') && $len > 5) {
            return substr($w, 0, -4);
        }
        if (str_ends_with($w, 'tions') && $len > 6) {
            return substr($w, 0, -5);
        }
        if (str_ends_with($w, 'tion') && $len > 5) {
            return substr($w, 0, -4);
        }
        if (str_ends_with($w, 'ively') && $len > 6) {
            return substr($w, 0, -5);
        }
        if (str_ends_with($w, 'ive') && $len > 5) {
            return substr($w, 0, -3);
        }
        if (str_ends_with($w, 'ingly') && $len > 6) {
            return substr($w, 0, -5);
        }
        if (str_ends_with($w, 'ing') && $len > 5) {
            $base = substr($w, 0, -3);
            if (preg_match('/([b-df-hj-np-tv-z])\1$/i', $base)) {
                $base = substr($base, 0, -1);
            }
            return $base;
        }
        if (str_ends_with($w, 'ed') && $len > 4) {
            $base = substr($w, 0, -2);
            if (preg_match('/([b-df-hj-np-tv-z])\1$/i', $base)) {
                $base = substr($base, 0, -1);
            }
            return $base;
        }
        if (str_ends_with($w, 'ies') && $len > 4) {
            return substr($w, 0, -3) . 'y';
        }
        if (str_ends_with($w, 'sses') && $len > 5) {
            return substr($w, 0, -2);
        }
        if (str_ends_with($w, 'es') && $len > 4) {
            return substr($w, 0, -2);
        }
        if (str_ends_with($w, 'ly') && $len > 4) {
            return substr($w, 0, -2);
        }
        if (str_ends_with($w, 's') && ! str_ends_with($w, 'ss') && $len > 3) {
            return substr($w, 0, -1);
        }

        return $w;
    }

    public static function getConceptRoots(string $str): array
    {
        $norm = self::normalize($str);
        if ($norm === '') {
            return [];
        }

        $norm = self::replaceSymbols($norm);

        static $synonymMap = null;
        if ($synonymMap === null) {
            $synonymMap = [];
            foreach (self::SYNONYM_GROUPS as $group) {
                $canonical = $group[0];
                foreach ($group as $syn) {
                    if ($syn !== $canonical) {
                        $synonymMap[$syn] = $canonical;
                    }
                }
            }
            uksort($synonymMap, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));
        }

        foreach ($synonymMap as $syn => $canonical) {
            if (mb_strlen($syn) > 1) {
                $norm = preg_replace('/\b' . preg_quote($syn, '/') . '\b/iu', $canonical, $norm);
            }
        }

        $words = preg_split('/\s+/u', $norm, -1, PREG_SPLIT_NO_EMPTY);
        $roots = [];

        foreach ($words as $w) {
            $w = trim($w);
            if ($w === '') {
                continue;
            }

            if (in_array($w, self::STOP_WORDS, true)) {
                continue;
            }

            if (in_array($w, self::FILLERS, true)) {
                continue;
            }

            if (mb_strlen($w) <= 1 && ! ctype_digit($w)) {
                continue;
            }

            $stemmed = self::stemWord($w);
            if ($stemmed !== '') {
                $roots[] = $stemmed;
            }
        }

        return array_values(array_unique($roots));
    }
}
