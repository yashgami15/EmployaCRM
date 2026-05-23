<?php

declare(strict_types=1);

class InterviewController
{
    public static function index(): void
    {
        require_permission('interviews');

        $user = current_user();
        $flash = get_flash();

        $stage = trim((string) ($_GET['stage'] ?? ''));
        $date = trim((string) ($_GET['date'] ?? ''));

        $where = [];
        $params = [];

        if (($_SESSION['role'] ?? 'user') !== 'admin') {
            $where[] = 'i.tenant_name = :tenant_name';
            $params['tenant_name'] = $_SESSION['tenant_name'] ?? '';
        }

        if ($stage !== '' && in_array($stage, interview_stage_options(), true)) {
            $where[] = 'i.stage = :stage';
            $params['stage'] = $stage;
        }

        if ($date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $where[] = 'i.interview_date = :interview_date';
            $params['interview_date'] = $date;
        }

        $sql = 'SELECT i.id, i.round_name, i.interview_date, i.interviewer, i.mode, i.stage, i.feedback,
                       c.id AS candidate_id, c.full_name AS candidate_name,
                       cl.id AS client_id, cl.company_name AS client_name
                FROM interviews i
                LEFT JOIN candidates c ON c.id = i.candidate_id
                LEFT JOIN clients cl ON cl.id = i.client_id';

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY DATE(i.interview_date) ASC, i.id DESC';

        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $interviews = $stmt->fetchAll();

        $tenantFilter = (($_SESSION['role'] ?? 'user') !== 'admin') ? "WHERE tenant_name = '" . ($_SESSION['tenant_name'] ?? '') . "'" : "";
        $candidates = db()->query("SELECT id, full_name FROM candidates $tenantFilter ORDER BY full_name ASC")->fetchAll();
        $clients = db()->query("SELECT id, company_name FROM clients $tenantFilter ORDER BY company_name ASC")->fetchAll();

        $stageOptions = interview_stage_options();
        $modeOptions = interview_mode_options();

        $stats = [
            'total' => (int) (db()->query("SELECT COUNT(*) AS total FROM interviews $tenantFilter")->fetch()['total'] ?? 0),
            'scheduled' => 0,
            'completed' => 0,
            'selected' => 0,
            'rejected' => 0,
        ];

        $rows = db()->query("SELECT stage, COUNT(*) AS total FROM interviews $tenantFilter GROUP BY stage")->fetchAll();

        foreach ($rows as $row) {
            $key = strtolower(trim((string) ($row['stage'] ?? '')));

            if (array_key_exists($key, $stats)) {
                $stats[$key] = (int) ($row['total'] ?? 0);
            }
        }

        require BASE_PATH . '/app/views/dashboard/interviews.php';
    }

    public static function addInterview(): void
    {
        require_auth();
        verify_csrf();

        if (!is_post()) {
            redirect_with_filters('interviews');
        }

        $candidateId = (int) ($_POST['candidate_id'] ?? 0);
        $clientId = (int) ($_POST['client_id'] ?? 0);
        $roundName = trim((string) ($_POST['round_name'] ?? ''));
        $interviewDate = trim((string) ($_POST['interview_date'] ?? ''));
        $interviewer = trim((string) ($_POST['interviewer'] ?? ''));
        $mode = trim((string) ($_POST['mode'] ?? 'Online'));
        $stage = trim((string) ($_POST['stage'] ?? 'Scheduled'));
        $feedback = trim((string) ($_POST['feedback'] ?? ''));

        set_old($_POST);

        if ($candidateId <= 0 || $roundName === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $interviewDate)) {
            set_flash('Candidate, round name and valid interview date are required.', 'danger');
            redirect_with_filters('interviews');
        }

        if (!in_array($mode, interview_mode_options(), true)) {
            $mode = 'Online';
        }

        if (!in_array($stage, interview_stage_options(), true)) {
            $stage = 'Scheduled';
        }

