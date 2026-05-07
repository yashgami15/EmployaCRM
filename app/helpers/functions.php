<?php

declare(strict_types=1);

function esc(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $location): never
{
    header('Location: ' . $location);
    exit;
}

function is_post(): bool
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}

function current_user(): ?array
{
    static $user = false;

    if (!is_logged_in()) {
        return null;
    }

    if ($user !== false) {
        return $user;
    }

    $stmt = db()->prepare('SELECT id, name, email FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => (int) $_SESSION['user_id']]);
    $result = $stmt->fetch();

    if (!$result) {
        unset($_SESSION['user_id']);
        return null;
    }

    $user = $result;

    return $user;
}

function require_auth(): void
{
    if (!is_logged_in()) {
        set_flash('Please sign in to continue.', 'warning');
        redirect('index.php');
    }
}

function require_guest(): void
{
    if (is_logged_in()) {
        redirect('index.php?action=home');
    }
}

function set_flash(string $message, string $type = 'success'): void
{
    $_SESSION['_flash'] = [
        'message' => $message,
        'type' => $type,
    ];
}

function get_flash(): ?array
{
    if (empty($_SESSION['_flash'])) {
        return null;
    }

    $flash = $_SESSION['_flash'];
    unset($_SESSION['_flash']);

    return $flash;
}

function set_old(array $input): void
{
    $_SESSION['_old'] = $input;
}

function old(string $key, string $default = ''): string
{
    $value = $_SESSION['_old'][$key] ?? $default;

    if (is_array($value)) {
        return implode(', ', array_map('strval', $value));
    }

    return (string) $value;
}

function old_array(string $key): array
{
    $value = $_SESSION['_old'][$key] ?? [];

    if (is_array($value)) {
        return array_values(array_map('strval', $value));
    }

    if (is_string($value) && trim($value) !== '') {
        return array_values(array_filter(array_map('trim', explode(',', $value))));
    }

    return [];
}

function clear_old(): void
{
    unset($_SESSION['_old']);
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . esc(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['_csrf'] ?? '';

    if (!hash_equals(csrf_token(), (string) $token)) {
        http_response_code(419);
        exit('Invalid CSRF token. Please refresh and try again.');
    }
}

function status_options(): array
{
    return ['Applied', 'Interview', 'Hired', 'Rejected'];
}

function source_options(): array
{
    return ['Direct', 'LinkedIn', 'Naukri', 'Referral', 'Indeed', 'Website'];
}

function client_status_options(): array
{
    return ['Active', 'In Progress', 'On Hold', 'Closed'];
}

function interview_stage_options(): array
{
    return ['Scheduled', 'Completed', 'Selected', 'Rejected'];
}

function interview_mode_options(): array
{
    return ['Online', 'Offline', 'Phone'];
}

function experience_type_options(): array
{
    return ['Fresher', 'Experienced'];
}

function marital_status_options(): array
{
    return ['Single', 'Married', 'Other'];
}

function documents_options(): array
{
    return ['Resume', 'Aadhaar', 'PAN', '10th Marksheet', '12th Marksheet', 'Graduation Certificate', 'Experience Letter', 'Offer Letter', 'Passport Photo', 'Other'];
}

function selected_ids_from_request(string $field = 'selected_ids'): array
{
    $raw = $_POST[$field] ?? $_GET[$field] ?? '';

    if (is_array($raw)) {
        $ids = $raw;
    } else {
        $ids = array_filter(array_map('trim', explode(',', (string) $raw)));
    }

    $clean = [];

    foreach ($ids as $id) {
        $intId = (int) $id;

        if ($intId > 0) {
            $clean[] = $intId;
        }
    }

    return array_values(array_unique($clean));
}

function app_logo_path(): string
{
    $logoCandidates = [
        'assets/employa-logo.png',
        'assets/employa-logo.webp',
        'assets/employa-logo.jpg',
        'assets/logo.png',
        'assets/logo.webp',
        'assets/logo.jpg',
        'assets/logo.jpeg',
    ];

    foreach ($logoCandidates as $assetPath) {
        if (file_exists(BASE_PATH . '/public/' . $assetPath)) {
            return $assetPath;
        }
    }

    return 'assets/logo.svg';
}

function app_logo_dark_path(): string
{
    $logoCandidates = [
        'assets/employa-logo-dark.png',
        'assets/employa-logo-dark.webp',
        'assets/employa-logo-dark.jpg',
    ];

    foreach ($logoCandidates as $assetPath) {
        if (file_exists(BASE_PATH . '/public/' . $assetPath)) {
            return $assetPath;
        }
    }

    return app_logo_path();
}

function tokenize_text(string $text): array
{
    $normalized = strtolower(preg_replace('/[^a-z0-9\s]+/i', ' ', $text) ?? '');
    $parts = array_filter(array_map('trim', preg_split('/\s+/', $normalized) ?: []));

    return array_values(array_unique($parts));
}

function match_score(array $candidate, array $client): int
{
    $candidateText = implode(' ', [
        (string) ($candidate['skills_set'] ?? ''),
        (string) ($candidate['preferred_work_role_field'] ?? ''),
        (string) ($candidate['preferred_location'] ?? ''),
        (string) ($candidate['role'] ?? ''),
    ]);

    $clientText = implode(' ', [
        (string) ($client['job_role'] ?? ''),
        (string) ($client['category'] ?? ''),
        (string) ($client['expectation'] ?? ''),
        (string) ($client['area'] ?? ''),
    ]);

    $candidateTokens = tokenize_text($candidateText);
    $clientTokens = tokenize_text($clientText);

    if (empty($candidateTokens) || empty($clientTokens)) {
        return 0;
    }

    $overlap = array_intersect($candidateTokens, $clientTokens);

    return (int) round((count($overlap) / max(1, count($candidateTokens))) * 100);
}

function normalize_datetime_input(string $value): string
{
    $value = trim($value);

    if ($value === '') {
        return '';
    }

    $formats = ['Y-m-d H:i', 'Y-m-d\TH:i', 'Y-m-d H:i:s', 'Y-m-d\TH:i:s'];

    foreach ($formats as $format) {
        $dt = DateTime::createFromFormat($format, $value);

        if ($dt instanceof DateTime) {
            return $dt->format('Y-m-d H:i:00');
        }
    }

    return '';
}

function add_web_notification(string $title, string $message): void
{
    $stmt = db()->prepare('INSERT INTO notifications (title, notification_message, is_read) VALUES (:title, :notification_message, 0)');
    $stmt->execute([
        'title' => $title,
        'notification_message' => $message,
    ]);
}

function unread_notification_count(): int
{
    $row = db()->query('SELECT COUNT(*) AS total FROM notifications WHERE is_read = 0')->fetch();
    return (int) ($row['total'] ?? 0);
}

function recent_notifications(int $limit = 8): array
{
    $stmt = db()->prepare('SELECT id, title, notification_message, is_read, created_at FROM notifications ORDER BY id DESC LIMIT :limit');
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function mark_notification_read(int $id): void
{
    $stmt = db()->prepare('UPDATE notifications SET is_read = 1 WHERE id = :id');
    $stmt->execute(['id' => $id]);
}

function send_email_notification(string $to, string $subject, string $body): bool
{
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type:text/plain;charset=UTF-8\r\n";
    $headers .= 'From: noreply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "\r\n";

    return mail($to, $subject, $body, $headers);
}

function send_sms_notification(string $phone, string $message): bool
{
    $phone = trim($phone);

    if ($phone === '') {
        return false;
    }

    $webhook = getenv('SMS_WEBHOOK_URL') ?: '';

    if ($webhook === '') {
        return false;
    }

    if (!function_exists('curl_init')) {
        return false;
    }

    $payload = json_encode([
        'phone' => $phone,
        'message' => $message,
    ]);

    if ($payload === false) {
        return false;
    }

    $ch = curl_init($webhook);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($payload),
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $error === '' && $response !== false && $status >= 200 && $status < 300;
}

function process_due_reminders(): void
{
    $stmt = db()->prepare(
        'SELECT id, title, reminder_message, remind_at, email_to, phone_to, notify_email, notify_sms, notify_web
                , email_status, sms_status, web_status
         FROM reminders
         WHERE remind_at <= :now
           AND (email_status = \'pending\' OR sms_status = \'pending\' OR web_status = \'pending\')
         ORDER BY remind_at ASC
         LIMIT 20'
    );
    $stmt->execute(['now' => date('Y-m-d H:i:00')]);
    $reminders = $stmt->fetchAll();

    foreach ($reminders as $reminder) {
        $id = (int) $reminder['id'];
        $title = (string) $reminder['title'];
        $message = (string) ($reminder['reminder_message'] ?: 'Reminder triggered.');

        $emailStatus = (string) ($reminder['email_status'] ?? 'pending');
        $smsStatus = (string) ($reminder['sms_status'] ?? 'pending');
        $webStatus = (string) ($reminder['web_status'] ?? 'pending');

        if ((int) $reminder['notify_email'] !== 1 && $emailStatus === 'pending') {
            $emailStatus = 'skipped';
        }

        if ((int) $reminder['notify_email'] === 1 && $emailStatus === 'pending') {
            $emailStatus = send_email_notification((string) $reminder['email_to'], $title, $message) ? 'sent' : 'failed';
        }

        if ((int) $reminder['notify_sms'] !== 1 && $smsStatus === 'pending') {
            $smsStatus = 'skipped';
        }

        if ((int) $reminder['notify_sms'] === 1 && $smsStatus === 'pending') {
            $smsStatus = send_sms_notification((string) $reminder['phone_to'], $title . ': ' . $message) ? 'sent' : 'failed';
        }

        if ((int) $reminder['notify_web'] !== 1 && $webStatus === 'pending') {
            $webStatus = 'skipped';
        }

        if ((int) $reminder['notify_web'] === 1 && $webStatus === 'pending') {
            add_web_notification($title, $message);
            $webStatus = 'sent';
        }

        $update = db()->prepare(
            'UPDATE reminders
             SET email_status = :email_status,
                 sms_status = :sms_status,
                 web_status = :web_status,
                 sent_at = :sent_at
             WHERE id = :id'
        );

        $update->execute([
            'email_status' => $emailStatus,
            'sms_status' => $smsStatus,
            'web_status' => $webStatus,
            'sent_at' => date('Y-m-d H:i:s'),
            'id' => $id,
        ]);
    }
}
