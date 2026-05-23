<?php
/**
 * Downloader script to ensure CSV files are served with correct headers 
 * and Excel compatibility (UTF-8 BOM).
 */

$type = isset($_GET['type']) ? $_GET['type'] : 'candidate';
$baseDir = __DIR__ . '/assets/';
$file = $type === 'client' ? 'sample_clients.csv' : 'sample_candidates.csv';
$filePath = $baseDir . $file;

if (file_exists($filePath)) {
    // Set headers to force download and Excel recognition
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $file . '"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $content = file_get_contents($filePath);
    
    // Add UTF-8 BOM for Excel to handle special characters and columns correctly
    if (strncmp($content, "\xEF\xBB\xBF", 3) !== 0) {
        echo "\xEF\xBB\xBF";
    }
    
    echo $content;
    exit;
}

http_response_code(404);
echo "Sample file not found.";
