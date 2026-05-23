<?php
require __DIR__ . '/config/database.php';

$pdo = db();

$pdo->sqliteCreateFunction('current_company', function() {
    return 'Super Admin';
});

// Let's test if we can do this
$pdo->exec('CREATE TABLE test_table (id INTEGER PRIMARY KEY, name TEXT, company_name TEXT)');
$pdo->exec('CREATE VIEW test_view AS SELECT * FROM test_table WHERE company_name = current_company() OR current_company() = "Super Admin"');

$pdo->exec('CREATE TRIGGER test_insert INSTEAD OF INSERT ON test_view
BEGIN
    INSERT INTO test_table (id, name, company_name) VALUES (NEW.id, NEW.name, current_company());
END;');

$pdo->exec('CREATE TRIGGER test_update INSTEAD OF UPDATE ON test_view
BEGIN
    UPDATE test_table SET name = NEW.name, company_name = NEW.company_name WHERE id = NEW.id;
END;');

$pdo->exec('INSERT INTO test_view (name) VALUES ("Hello")');

$stmt = $pdo->query('SELECT * FROM test_view');
var_dump($stmt->fetchAll());
