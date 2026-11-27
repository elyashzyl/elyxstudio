<?php

namespace App\Services;

use App\Models\TeachingMaterial;

class MaterialTextExtractor
{
    /**
     * Extract readable text from an uploaded material.
     * Returns ['text' => string] or ['error' => string].
     */
    public static function extract(TeachingMaterial $material): array
    {
        $text = '';
        $absolute = $material->getResolvedFilePath();
        $cleanupTemp = false;

        // If not found on disk, attempt extracting from embedded base64 file_data
        if ((! $absolute || ! file_exists($absolute)) && ! empty($material->file_data)) {
            $tmp = tempnam(sys_get_temp_dir(), 'mat_');
            $binary = base64_decode((string) $material->file_data, true);
            if ($binary !== false && file_put_contents($tmp, $binary) !== false) {
                $absolute = $tmp;
                $cleanupTemp = true;
            }
        }

        if ($absolute && file_exists($absolute)) {
            $type = strtolower($material->file_type ?: pathinfo($absolute, PATHINFO_EXTENSION));

            try {
                $text = self::extractFromPath($absolute, $type);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('MaterialTextExtractor file parse error: ' . $e->getMessage());
            } finally {
                if ($cleanupTemp && file_exists($absolute)) {
                    @unlink($absolute);
                }
            }
        }

        $metaParts = array_filter([
            $material->title ? "Material Title: {$material->title}" : null,
            $material->subject ? "Subject: {$material->subject}" : null,
            $material->gradeLevel?->name ? "Grade Level: {$material->gradeLevel->name}" : null,
            $material->description ? "Overview / Description: {$material->description}" : null,
        ]);

        $metaHeader = trim(implode("\n", $metaParts));
        $fileText = trim($text);

        if ($fileText !== '') {
            $fullText = $metaHeader !== ''
                ? $metaHeader . "\n\n=== Document Content ===\n" . $fileText
                : $fileText;
        } else {
            $fullText = $metaHeader;
        }

        if (trim($fullText) === '') {
            return ['error' => 'No readable text could be extracted from this material or its description.'];
        }

        return ['text' => $fullText];
    }

    /**
     * Extract readable text from an uploaded HTTP file.
     * Returns ['text' => string, 'filename' => string] or ['error' => string].
     */
    public static function extractFromFile(\Illuminate\Http\UploadedFile $file): array
    {
        $path = $file->getRealPath();
        if (! $path || ! file_exists($path)) {
            return ['error' => 'Uploaded file could not be read from disk.'];
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: '');
        $text = self::extractFromPath($path, $extension);

        if (trim($text) === '') {
            return ['error' => 'No readable text could be extracted from this file. If this is a scanned document, please paste the text directly.'];
        }

        return [
            'text' => $text,
            'filename' => $file->getClientOriginalName(),
            'extension' => $extension,
        ];
    }

    /**
     * Extract text from any file on disk given its path and optional type.
     */
    public static function extractFromPath(string $absolute, ?string $type = null): string
    {
        if (! file_exists($absolute)) {
            return '';
        }

        $type = strtolower($type ?: pathinfo($absolute, PATHINFO_EXTENSION));

        if ($type === 'pdf') {
            return self::fromPdf($absolute);
        }

        if ($type === 'pptx') {
            return self::fromPptx($absolute);
        }

        if ($type === 'ppt') {
            return self::fromPpt($absolute);
        }

        if ($type === 'docx') {
            return self::fromDocx($absolute);
        }

        if ($type === 'doc') {
            $docxTry = self::fromDocx($absolute);
            if ($docxTry !== '') {
                return $docxTry;
            }
            $pptLike = self::fromPpt($absolute);
            if ($pptLike !== '') {
                return $pptLike;
            }
            return self::fromPlainText($absolute);
        }

        if (in_array($type, ['txt', 'text', 'md', 'markdown', 'csv', 'json', 'rtf'], true)) {
            return self::fromPlainText($absolute);
        }

        return self::fromPlainText($absolute);
    }

    private static function fromPdf(string $path): string
    {
        $text = '';

        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($path);
            $text = self::clean($pdf->getText());
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('MaterialTextExtractor PdfParser error: ' . $e->getMessage());
        }

        // If PdfParser extracted very little, attempt stream extraction
        if (mb_strlen(trim($text)) < 50) {
            $fallback = self::fromPdfStreams($path);
            if (mb_strlen(trim($fallback)) > mb_strlen(trim($text))) {
                $text = $fallback;
            }
        }