        // Duplicate Check
        $checkStmt = db()->prepare('SELECT id FROM interviews WHERE candidate_id = :cid AND IFNULL(client_id, 0) = :clid AND round_name = :round AND interview_date = :date LIMIT 1');
        $checkStmt->execute([
            'cid' => $candidateId,
            'clid' => $clientId > 0 ? $clientId : 0,
            'round' => $roundName,
            'date' => $interviewDate
        ]);
        if ($checkStmt->fetch()) {
            set_flash('This interview round for the candidate already exists on this date.', 'danger');
            redirect_with_filters('interviews');
        }

        $stmt = db()->prepare(
            'INSERT INTO interviews (tenant_name, candidate_id, client_id, round_name, interview_date, interviewer, mode, stage, feedback)
             VALUES (:tenant_name, :candidate_id, :client_id, :round_name, :interview_date, :interviewer, :mode, :stage, :feedback)'
        );

        $stmt->execute([
            'tenant_name' => $_SESSION['tenant_name'] ?? '',
            'candidate_id' => $candidateId,
            'client_id' => $clientId > 0 ? $clientId : null,
            'round_name' => $roundName,
            'interview_date' => $interviewDate,
            'interviewer' => $interviewer,
            'mode' => $mode,
            'stage' => $stage,
            'feedback' => $feedback,
        ]);

        $interviewId = (int) db()->lastInsertId();

        // Create reminders to share interview details
        $candQuery = db()->prepare('SELECT full_name, email_id, email, mobile_number, phone FROM candidates WHERE id = :id');
        $candQuery->execute(['id' => $candidateId]);
        $cand = $candQuery->fetch();

        $clientQuery = db()->prepare('SELECT company_name, email, mobile_number, phone FROM clients WHERE id = :id');
        $clientQuery->execute(['id' => $clientId]);
        $client = $clientQuery->fetch();

        if ($cand) {
            $cEmail = $cand['email_id'] ?: $cand['email'];
            $cPhone = $cand['mobile_number'] ?: $cand['phone'];
            $cName = $cand['full_name'];
            $message = "Interview scheduled for $cName. Round: $roundName, Date: $interviewDate, Mode: $mode.";

            $insertRem = db()->prepare('INSERT INTO reminders (tenant_name, reminder_type, reference_id, title, reminder_message, remind_at, email_to, phone_to, notify_email, notify_sms, notify_web) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, 1, 1)');
            
            // For candidate
            $tenant = $_SESSION['tenant_name'] ?? '';
            $insertRem->execute([$tenant, 'interview', $interviewId, "Interview Details - $cName", $message, $interviewDate . ' 09:00:00', $cEmail, $cPhone]);

            // For client
            if ($client) {
                $clEmail = $client['email'];
                $clPhone = $client['mobile_number'] ?: $client['phone'];
                $clName = $client['company_name'];
                $insertRem->execute([$tenant, 'interview', $interviewId, "Interview Details for $cName at $clName", $message, $interviewDate . ' 09:00:00', $clEmail, $clPhone]);
                
                log_activity('candidate', $candidateId, 'Interview Scheduled', "Round: $roundName at $clName on $interviewDate");
                log_activity('client', (int)$clientId, 'Interview Scheduled', "Candidate: $cName for $roundName on $interviewDate");
            } else {
                log_activity('candidate', $candidateId, 'Interview Scheduled', "Round: $roundName on $interviewDate");
            }
        }

        if ($interviewId > 0) {
            self::syncCandidateStatus($candidateId);
        }

