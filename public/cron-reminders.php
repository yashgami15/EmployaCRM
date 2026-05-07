<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

$isCli = PHP_SAPI === 'cli';

if (!$isCli) {
    $expectedToken = getenv('CRON_TOKEN') ?: '';
    $givenToken = trim((string) ($_GET['token'] ?? ''));

    if ($expectedToken !== '' && !hash_equals($expectedToken, $givenToken)) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Forbidden';
        exit;
    }
}

if (!$isCli) {
    header('Content-Type: text/plain; charset=utf-8');
}

echo 'OK ' . date('Y-m-d H:i:s');
