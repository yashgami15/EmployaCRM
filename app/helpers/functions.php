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

function redirect_with_filters(string $baseAction): never
{
    $params = $_GET;
    $params['action'] = $baseAction;
    redirect('index.php?' . http_build_query($params));
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

    $stmt = db()->prepare('SELECT id, name, email, permissions FROM users WHERE id = :id LIMIT 1');
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

function require_permission(string $module): void
{
    require_auth();
    
    if ($_SESSION['role'] === 'admin') {
        return; // Admins have full access
    }

    $user = current_user();
    if (!$user) {
        redirect('index.php?action=logout');
    }

    $permissions = json_decode((string) ($user['permissions'] ?? '[]'), true) ?: [];
    
    if (!in_array($module, $permissions, true)) {
        set_flash("You do not have permission to access the {$module} module.", 'danger');
        redirect('index.php?action=home');
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

function get_dynamic_options(string $groupName, array $defaultOptions = []): array
{
    static $cache = null;
    if ($cache === null) {
        try {
            $rows = db()->query('SELECT group_name, option_value FROM dropdown_settings ORDER BY sort_order ASC, id ASC')->fetchAll();
            $cache = [];
            foreach ($rows as $row) {
                $cache[$row['group_name']][] = $row['option_value'];
            }
        } catch (Throwable $e) {
            $cache = [];
        }
    }
    return !empty($cache[$groupName]) ? $cache[$groupName] : $defaultOptions;
}

function status_options(): array
{
    return get_dynamic_options('candidate_status', ['Applied', 'Interview', 'Hired', 'Rejected']);
}

function source_options(): array
{
    return get_dynamic_options('candidate_source', ['Direct', 'LinkedIn', 'Naukri', 'Referral', 'Indeed', 'Website']);
}

function client_status_options(): array
{
    return get_dynamic_options('client_status', ['Active', 'In Progress', 'On Hold', 'Closed']);
}

function interview_stage_options(): array
{
    return get_dynamic_options('interview_stage', ['Scheduled', 'Completed', 'Selected', 'Rejected']);
}

function interview_mode_options(): array
{
    return get_dynamic_options('interview_mode', ['Online', 'Offline', 'Phone']);
}

function experience_type_options(): array
{
    return get_dynamic_options('experience_type', ['Fresher', 'Experienced']);
}

function marital_status_options(): array
{
    return get_dynamic_options('marital_status', ['Single', 'Married', 'Other']);
}

function documents_options(): array
{
    return get_dynamic_options('documents', ['Resume', 'Aadhaar', 'PAN', '10th Marksheet', '12th Marksheet', 'Graduation Certificate', 'Experience Letter', 'Offer Letter', 'Passport Photo', 'Other']);
}

function client_category_options(): array
{
    return get_dynamic_options('client_category', ['IT Services', 'Manufacturing', 'Retail', 'Healthcare', 'Education', 'Finance', 'Other']);
}

function client_timing_options(): array
{
    return get_dynamic_options('client_timing', ['Full Time', 'Part Time', 'Shift Based']);
}

function client_gender_options(): array
{
    return get_dynamic_options('client_gender', ['Any', 'Male', 'Female']);
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

/**
 * Matches a value against an array of options case-insensitively.
 * Returns the matching option if found, otherwise returns the original value or a default.
 */
function match_option_case_insensitive(string $value, array $options, string $default = ''): string
{
    $value = trim($value);
    if ($value === '') {
        return $default;
    }

    foreach ($options as $option) {
        if (strcasecmp($value, (string) $option) === 0) {
            return (string) $option;
        }
    }

    return $value !== '' ? $value : $default;
}

function generate_client_job_code(): string
{
    static $nextId = null;
    if ($nextId === null) {
        try {
            $row = db()->query('SELECT MAX(id) as max_id FROM clients')->fetch();
            $nextId = (int)($row['max_id'] ?? 0) + 1;
        } catch (Throwable $e) {
            $nextId = 1;
        }
    } else {
        $nextId++;
    }
    return 'JOB-' . str_pad((string)$nextId, 4, '0', STR_PAD_LEFT);
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

    return 'assets/logo.png';
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

function match_score_detailed(array $candidate, array $client): array
{
    $totalScore = 0;
    $maxPossibleScore = 0;
    $distanceKm = null;

    // --- 1. Role Match (30% Weight) ---
    $roleWeight = 30;
    $maxPossibleScore += $roleWeight;
    $jobRole = strtolower(trim((string)($client['job_role'] ?? '')));
    $candidatePrefRole = strtolower(trim((string)($candidate['preferred_work_role_field'] ?? '')));
    $candidateCurrRole = strtolower(trim((string)($candidate['role'] ?? '')));
    
    $roleMatched = false;
    if ($jobRole !== '') {
        if ($candidatePrefRole !== '' && (str_contains($candidatePrefRole, $jobRole) || str_contains($jobRole, $candidatePrefRole))) {
            $totalScore += $roleWeight;
            $roleMatched = true;
        } elseif ($candidateCurrRole !== '' && (str_contains($candidateCurrRole, $jobRole) || str_contains($jobRole, $candidateCurrRole))) {
            $totalScore += $roleWeight * 0.8;
            $roleMatched = true;
        }
    } else {
        // Missing requirement data
        $totalScore += $roleWeight * 0.2; 
    }

    // --- 2. Skills Match (25% Weight) ---
    $skillWeight = 25;
    $maxPossibleScore += $skillWeight;
    $skillsText = (string)($candidate['skills_set'] ?? '');
    $expectationsText = (string)($client['expectation'] ?? '') . ' ' . (string)($client['category'] ?? '');
    
    $candidateSkills = tokenize_text($skillsText);
    $clientExpectations = tokenize_text($expectationsText);
    
    if (!empty($clientExpectations)) {
        if (!empty($candidateSkills)) {
            $overlap = array_intersect($candidateSkills, $clientExpectations);
            $skillScore = (count($overlap) / count($clientExpectations)) * $skillWeight;
            $totalScore += min($skillWeight, $skillScore);
        } else {
            // Candidate has no skills listed
            $totalScore += 0;
        }
    } else {
        if ($roleMatched) $totalScore += $skillWeight * 0.5;
    }

    // --- 3. Experience Match (15% Weight) ---
    $expWeight = 15;
    $maxPossibleScore += $expWeight;
    $candidateExpType = strtolower(trim((string)($candidate['experience_type'] ?? 'fresher')));
    $clientText = strtolower((string)($client['expectation'] ?? '') . ' ' . (string)($client['remarks'] ?? ''));
    
    $wantsExperience = str_contains($clientText, 'experience') || str_contains($clientText, 'exp') || str_contains($clientText, 'experienced');
    $wantsFresher = str_contains($clientText, 'fresher');

    if ($wantsExperience && !$wantsFresher) {
        if ($candidateExpType === 'experienced') $totalScore += $expWeight;
        else $totalScore -= 5; // Penalty for fresher when exp is wanted
    } elseif ($wantsFresher && !$wantsExperience) {
        if ($candidateExpType === 'fresher') $totalScore += $expWeight;
        else $totalScore += $expWeight * 0.5; // Experienced candidate for fresher role (overqualified)
    } else {
        // Requirement not clear, assume match if role matches
        if ($roleMatched) $totalScore += $expWeight * 0.7;
    }

    // --- 4. Salary Match (20% Weight) ---
    $salaryWeight = 20;
    $maxPossibleScore += $salaryWeight;
    
    $candidateExpected = (int) preg_replace('/[^0-9]/', '', (string)($candidate['expected_salary_month'] ?? '0'));
    $candidateCurrent = (int) preg_replace('/[^0-9]/', '', (string)($candidate['current_salary_month'] ?? '0'));
    $clientBudget = (int) preg_replace('/[^0-9]/', '', (string)($client['budget'] ?? '0'));

    if ($clientBudget > 0) {
        if ($candidateExpected > 0) {
            if ($candidateExpected <= $clientBudget) {
                $totalScore += $salaryWeight;
            } else {
                return ['score' => 0, 'distance_km' => null]; // Strict filter: drop candidate immediately if over budget
            }
        } elseif ($candidateCurrent > 0) {
            // Use current salary as fallback
            if ($candidateCurrent <= $clientBudget) {
                $totalScore += $salaryWeight * 0.6;
            } else {
                return ['score' => 0, 'distance_km' => null];
            }
        } else {
            // Missing data penalty: If candidate has no salary info, reduce score
            $totalScore += 0; 
        }
    } else {
        $totalScore += $salaryWeight * 0.5;
    }

    // --- 5. Location Match (10% Weight) ---
    $locationWeight = 10;
    $maxPossibleScore += $locationWeight;
    $prefLocation = strtolower(trim((string)($candidate['preferred_location'] ?? '')));
    $jobArea = strtolower(trim((string)($client['area'] ?? '')));
    
    if ($jobArea !== '' && $prefLocation !== '') {
        $candCoords = geocode($prefLocation);
        $clientCoords = geocode($jobArea);
        
        if ($candCoords && $clientCoords) {
            $distanceKm = calculate_distance($candCoords['lat'], $candCoords['lng'], $clientCoords['lat'], $clientCoords['lng']);
            if ($distanceKm > 30) {
                return ['score' => 0, 'distance_km' => $distanceKm]; // Reject if too far (> 30km)
            } elseif ($distanceKm <= 10) {
                $totalScore += $locationWeight; // Within 10km
            } elseif ($distanceKm <= 20) {
                $totalScore += $locationWeight * 0.7; // Within 20km
            } else {
                $totalScore += $locationWeight * 0.3; // Within 30km but far
            }
        } else {
            // Fallback to text matching
            if (str_contains($prefLocation, $jobArea) || str_contains($jobArea, $prefLocation)) {
                $totalScore += $locationWeight;
            } else {
                // Fuzzier match: check if any word from jobArea matches prefLocation
                $areaWords = array_filter(explode(' ', $jobArea), fn($w) => strlen($w) > 3);
                foreach ($areaWords as $word) {
                    if (str_contains($prefLocation, $word)) {
                        $totalScore += $locationWeight * 0.7;
                        break;
                    }
                }
            }
        }
    } else {
        if ($roleMatched) $totalScore += $locationWeight * 0.5;
    }

    // --- Final Calculation & Hard Limits ---
    $finalScore = (int) round(($totalScore / max(1, $maxPossibleScore)) * 100);
    if ($jobRole !== '' && !$roleMatched) {
        $roleTokens = tokenize_text($jobRole);
        $skillOverlap = array_intersect($candidateSkills, $roleTokens);
        if (empty($skillOverlap)) {
            return ['score' => 0, 'distance_km' => null]; // Role specified and doesn't match at all
        }
        $finalScore = min($finalScore, 10);
    }
    
    // 3. Penalty for Missing Critical Data (Riddhi's Case)
    // If multiple key fields are missing, cap the score
    $missingCount = 0;
    if ($candidatePrefRole === '' && $candidateCurrRole === '') $missingCount++;
    if ($skillsText === '') $missingCount++;
    if ($candidateExpected === 0) $missingCount++;
    
    if ($missingCount >= 2) {
        $finalScore = (int)($finalScore * 0.6); // Reduce score for low-information profiles
    }

    return ['score' => max(0, $finalScore), 'distance_km' => $distanceKm];
}

function match_score(array $candidate, array $client): int
{
    $result = match_score_detailed($candidate, $client);
    return $result['score'];
}

function geocode(string $location): ?array {
    $location = strtolower(trim($location));
    if (!$location || strlen($location) < 3) return null;
    
    $cacheFile = BASE_PATH . '/data/location_cache.json';
    $cache = [];
    if (file_exists($cacheFile)) {
        $cache = json_decode(file_get_contents($cacheFile), true) ?: [];
    }
    
    if (array_key_exists($location, $cache)) {
        return $cache[$location];
    }
    
    // Call Nominatim
    $url = "https://nominatim.openstreetmap.org/search?q=" . urlencode($location) . "&format=json&limit=1";
    $opts = [
        "http" => [
            "method" => "GET",
            "header" => "User-Agent: EmployaHR-App/1.0\r\n"
        ]
    ];
    $context = stream_context_create($opts);
    $response = @file_get_contents($url, false, $context);
    
    $res = null;
    if ($response) {
        $data = json_decode($response, true);
        if (!empty($data)) {
            $res = ['lat' => (float)$data[0]['lat'], 'lng' => (float)$data[0]['lon']];
        }
    }
    
    $cache[$location] = $res;
    file_put_contents($cacheFile, json_encode($cache, JSON_PRETTY_PRINT));
    return $res;
}

function calculate_distance(float $lat1, float $lon1, float $lat2, float $lon2): float {
    $earthRadius = 6371; // km
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $earthRadius * $c;
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
function log_activity(string $module, int $id, string $title, string $details = ''): void
{
    try {
        $user = current_user();
        $userName = $user ? $user['name'] : 'System';

        $stmt = db()->prepare(
            'INSERT INTO timelines (module_type, reference_id, action_title, action_details, created_by, created_at)
             VALUES (:module, :id, :title, :details, :userName, :at)'
        );

        $stmt->execute([
            'module' => $module,
            'id' => $id,
            'title' => $title,
            'details' => $details,
            'userName' => $userName,
            'at' => date('Y-m-d H:i:s'),
        ]);
    } catch (Exception $e) {
        // Silently ignore logging errors to avoid breaking the main flow
    }
}
