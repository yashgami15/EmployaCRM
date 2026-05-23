<?php
require 'app/bootstrap.php';
$stmt = db()->query("SELECT job_code FROM clients WHERE job_code IS NOT NULL AND job_code != '' LIMIT 10");
$codes = $stmt->fetchAll();
foreach ($codes as $c) {
    echo $c['job_code'] . "\n";
}
