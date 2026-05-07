<?php

declare(strict_types=1);

class ClientController
{
    public static function index(): void
    {
        require_auth();

        $user = current_user();
        $flash = get_flash();

        $search = trim((string) ($_GET['search'] ?? ''));
        $status = trim((string) ($_GET['status'] ?? ''));

        $where = [];
        $params = [];

        if ($search !== '') {
            $where[] = '(company_name LIKE :search OR job_code LIKE :search OR reference_code LIKE :search OR contact_person LIKE :search OR mobile_number LIKE :search OR category LIKE :search OR job_role LIKE :search OR area LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        if ($status !== '' && in_array($status, client_status_options(), true)) {
            $where[] = 'status = :status';
            $params['status'] = $status;
        }

        $sql = 'SELECT id, company_name, job_code, reference_code, contact_person, mobile_number, mobile_number_2,
                       website, area, category, job_role, timing, gender_preference, required_person_count,
                       budget, expectation, remarks, follower_name, status, follow_up_1, follow_up_2, follow_up_3,
                       email, open_positions, phone
                FROM clients';

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY id DESC';

        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $clients = $stmt->fetchAll();

        $statusOptions = client_status_options();

        $stats = [
            'total' => (int) (db()->query('SELECT COUNT(*) AS total FROM clients')->fetch()['total'] ?? 0),
            'active' => 0,
            'in_progress' => 0,
            'on_hold' => 0,
            'closed' => 0,
            'open_positions' => (int) (db()->query('SELECT COALESCE(SUM(CASE WHEN required_person_count > 0 THEN required_person_count ELSE open_positions END), 0) AS total FROM clients')->fetch()['total'] ?? 0),
        ];

        $rows = db()->query('SELECT status, COUNT(*) AS total FROM clients GROUP BY status')->fetchAll();

        foreach ($rows as $row) {
            $key = strtolower(str_replace(' ', '_', trim((string) ($row['status'] ?? ''))));

            if (array_key_exists($key, $stats)) {
                $stats[$key] = (int) ($row['total'] ?? 0);
            }
        }

        require BASE_PATH . '/app/views/dashboard/clients.php';
    }

