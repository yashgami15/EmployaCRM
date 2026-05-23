<?php
require 'config/database.php';
$stmt = db()->query('SELECT DISTINCT action_title FROM timelines');
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach($results as $row) {
    echo $row['action_title'] . "\n";
}
