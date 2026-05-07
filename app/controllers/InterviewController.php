<?php

declare(strict_types=1);

class InterviewController
{
    public static function index(): void
    {
        require_auth();

        $user = current_user();
        $flash = get_flash();

        $stage = trim((string) ($_GET['stage'] ?? ''));
        $date = trim((string) ($_GET['date'] ?? ''));

        $where = [];
        $params = [];

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

        $candidates = db()->query('SELECT id, full_name FROM candidates ORDER BY full_name ASC')->fetchAll();
        $clients = db()->query('SELECT id, company_name FROM clients ORDER BY company_name ASC')->fetchAll();

        $stageOptions = interview_stage_options();
        $modeOptions = interview_mode_options();

        $stats = [
            'total' => (int) (db()->query('SELECT COUNT(*) AS total FROM interviews')->fetch()['total'] ?? 0),
            'scheduled' => 0,
            'completed' => 0,
            'selected' => 0,
            'rejected' => 0,
        ];

        $rows = db()->query('SELECT stage, COUNT(*) AS total FROM interviews GROUP BY stage')->fetchAll();

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
            redirect('index.php?action=interviews');
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
            redirect('index.php?action=interviews');
        }

        if (!in_array($mode, interview_mode_options(), true)) {
            $mode = 'Online';
        }

        if (!in_array($stage, interview_stage_options(), true)) {
            $stage = 'Scheduled';
        }

        $stmt = db()->prepare(
            'INSERT INTO interviews (candidate_id, client_id, round_name, interview_date, interviewer, mode, stage, feedback)
             VALUES (:candidate_id, :client_id, :round_name, :interview_date, :interviewer, :mode, :stage, :feedback)'
        );

        $stmt->execute([
            'candidate_id' => $candidateId,
            'client_id' => $clientId > 0 ? $clientId : null,
            'round_name' => $roundName,
            'interview_date' => $interviewDate,
            'interviewer' => $interviewer,
            'mode' => $mode,
            'stage' => $stage,
            'feedback' => $feedback,
        ]);

        if ($stage === 'Selected') {
            $updateCandidate = db()->prepare('UPDATE candidates SET status = :status WHERE id = :candidate_id');
            $updateCandidate->execute(['status' => 'Hired', 'candidate_id' => $candidateId]);
        } elseif ($stage === 'Rejected') {
            $updateCandidate = db()->prepare('UPDATE candidates SET status = :status WHERE id = :candidate_id');
            $updateCandidate->execute(['status' => 'Rejected', 'candidate_id' => $candidateId]);
        } elseif ($stage === 'Completed') {
            $updateCandidate = db()->prepare('UPDATE candidates SET status = :status WHERE id = :candidate_id AND status = "Applied"');
            $updateCandidate->execute(['status' => 'Interview', 'candidate_id' => $candidateId]);
        }

        clear_old();
        set_flash('Interview added successfully.', 'success');
        redirect('index.php?action=interviews');
    }

    public static function updateStage(): void
    {
        require_auth();
        verify_csrf();

        $interviewId = (int) ($_POST['interview_id'] ?? 0);
        $stage = trim((string) ($_POST['stage'] ?? ''));

        if ($interviewId <= 0 || !in_array($stage, interview_stage_options(), true)) {
            set_flash('Invalid interview stage update request.', 'danger');
            redirect('index.php?action=interviews');
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
            $candidateId = (int) $row['candidate_id'];

            if ($stage === 'Selected') {
                $updateCandidate = db()->prepare('UPDATE candidates SET status = :status WHERE id = :candidate_id');
                $updateCandidate->execute(['status' => 'Hired', 'candidate_id' => $candidateId]);
            } elseif ($stage === 'Rejected') {
                $updateCandidate = db()->prepare('UPDATE candidates SET status = :status WHERE id = :candidate_id');
                $updateCandidate->execute(['status' => 'Rejected', 'candidate_id' => $candidateId]);
            } elseif ($stage === 'Completed') {
                $updateCandidate = db()->prepare('UPDATE candidates SET status = :status WHERE id = :candidate_id AND status = "Applied"');
                $updateCandidate->execute(['status' => 'Interview', 'candidate_id' => $candidateId]);
            }
        }

        set_flash('Interview stage updated.', 'success');
        redirect('index.php?action=interviews');
    }
}
