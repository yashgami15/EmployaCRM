<?php
require 'app/bootstrap.php';

$stmt = db()->query("SELECT id, company_name FROM clients WHERE job_code IS NULL OR job_code = '' OR job_code = '-'");
$clients = $stmt->fetchAll();

if (empty($clients)) {
    echo "No clients found with empty job codes.\n";
    exit;
}

$update = db()->prepare("UPDATE clients SET job_code = :code WHERE id = :id");

echo "Updating " . count($clients) . " clients...\n";

foreach ($clients as $client) {
    $code = generate_client_job_code();
    $update->execute(['code' => $code, 'id' => $client['id']]);
    echo "Updated {$client['company_name']} (ID: {$client['id']}) with code: {$code}\n";
}

echo "Done.\n";