    public static function addClient(): void
    {
        require_auth();
        verify_csrf();

        if (!is_post()) {
            redirect('index.php?action=clients');
        }

        $companyName = trim((string) ($_POST['company_name'] ?? ''));
        $jobCode = trim((string) ($_POST['job_code'] ?? ''));
        $referenceCode = trim((string) ($_POST['reference_code'] ?? ''));
        $contactPerson = trim((string) ($_POST['contact_person'] ?? ''));
        $mobileNumber = trim((string) ($_POST['mobile_number'] ?? ''));
        $mobileNumber2 = trim((string) ($_POST['mobile_number_2'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $website = trim((string) ($_POST['website'] ?? ''));
        $area = trim((string) ($_POST['area'] ?? ''));
        $category = trim((string) ($_POST['category'] ?? ''));
        $jobRole = trim((string) ($_POST['job_role'] ?? ''));
        $timing = trim((string) ($_POST['timing'] ?? ''));
        $genderPreference = trim((string) ($_POST['gender_preference'] ?? 'Any'));
        $requiredPersonCount = max(0, (int) ($_POST['required_person_count'] ?? 0));
        $budget = trim((string) ($_POST['budget'] ?? ''));
        $expectation = trim((string) ($_POST['expectation'] ?? ''));
        $remarks = trim((string) ($_POST['remarks'] ?? ''));
        $followerName = trim((string) ($_POST['follower_name'] ?? ''));
        $status = trim((string) ($_POST['status'] ?? 'Active'));
        $followUp1 = normalize_datetime_input((string) ($_POST['follow_up_1'] ?? ''));
        $followUp2 = normalize_datetime_input((string) ($_POST['follow_up_2'] ?? ''));
        $followUp3 = normalize_datetime_input((string) ($_POST['follow_up_3'] ?? ''));

        set_old($_POST);

        if ($companyName === '') {
            set_flash('Company name is required.', 'danger');
            redirect('index.php?action=clients');
        }

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_flash('Please enter a valid client email.', 'danger');
            redirect('index.php?action=clients');
        }

        if (!in_array($status, client_status_options(), true)) {
            $status = 'Active';
        }

        $stmt = db()->prepare(
            'INSERT INTO clients (
                company_name, job_code, reference_code, contact_person, mobile_number, mobile_number_2,
                email, phone, website, area, category, job_role, timing, gender_preference,
                required_person_count, budget, expectation, remarks, follower_name, status,
                follow_up_1, follow_up_2, follow_up_3, open_positions
            ) VALUES (
                :company_name, :job_code, :reference_code, :contact_person, :mobile_number, :mobile_number_2,
                :email, :phone, :website, :area, :category, :job_role, :timing, :gender_preference,
                :required_person_count, :budget, :expectation, :remarks, :follower_name, :status,
                :follow_up_1, :follow_up_2, :follow_up_3, :open_positions
            )'
        );

        $stmt->execute([
            'company_name' => $companyName,
            'job_code' => $jobCode,
            'reference_code' => $referenceCode,
            'contact_person' => $contactPerson,
            'mobile_number' => $mobileNumber,
            'mobile_number_2' => $mobileNumber2,
            'email' => $email,
            'phone' => $mobileNumber,
            'website' => $website,
            'area' => $area,
            'category' => $category,
            'job_role' => $jobRole,
            'timing' => $timing,
            'gender_preference' => $genderPreference,
            'required_person_count' => $requiredPersonCount,
            'budget' => $budget,
            'expectation' => $expectation,
            'remarks' => $remarks,
            'follower_name' => $followerName,
            'status' => $status,
            'follow_up_1' => $followUp1,
            'follow_up_2' => $followUp2,
            'follow_up_3' => $followUp3,
            'open_positions' => $requiredPersonCount,
        ]);

        $clientId = (int) db()->lastInsertId();
        self::createFollowUpReminders($clientId, $companyName, $followUp1, $followUp2, $followUp3, $email, $mobileNumber);

        clear_old();
        set_flash('Client created successfully.', 'success');
        redirect('index.php?action=clients');
    }

    public static function updateStatus(): void
    {
        require_auth();
        verify_csrf();

        $clientId = (int) ($_POST['client_id'] ?? 0);
        $status = trim((string) ($_POST['status'] ?? ''));

        if ($clientId <= 0 || !in_array($status, client_status_options(), true)) {
            set_flash('Invalid client status update request.', 'danger');
            redirect('index.php?action=clients');
        }

        $stmt = db()->prepare('UPDATE clients SET status = :status WHERE id = :id');
        $stmt->execute([
            'status' => $status,
            'id' => $clientId,
        ]);

        set_flash('Client status updated.', 'success');
        redirect('index.php?action=clients');
    }

    public static function addReminder(): void
    {
        require_auth();
        verify_csrf();

        $clientId = (int) ($_POST['client_id'] ?? 0);
        $title = trim((string) ($_POST['title'] ?? 'Client Follow-up Reminder'));
        $remindAt = normalize_datetime_input((string) ($_POST['remind_at'] ?? ''));
        $message = trim((string) ($_POST['reminder_message'] ?? ''));
        $emailTo = trim((string) ($_POST['email_to'] ?? ''));
        $phoneTo = trim((string) ($_POST['phone_to'] ?? ''));
        $notifyEmail = !empty($_POST['notify_email']) ? 1 : 0;
        $notifySms = !empty($_POST['notify_sms']) ? 1 : 0;
        $notifyWeb = !empty($_POST['notify_web']) ? 1 : 0;

        if ($clientId <= 0 || $remindAt === '') {
            set_flash('Please select valid client reminder details.', 'danger');
            redirect('index.php?action=clients');
        }

        $insert = db()->prepare(
            'INSERT INTO reminders (
                reminder_type, reference_id, title, reminder_message, remind_at,
                email_to, phone_to, notify_email, notify_sms, notify_web
            ) VALUES (
                :reminder_type, :reference_id, :title, :reminder_message, :remind_at,
                :email_to, :phone_to, :notify_email, :notify_sms, :notify_web
            )'
        );

        $insert->execute([
            'reminder_type' => 'client',
            'reference_id' => $clientId,
            'title' => $title,
            'reminder_message' => $message !== '' ? $message : 'Follow-up reminder generated from CRM.',
            'remind_at' => $remindAt,
            'email_to' => $emailTo,
            'phone_to' => $phoneTo,
            'notify_email' => $notifyEmail,
            'notify_sms' => $notifySms,
            'notify_web' => $notifyWeb,
        ]);

        set_flash('Reminder added successfully.', 'success');
        redirect('index.php?action=clients');
    }

    private static function createFollowUpReminders(int $clientId, string $companyName, string $follow1, string $follow2, string $follow3, string $email, string $phone): void
    {
        $items = [
            ['Follow-up 1', $follow1],
            ['Follow-up 2', $follow2],
            ['Follow-up 3', $follow3],
        ];

        foreach ($items as $item) {
            [$label, $dateTime] = $item;

            if ($dateTime === '') {
                continue;
            }

            $insert = db()->prepare(
                'INSERT INTO reminders (
                    reminder_type, reference_id, title, reminder_message, remind_at,
                    email_to, phone_to, notify_email, notify_sms, notify_web
                ) VALUES (
                    :reminder_type, :reference_id, :title, :reminder_message, :remind_at,
                    :email_to, :phone_to, 1, 1, 1
                )'
            );

            $insert->execute([
                'reminder_type' => 'client',
                'reference_id' => $clientId,
                'title' => $label . ' - ' . $companyName,
                'reminder_message' => $label . ' reminder for client ' . $companyName,
                'remind_at' => $dateTime,
                'email_to' => $email,
                'phone_to' => $phone,
            ]);
        }
    }
}
