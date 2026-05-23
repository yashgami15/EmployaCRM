<?php

declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dbFile = BASE_PATH . '/data/app.db';

    if (!is_dir(dirname($dbFile))) {
        mkdir(dirname($dbFile), 0775, true);
    }

    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');

    run_migrations($pdo);

    return $pdo;
}

function run_migrations(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS candidates (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            full_name TEXT NOT NULL,
            email TEXT NOT NULL,
            phone TEXT,
            role TEXT,
            skills TEXT,
            status TEXT NOT NULL DEFAULT "Applied",
            source TEXT NOT NULL DEFAULT "Direct",
            added_on TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )'
    );

    ensure_table_columns($pdo, 'candidates', [
        'email_address' => 'TEXT',
        'mobile_number' => 'TEXT',
        'email_id' => 'TEXT',
        'date_of_birth' => 'TEXT',
        'full_address' => 'TEXT',
        'nearby_landmark' => 'TEXT',
        'native_place' => 'TEXT',
        'caste' => 'TEXT',
        'father_occupation' => 'TEXT',
        'mother_occupation' => 'TEXT',
        'sibling_status' => 'TEXT',
        'marital_status' => 'TEXT',
        'ssc_details' => 'TEXT',
        'hsc_diploma_details' => 'TEXT',
        'graduate_details' => 'TEXT',
        'post_graduate_details' => 'TEXT',
        'experience_type' => 'TEXT',
        'previous_company_city' => 'TEXT',
        'previous_designation' => 'TEXT',
        'previous_roles' => 'TEXT',
        'previous_start_date' => 'TEXT',
        'previous_end_date' => 'TEXT',
        'previous_salary_month' => 'TEXT',
        'current_company_city' => 'TEXT',
        'current_designation' => 'TEXT',
        'current_roles' => 'TEXT',
        'current_start_date' => 'TEXT',
        'current_salary_month' => 'TEXT',
        'reason_for_change' => 'TEXT',
        'skills_set' => 'TEXT',
        'achievements' => 'TEXT',
        'expected_salary_month' => 'TEXT',
        'preferred_location' => 'TEXT',
        'preferred_working_time' => 'TEXT',
        'preferred_work_role_field' => 'TEXT',
        'documents_have' => 'TEXT',
        'additional_notes' => 'TEXT',
        'resume_path' => 'TEXT',
    ]);

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS clients (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            company_name TEXT NOT NULL,
            contact_person TEXT,
            email TEXT,
            phone TEXT,
            industry TEXT,
            open_positions INTEGER NOT NULL DEFAULT 0,
            status TEXT NOT NULL DEFAULT "Active",
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )'
    );

    ensure_table_columns($pdo, 'clients', [
        'job_code' => 'TEXT',
        'reference_code' => 'TEXT',
        'mobile_number' => 'TEXT',
        'mobile_number_2' => 'TEXT',
        'website' => 'TEXT',
        'area' => 'TEXT',
        'category' => 'TEXT',
        'job_role' => 'TEXT',
        'timing' => 'TEXT',
        'gender_preference' => 'TEXT',
        'required_person_count' => 'INTEGER NOT NULL DEFAULT 0',
        'budget' => 'TEXT',
        'expectation' => 'TEXT',
        'remarks' => 'TEXT',
        'follower_name' => 'TEXT',
        'follow_up_1' => 'TEXT',
        'follow_up_2' => 'TEXT',
        'follow_up_3' => 'TEXT'
    ]);

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS interviews (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            candidate_id INTEGER NOT NULL,
            client_id INTEGER,
            round_name TEXT NOT NULL,
            interview_date TEXT NOT NULL,
            interviewer TEXT,
            mode TEXT NOT NULL DEFAULT "Online",
            stage TEXT NOT NULL DEFAULT "Scheduled",
            feedback TEXT,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(candidate_id) REFERENCES candidates(id) ON DELETE CASCADE,
            FOREIGN KEY(client_id) REFERENCES clients(id) ON DELETE SET NULL
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS reminders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            reminder_type TEXT NOT NULL,
            reference_id INTEGER,
            title TEXT NOT NULL,
            reminder_message TEXT,
            remind_at TEXT NOT NULL,
            email_to TEXT,
            phone_to TEXT,
            notify_email INTEGER NOT NULL DEFAULT 1,
            notify_sms INTEGER NOT NULL DEFAULT 1,
            notify_web INTEGER NOT NULL DEFAULT 1,
            email_status TEXT NOT NULL DEFAULT "pending",
            sms_status TEXT NOT NULL DEFAULT "pending",
            web_status TEXT NOT NULL DEFAULT "pending",
            sent_at TEXT,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS notifications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            notification_message TEXT,
            is_read INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS timelines (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            module_type TEXT NOT NULL,
            reference_id INTEGER NOT NULL,
            action_title TEXT NOT NULL,
            action_details TEXT,
            created_by TEXT,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS dropdown_settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            group_name TEXT NOT NULL,
            option_value TEXT NOT NULL,
            sort_order INTEGER DEFAULT 0
        )'
    );

    $settingsCountStmt = $pdo->query('SELECT COUNT(*) as total FROM dropdown_settings');
    if (((int) ($settingsCountStmt->fetch()['total'] ?? 0)) === 0) {
        $initialSettings = [
            'candidate_status' => ['Applied', 'Interview', 'Hired', 'Rejected'],
            'candidate_source' => ['Direct', 'LinkedIn', 'Naukri', 'Referral', 'Indeed', 'Website'],
            'client_status' => ['Active', 'In Progress', 'On Hold', 'Closed'],
            'interview_stage' => ['Scheduled', 'Completed', 'Selected', 'Rejected'],
            'interview_mode' => ['Online', 'Offline', 'Phone'],
            'experience_type' => ['Fresher', 'Experienced'],
            'marital_status' => ['Single', 'Married', 'Other'],
            'documents' => ['Resume', 'Aadhaar', 'PAN', '10th Marksheet', '12th Marksheet', 'Graduation Certificate', 'Experience Letter', 'Offer Letter', 'Passport Photo', 'Other'],
            'client_category' => ['IT Services', 'Manufacturing', 'Retail', 'Healthcare', 'Education', 'Finance', 'Other'],
            'client_timing' => ['Full Time', 'Part Time', 'Shift Based'],
            'client_gender' => ['Any', 'Male', 'Female']
        ];
        $insertSetting = $pdo->prepare('INSERT INTO dropdown_settings (group_name, option_value, sort_order) VALUES (:group_name, :option_value, :sort_order)');
        foreach ($initialSettings as $group => $options) {
            foreach ($options as $i => $opt) {
                $insertSetting->execute(['group_name' => $group, 'option_value' => $opt, 'sort_order' => $i]);
            }
        }
    }

    ensure_table_columns($pdo, 'users', [
        'tenant_name' => 'TEXT',
        'role' => 'TEXT DEFAULT "user"',
        'permissions' => 'TEXT DEFAULT "[]"',
        'visible_password' => 'TEXT'
    ]);

    ensure_table_columns($pdo, 'candidates', ['tenant_name' => 'TEXT']);
    ensure_table_columns($pdo, 'clients', ['tenant_name' => 'TEXT']);
    ensure_table_columns($pdo, 'interviews', ['tenant_name' => 'TEXT']);
    ensure_table_columns($pdo, 'reminders', ['tenant_name' => 'TEXT']);
    ensure_table_columns($pdo, 'notifications', ['tenant_name' => 'TEXT']);
    ensure_table_columns($pdo, 'timelines', ['tenant_name' => 'TEXT']);
    ensure_table_columns($pdo, 'dropdown_settings', ['tenant_name' => 'TEXT']);

    // Migrate legacy data: if tenant_name is NULL, assign it to the primary company 'employahr'
    $tablesToMigrate = ['candidates', 'clients', 'interviews', 'reminders', 'notifications', 'timelines', 'users'];
    foreach ($tablesToMigrate as $table) {
        try {
            $pdo->exec("UPDATE $table SET tenant_name = 'employahr' WHERE tenant_name IS NULL OR tenant_name = ''");
        } catch (Throwable $e) {}
    }

    // Ensure the requested admin user exists and has the correct credentials
    $adminCheck = $pdo->prepare('SELECT id FROM users WHERE email = "admin@employahr.com"');
    $adminCheck->execute();
    if ($adminCheck->fetch()) {
        $updateAdmin = $pdo->prepare('UPDATE users SET role = "admin", tenant_name = "employahr", password = :password WHERE email = "admin@employahr.com"');
        $updateAdmin->execute([
            'password' => password_hash('admin@123', PASSWORD_DEFAULT)
        ]);
    } else {
        $seed = $pdo->prepare('INSERT INTO users (name, email, password, role, tenant_name) VALUES (:name, :email, :password, :role, :tenant)');
        $seed->execute([
            'name' => 'Super Admin',
            'email' => 'admin@employahr.com',
            'password' => password_hash('admin@123', PASSWORD_DEFAULT),
            'role' => 'admin',
            'tenant' => 'employahr'
        ]);
    }
}

function ensure_table_columns(PDO $pdo, string $table, array $columns): void
{
    $stmt = $pdo->query('PRAGMA table_info(' . $table . ')');
    $existing = [];

    foreach ($stmt->fetchAll() as $row) {
        $existing[] = (string) ($row['name'] ?? '');
    }

    foreach ($columns as $column => $definition) {
        if (!in_array($column, $existing, true)) {
            $pdo->exec('ALTER TABLE ' . $table . ' ADD COLUMN ' . $column . ' ' . $definition);
        }
    }
}
