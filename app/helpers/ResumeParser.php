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
        
        // Remove bad UTF-8 which causes JSON encoding to fail
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        
        // Create a clean version for easy regex
        $cleanText = preg_replace('/[^a-zA-Z0-9\s@\.\-\+]/', ' ', $text);

        $data = [
            'full_name' => '',
            'email_address' => '',
            'mobile_number' => '',
            'preferred_work_role_field' => '',
            'skills_set' => '',
            'current_company_city' => '',
            'current_designation' => '',
            'expected_salary_month' => '',
            'experience_type' => 'Fresher'
        ];

        // 1. Extract Email
        if (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $cleanText, $matches)) {
            $data['email_address'] = strtolower($matches[0]);
        }

        // 2. Extract Phone (Indian and International)
        if (preg_match('/(?:(?:\+|0{0,2})91\s*)?[6789]\d{9}/', str_replace(['-', ' '], '', $cleanText), $matches)) {
            $data['mobile_number'] = $matches[0];
        }

        // 3. Guess Experience Type
        $lowerText = strtolower($cleanText);
        if (preg_match('/\b(experience|worked at|employed|years|months|senior)\b/i', $lowerText)) {
            $data['experience_type'] = 'Experienced';
        }

        // 4. Extract Skills (Heuristics)
        $popularSkills = ['php', 'python', 'java', 'c\+\+', 'javascript', 'html', 'css', 'react', 'node\.js', 'sql', 'mysql', 'laravel', 'codeigniter', 'marketing', 'sales', 'seo', 'accounting', 'hr', 'management', 'excel', 'tally', 'aws', 'docker', 'git', 'communication', 'leadership', 'design', 'photoshop', 'illustrator', 'figma'];
        $foundSkills = [];
        foreach ($popularSkills as $skill) {
            if (preg_match('/\b' . str_replace('\+', '\\+', $skill) . '\b/i', $lowerText)) {
                $foundSkills[] = ucwords(str_replace('\\', '', $skill));
            }
        }
        if (!empty($foundSkills)) {
            $data['skills_set'] = implode(', ', array_unique($foundSkills));
        }

        // 5. Extract City (Heuristics)
        $popularCities = ['mumbai', 'delhi', 'bangalore', 'hyderabad', 'ahmedabad', 'chennai', 'kolkata', 'surat', 'pune', 'jaipur', 'lucknow', 'kanpur', 'nagpur', 'indore', 'thane', 'bhopal', 'visakhapatnam', 'patna', 'vadodara', 'ghaziabad', 'ludhiana', 'agra', 'nashik', 'rajkot', 'varanasi', 'gandhinagar', 'noida', 'gurgaon'];
        foreach ($popularCities as $city) {
            if (preg_match('/\b' . $city . '\b/i', $lowerText)) {
                $data['current_company_city'] = ucwords($city);
                break;
            }
        }

        // 6. Guess Name (Very basic heuristic: look for first few words before a newline or strong keyword)
        $lines = explode("\n", trim($text));
        foreach ($lines as $line) {
            $line = trim(preg_replace('/[^a-zA-Z\s]/', '', $line));
            if (strlen($line) > 3 && strlen($line) < 30 && str_word_count($line) >= 1 && str_word_count($line) <= 3) {
                if (strtolower($line) !== 'resume' && strtolower($line) !== 'curriculum vitae' && strtolower($line) !== 'cv') {
                    $data['full_name'] = ucwords(strtolower($line));
                    break;
                }
            }
        }

        return $data;
    }
}
