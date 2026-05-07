<?php

declare(strict_types=1);

class HomeController
{
    public static function index(): void
    {
        require_auth();

        $user = current_user();
        $flash = get_flash();

        $candidateStats = [
            'total' => 0,
            'applied' => 0,
            'interview' => 0,
            'hired' => 0,
            'rejected' => 0,
        ];

        $candidateStats['total'] = (int) (db()->query('SELECT COUNT(*) AS total FROM candidates')->fetch()['total'] ?? 0);

        $statusRows = db()->query('SELECT status, COUNT(*) AS total FROM candidates GROUP BY status')->fetchAll();

        foreach ($statusRows as $row) {
            $key = strtolower(trim((string) ($row['status'] ?? '')));

            if (array_key_exists($key, $candidateStats)) {
                $candidateStats[$key] = (int) ($row['total'] ?? 0);
            }
        }

        $clientStats = [
            'total' => 0,
            'active' => 0,
            'in_progress' => 0,
            'on_hold' => 0,
            'closed' => 0,
            'open_positions' => 0,
        ];

        $clientStats['total'] = (int) (db()->query('SELECT COUNT(*) AS total FROM clients')->fetch()['total'] ?? 0);
        $clientStats['open_positions'] = (int) (db()->query('SELECT COALESCE(SUM(CASE WHEN required_person_count > 0 THEN required_person_count ELSE open_positions END), 0) AS total FROM clients')->fetch()['total'] ?? 0);

        $clientRows = db()->query('SELECT status, COUNT(*) AS total FROM clients GROUP BY status')->fetchAll();

        foreach ($clientRows as $row) {
            $key = strtolower(str_replace(' ', '_', trim((string) ($row['status'] ?? ''))));

            if (array_key_exists($key, $clientStats)) {
                $clientStats[$key] = (int) ($row['total'] ?? 0);
            }
        }

        $interviewStats = [
            'total' => 0,
            'scheduled' => 0,
            'completed' => 0,
            'selected' => 0,
            'rejected' => 0,
        ];

        $interviewStats['total'] = (int) (db()->query('SELECT COUNT(*) AS total FROM interviews')->fetch()['total'] ?? 0);

        $interviewRows = db()->query('SELECT stage, COUNT(*) AS total FROM interviews GROUP BY stage')->fetchAll();

        foreach ($interviewRows as $row) {
            $key = strtolower(trim((string) ($row['stage'] ?? '')));

            if (array_key_exists($key, $interviewStats)) {
                $interviewStats[$key] = (int) ($row['total'] ?? 0);
            }
        }

        $monthlyRows = db()->query(
            'SELECT strftime("%Y-%m", added_on) AS ym, COUNT(*) AS total
             FROM candidates
             WHERE added_on IS NOT NULL AND added_on != ""
             GROUP BY ym
             ORDER BY ym DESC
             LIMIT 6'
        )->fetchAll();

        $ordered = array_reverse($monthlyRows);
        $chartLabels = [];
        $chartValues = [];

        foreach ($ordered as $row) {
            $ym = (string) ($row['ym'] ?? '');

            if ($ym === '' || strlen($ym) < 7) {
                continue;
            }

            $year = (int) substr($ym, 0, 4);
            $month = (int) substr($ym, 5, 2);
            $dateObj = DateTime::createFromFormat('Y-n-j', $year . '-' . $month . '-1');

            if ($dateObj) {
                $chartLabels[] = $dateObj->format('M Y');
            } else {
                $chartLabels[] = $ym;
            }

            $chartValues[] = (int) ($row['total'] ?? 0);
        }

        if (empty($chartLabels)) {
            $chartLabels = [date('M Y')];
            $chartValues = [0];
        }

        $periodStats = [
            'today' => [
                'candidates' => (int) (db()->query('SELECT COUNT(*) AS total FROM candidates WHERE date(added_on) = date("now", "localtime")')->fetch()['total'] ?? 0),
                'interviews' => (int) (db()->query('SELECT COUNT(*) AS total FROM interviews WHERE date(interview_date) = date("now", "localtime")')->fetch()['total'] ?? 0),
            ],
            'month' => [
                'candidates' => (int) (db()->query('SELECT COUNT(*) AS total FROM candidates WHERE strftime("%Y-%m", added_on) = strftime("%Y-%m", "now", "localtime")')->fetch()['total'] ?? 0),
                'interviews' => (int) (db()->query('SELECT COUNT(*) AS total FROM interviews WHERE strftime("%Y-%m", interview_date) = strftime("%Y-%m", "now", "localtime")')->fetch()['total'] ?? 0),
            ],
            'year' => [
                'candidates' => (int) (db()->query('SELECT COUNT(*) AS total FROM candidates WHERE strftime("%Y", added_on) = strftime("%Y", "now", "localtime")')->fetch()['total'] ?? 0),
                'interviews' => (int) (db()->query('SELECT COUNT(*) AS total FROM interviews WHERE strftime("%Y", interview_date) = strftime("%Y", "now", "localtime")')->fetch()['total'] ?? 0),
            ],
        ];

        $pipeline = [
            ['label' => 'Applied', 'count' => $candidateStats['applied'], 'class' => 'stage-applied'],
            ['label' => 'Interview', 'count' => $candidateStats['interview'], 'class' => 'stage-interview'],
            ['label' => 'Hired', 'count' => $candidateStats['hired'], 'class' => 'stage-hired'],
            ['label' => 'Rejected', 'count' => $candidateStats['rejected'], 'class' => 'stage-rejected'],
        ];

        $clientChart = [
            'labels' => ['Active', 'In Progress', 'On Hold', 'Closed'],
            'values' => [
                $clientStats['active'],
                $clientStats['in_progress'],
                $clientStats['on_hold'],
                $clientStats['closed'],
            ],
        ];

        $interviewChart = [
            'labels' => ['Scheduled', 'Completed', 'Selected', 'Rejected'],
            'values' => [
                $interviewStats['scheduled'],
                $interviewStats['completed'],
                $interviewStats['selected'],
                $interviewStats['rejected'],
            ],
        ];

        $upcomingInterviews = db()->query(
            'SELECT i.id, i.round_name, i.interview_date, i.interviewer, i.mode, i.stage,
                    c.full_name AS candidate_name, cl.company_name AS client_name
             FROM interviews i
             LEFT JOIN candidates c ON c.id = i.candidate_id
             LEFT JOIN clients cl ON cl.id = i.client_id
             ORDER BY DATE(i.interview_date) ASC, i.id DESC
             LIMIT 8'
        )->fetchAll();

        $upcomingReminders = db()->query(
            'SELECT id, title, reminder_message, remind_at, email_to, phone_to, email_status, sms_status, web_status
             FROM reminders
             WHERE datetime(remind_at) >= datetime("now", "-1 day")
             ORDER BY datetime(remind_at) ASC
             LIMIT 8'
        )->fetchAll();

        require BASE_PATH . '/app/views/dashboard/home.php';
    }

    public static function markNotificationRead(): void
    {
        require_auth();

        $id = (int) ($_GET['id'] ?? 0);

        if ($id > 0) {
            mark_notification_read($id);
        }

        $back = trim((string) ($_SERVER['HTTP_REFERER'] ?? 'index.php?action=home'));
        redirect($back !== '' ? $back : 'index.php?action=home');
    }
}