        return $text;
    }

    private static function fromPdfStreams(string $path): string
    {
        $raw = @file_get_contents($path);
        if (! $raw) {
            return '';
        }

        $text = '';

        if (preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $raw, $streams)) {
            foreach ($streams[1] as $stream) {
                $decompressed = @gzuncompress($stream);
                if ($decompressed === false) {
                    $decompressed = @gzinflate($stream);
                }
                $data = $decompressed !== false ? $decompressed : $stream;

                if (preg_match_all('/\((.*?)\)\s*(?:Tj|\')/s', $data, $m)) {
                    $text .= ' ' . implode(' ', $m[1]);
                }

                if (preg_match_all('/\[(.*?)\]\s*TJ/s', $data, $m)) {
                    foreach ($m[1] as $arr) {
                        if (preg_match_all('/\((.*?)\)/s', $arr, $parts)) {
                            $text .= ' ' . implode('', $parts[1]);
                        }
                    }
                }
            }
        }

        if (mb_strlen(trim($text)) < 50) {
            if (preg_match_all('/\(([\x20-\x7E]{3,})\)/', $raw, $m)) {
                $text .= ' ' . implode(' ', $m[1]);
            }
        }

        return self::clean(strip_tags($text));
    }

    private static function fromPpt(string $path): string
    {
        $raw = @file_get_contents($path);
        if (! $raw) {
            return '';
        }

        $words = [];

        // 1. ASCII strings
        if (preg_match_all('/[\x20-\x7E]{4,}/', $raw, $matches)) {
            foreach ($matches[0] ?? [] as $chunk) {
                if (! preg_match('/^(Font|Default|Slide|PowerPoint|Microsoft|Current|Times|Calibri|Arial|Scheme|Normal)/i', $chunk)) {
                    $words[] = $chunk;
                }
            }
        }

        // 2. UTF-16LE strings common in PowerPoint 97-2003
        if (preg_match_all('/(?:[\x20-\x7E]\x00){4,}/', $raw, $utf16Matches)) {
            foreach ($utf16Matches[0] ?? [] as $chunk) {
                $converted = @mb_convert_encoding($chunk, 'UTF-8', 'UTF-16LE');
                if ($converted && ! preg_match('/^(Font|Default|Slide|PowerPoint|Microsoft|Current|Times|Calibri|Arial|Scheme|Normal)/i', $converted)) {
                    $words[] = $converted;
                }
            }
        }

        return self::clean(implode(' ', array_unique($words)));
    }

    private static function fromPptx(string $path): string
    {
        $zip = new \ZipArchive();

        if ($zip->open($path) !== true) {
            return '';
        }

        $texts = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);

            if (!preg_match('#^ppt/(slides|notesSlides)/[^/]+\.xml$#', (string) $name)) {
                continue;
            }

            $xml = $zip->getFromIndex($i);

            if ($xml === false) {
                continue;
            }

            preg_match_all('#<a:t>(.*?)</a:t>#s', $xml, $matches);

            foreach ($matches[1] as $chunk) {
                $chunk = html_entity_decode(strip_tags($chunk), ENT_QUOTES | ENT_XML1, 'UTF-8');
                $chunk = trim(preg_replace('/\s+/u', ' ', $chunk));

                if ($chunk !== '') {
                    $texts[] = $chunk;
                }
            }
        }

        $zip->close();

        return self::clean(implode("\n", $texts));
    }

    private static function fromDocx(string $path): string
    {
        $zip = new \ZipArchive();

        if ($zip->open($path) !== true) {
            return '';
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($xml === false) {
            return '';
        }

        // Group text by paragraph to preserve question formatting and linebreaks
        if (preg_match_all('#<w:p(?:\s[^>]*)?>(.*?)</w:p>#s', $xml, $pMatches)) {
            $paragraphs = [];
            foreach ($pMatches[1] as $pXml) {
                if (preg_match_all('#<w:t(?:\s[^>]*)?>(.*?)</w:t>#s', $pXml, $tMatches)) {
                    $pText = '';
                    foreach ($tMatches[1] as $chunk) {
                        $pText .= html_entity_decode(strip_tags($chunk), ENT_QUOTES | ENT_XML1, 'UTF-8');
                    }
                    $pText = trim(preg_replace('/\s+/u', ' ', $pText));
                    if ($pText !== '') {
                        $paragraphs[] = $pText;
                    }
                }
            }
            if (! empty($paragraphs)) {
                return self::clean(implode("\n", $paragraphs));
            }
        }

        preg_match_all('#<w:t(?:[^>]*)>(.*?)</w:t>#s', $xml, $matches);
        $texts = [];

        foreach ($matches[1] as $chunk) {
            $chunk = html_entity_decode(strip_tags($chunk), ENT_QUOTES | ENT_XML1, 'UTF-8');
            $chunk = trim(preg_replace('/\s+/u', ' ', $chunk));

            if ($chunk !== '') {
                $texts[] = $chunk;
            }
        }

        return self::clean(implode("\n", $texts));
    }

    private static function fromPlainText(string $path): string
    {
        $content = @file_get_contents($path);
        return $content !== false ? self::clean($content) : '';
    }

    private static function clean(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[ \t]+/u', ' ', $text);
        $text = preg_replace('/\n{3,}/u', "\n\n", $text);

        return trim(mb_substr($text, 0, 40000));
    }
}
