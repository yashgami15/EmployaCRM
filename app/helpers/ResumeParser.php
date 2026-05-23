<?php
declare(strict_types=1);

class ResumeParser
{
    public static function extractTextFallback(string $filePath, string $mimeType): string
    {
        $text = '';
        if ($mimeType === 'application/pdf') {
            $text = self::extractFromPdf($filePath);
        } else {
            // DOC/DOCX/TXT fallback
            $text = file_get_contents($filePath);
            if (class_exists('ZipArchive')) {
                $zip = new ZipArchive();
                if ($zip->open($filePath) === true) {
                    if (($index = $zip->locateName('word/document.xml')) !== false) {
                        $data = $zip->getFromIndex($index);
                        $text = strip_tags(str_replace(['<w:p>', '</w:p>'], ["\n", "\n"], $data));
                    }
                    $zip->close();
                }
            }
        }
        return $text;
    }

    private static function extractFromPdf(string $filename): string
    {
        // Attempt to use pdftotext if available on linux
        if (function_exists('shell_exec') && !stripos(PHP_OS, 'WIN')) {
            $out = @shell_exec('pdftotext ' . escapeshellarg($filename) . ' - 2>/dev/null');
            if ($out) return $out;
        }

        // Basic PDF text extraction fallback (Decompress streams)
        $content = file_get_contents($filename);
        $text = '';
        
        // Find all compressed streams
        if (preg_match_all('/stream(.*?)endstream/is', $content, $matches)) {
            foreach ($matches[1] as $stream) {
                $stream = ltrim($stream, "\r\n");
                $decoded = @gzuncompress($stream);
                if ($decoded) {
                    // Extract strings inside parentheses like (Hello World)
                    if (preg_match_all('/\((.*?)\)/', $decoded, $strMatches)) {
                        foreach ($strMatches[1] as $str) {
                            $text .= ' ' . $str;
                        }
                    }
                }
            }
        }
        
        return $text;
    }

    public static function parseLocally(string $filePath, string $mimeType): array
    {
        $text = self::extractTextFallback($filePath, $mimeType);
        $text = preg_replace('/[^a-zA-Z0-9\s@\.\-\+]/', ' ', $text); // Clean up gibberish

        $data = [
            'full_name' => '',
            'email_address' => '',
            'mobile_number' => '',
            'preferred_work_role_field' => '',
            'skills_set' => '',
            'current_company_city' => '',
            'current_designation' => '',
            'expected_salary_month' => '',
            'experience_type' => ''
        ];

        // 1. Extract Email
        if (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $text, $matches)) {
            $data['email_address'] = $matches[0];
        }

        // 2. Extract Phone (Indian and International)
        if (preg_match('/(?:(?:\+|0{0,2})91\s*)?[6789]\d{9}/', str_replace(['-', ' '], '', $text), $matches)) {
            $data['mobile_number'] = $matches[0];
        }

        // Add a notice about local fallback
        $data['skills_set'] = 'Extracted locally. No AI used. Email/Phone only.';

        return $data;
    }
}