        clear_old();
        set_flash('Interview added successfully.', 'success');
        redirect_with_filters('interviews');
    }

    public static function updateStage(): void
    {
        require_auth();
        verify_csrf();

        $interviewId = (int) ($_POST['interview_id'] ?? 0);
        $stage = trim((string) ($_POST['stage'] ?? ''));

        if ($interviewId <= 0 || !in_array($stage, interview_stage_options(), true)) {
            set_flash('Invalid interview stage update request.', 'danger');
            redirect_with_filters('interviews');
        }

        $stmt = db()->prepare('UPDATE interviews SET stage = :stage WHERE id = :id');
        $stmt->execute([
            'stage' => $stage,
            'id' => $interviewId,
        ]);

        $link = db()->prepare('SELECT candidate_id FROM interviews WHERE id = :id LIMIT 1');
        $link->execute(['id' => $interviewId]);
        $row = $link->fetch();

        if ($row && !empty($row['candidate_id'])) {
            self::syncCandidateStatus((int) $row['candidate_id']);
        }

        set_flash('Interview stage updated.', 'success');

        // Log the activity
        $info = db()->prepare('SELECT i.candidate_id, i.client_id, i.round_name, cl.company_name, c.full_name 
                               FROM interviews i 
                               LEFT JOIN clients cl ON cl.id = i.client_id 
                               LEFT JOIN candidates c ON c.id = i.candidate_id
                               WHERE i.id = :id');
        $info->execute(['id' => $interviewId]);
        $details = $info->fetch();
        if ($details) {
            $msg = "Interview round '{$details['round_name']}' updated to: $stage";
            log_activity('candidate', (int)$details['candidate_id'], 'Interview Stage Updated', $msg);
            if ($details['client_id']) {
                log_activity('client', (int)$details['client_id'], 'Interview Stage Updated', "Candidate {$details['full_name']}: $msg");
            }
        }

        redirect_with_filters('interviews');
    }

    public static function updateInterview(): void
    {
        require_auth();
        verify_csrf();

        if (!is_post()) {
            redirect_with_filters('interviews');
        }

        $interviewId = (int) ($_POST['interview_id'] ?? 0);
        $candidateId = (int) ($_POST['candidate_id'] ?? 0);
        $clientId = (int) ($_POST['client_id'] ?? 0);
        $interviewDate = trim((string) ($_POST['interview_date'] ?? ''));
        $mode = trim((string) ($_POST['mode'] ?? 'Online'));
        $round = trim((string) ($_POST['round_name'] ?? '1st Round'));
        $interviewerName = trim((string) ($_POST['interviewer'] ?? ''));
        $notes = trim((string) ($_POST['feedback'] ?? ''));
        $stage = trim((string) ($_POST['stage'] ?? 'Scheduled'));

        if ($interviewId <= 0 || $candidateId <= 0) {
            set_flash('Valid candidate is required.', 'danger');
            redirect_with_filters('interviews');
        }

        $stmt = db()->prepare(
            'UPDATE interviews SET 
                candidate_id = :candidate_id, client_id = :client_id, interview_date = :interview_date, 
                mode = :mode, round_name = :round_name, interviewer = :interviewer, 
                feedback = :feedback, stage = :stage
            WHERE id = :id'
        );

        $stmt->execute([
            'id' => $interviewId,
            'candidate_id' => $candidateId,
            'client_id' => $clientId ?: null,
            'interview_date' => $interviewDate ?: null,
            'mode' => $mode,
            'round_name' => $round,
            'interviewer' => $interviewerName,
            'feedback' => $notes,
            'stage' => $stage,
        ]);

        self::syncCandidateStatus($candidateId);

        set_flash('Interview updated successfully.', 'success');
        redirect_with_filters('interviews');
    }

    public static function deleteSelected(): void
    {
        require_auth();
        verify_csrf();

        $ids = $_POST['selected_ids'] ?? [];
        if (!is_array($ids)) {
            $ids = explode(',', (string) $ids);
        }

        $ids = array_filter(array_map('intval', $ids), fn($id) => $id > 0);

        if (empty($ids)) {
            set_flash('No interviews selected for deletion.', 'danger');
            redirect_with_filters('interviews');
        }

        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $stmt = db()->prepare("DELETE FROM interviews WHERE id IN ($placeholders)");
        $stmt->execute(array_values($ids));

        set_flash('Selected interviews deleted successfully.', 'success');
        redirect_with_filters('interviews');
    }

    public static function exportSelected(): void
    {
        require_auth();

        $ids = selected_ids_from_request();

        if (empty($ids)) {
            set_flash('Please select interviews before exporting selected.', 'warning');
            redirect_with_filters('interviews');
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = 'SELECT c.full_name AS candidate_name, cl.company_name AS client_name, 
                       i.round_name, i.interview_date, i.interviewer, i.mode, i.stage, i.feedback
                FROM interviews i
                LEFT JOIN candidates c ON c.id = i.candidate_id
                LEFT JOIN clients cl ON cl.id = i.client_id
                WHERE i.id IN (' . $placeholders . ')
                ORDER BY DATE(i.interview_date) ASC, i.id DESC';

        $stmt = db()->prepare($sql);
        $stmt->execute($ids);

        self::streamCsv('interviews_selected_' . date('Ymd_His') . '.csv', $stmt->fetchAll());
    }

    public static function exportFiltered(): void
    {
        require_auth();

        $stage = trim((string) ($_GET['stage'] ?? ''));
        $date = trim((string) ($_GET['date'] ?? ''));

        $where = [];
        $params = [];

        if (($_SESSION['role'] ?? 'user') !== 'admin') {
            $where[] = 'i.tenant_name = :tenant_name';
            $params['tenant_name'] = $_SESSION['tenant_name'] ?? '';
        }

        if ($stage !== '' && in_array($stage, interview_stage_options(), true)) {
            $where[] = 'i.stage = :stage';
            $params['stage'] = $stage;
        }

        if ($date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $where[] = 'i.interview_date = :interview_date';
            $params['interview_date'] = $date;
        }

        $sql = 'SELECT c.full_name AS candidate_name, cl.company_name AS client_name, 
                       i.round_name, i.interview_date, i.interviewer, i.mode, i.stage, i.feedback
                FROM interviews i
                LEFT JOIN candidates c ON c.id = i.candidate_id
                LEFT JOIN clients cl ON cl.id = i.client_id';

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY DATE(i.interview_date) ASC, i.id DESC';

        $stmt = db()->prepare($sql);
        $stmt->execute($params);

        self::streamCsv('interviews_filtered_' . date('Ymd_His') . '.csv', $stmt->fetchAll());
    }

    private static function streamCsv(string $filename, array $rows): never
    {
        if (ob_get_length()) {
            ob_end_clean();
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        $output = fopen('php://output', 'wb');

        // Add UTF-8 BOM for Excel compatibility
        fwrite($output, "\xEF\xBB\xBF");

        if (!empty($rows)) {
            fputcsv($output, array_keys($rows[0]), ',', '"', "\\");
            foreach ($rows as $row) {
                fputcsv($output, $row, ',', '"', "\\");
            }
        }

        fclose($output);
        exit;
    }

    private static function syncCandidateStatus(int $candidateId): void
    {
        if ($candidateId <= 0) return;

        // Get all interviews for this candidate
        $stmt = db()->prepare('SELECT stage FROM interviews WHERE candidate_id = :candidate_id');
        $stmt->execute(['candidate_id' => $candidateId]);
        $interviews = $stmt->fetchAll();

        $hasSelected = false;
        $hasScheduled = false;
        $hasCompleted = false;

        foreach ($interviews as $i) {
            $stage = (string)($i['stage'] ?? '');
            if ($stage === 'Selected') $hasSelected = true;
            if ($stage === 'Scheduled') $hasScheduled = true;
            if ($stage === 'Completed') $hasCompleted = true;
        }

        // Determine new status
        $newStatus = 'Applied';
        if ($hasSelected) {
            $newStatus = 'Hired';
        } elseif ($hasScheduled || $hasCompleted) {
            $newStatus = 'Interview';
        }

        // Update candidate status
        $update = db()->prepare('UPDATE candidates SET status = :status WHERE id = :id');
        $update->execute(['status' => $newStatus, 'id' => $candidateId]);
    }
}
