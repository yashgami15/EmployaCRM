<?php

declare(strict_types=1);

class ClientController
{
    public static function index(): void
    {
        require_permission('clients');

        $user = current_user();
        $flash = get_flash();

        $search = trim((string) ($_GET['search'] ?? ''));
        $status = trim((string) ($_GET['status'] ?? ''));

        $where = [];
        $params = [];

        if (($_SESSION['role'] ?? 'user') !== 'admin') {
            $where[] = 'tenant_name = :tenant_name';
            $params['tenant_name'] = $_SESSION['tenant_name'] ?? '';
        }

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

        $tenantFilter = (($_SESSION['role'] ?? 'user') !== 'admin') ? "WHERE tenant_name = '" . ($_SESSION['tenant_name'] ?? '') . "'" : "";
        
        $stats = [
            'total' => (int) (db()->query("SELECT COUNT(*) AS total FROM clients $tenantFilter")->fetch()['total'] ?? 0),
            'active' => 0,
            'in_progress' => 0,
            'on_hold' => 0,
            'closed' => 0,
            'open_positions' => (int) (db()->query("SELECT COALESCE(SUM(CASE WHEN required_person_count > 0 THEN required_person_count ELSE open_positions END), 0) AS total FROM clients $tenantFilter")->fetch()['total'] ?? 0),
        ];

        $rows = db()->query("SELECT status, COUNT(*) AS total FROM clients $tenantFilter GROUP BY status")->fetchAll();

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
            redirect_with_filters('clients');
        }

        $companyName = trim((string) ($_POST['company_name'] ?? ''));
        $jobCode = trim((string) ($_POST['job_code'] ?? ''));
        if ($jobCode === '') {
            $jobCode = generate_client_job_code();
        }
        $referenceCode = trim((string) ($_POST['reference_code'] ?? ''));
        $contactPerson = trim((string) ($_POST['contact_person'] ?? ''));
        $mobileNumber = trim((string) ($_POST['mobile_number'] ?? ''));
        $mobileNumber2 = trim((string) ($_POST['mobile_number_2'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $website = trim((string) ($_POST['website'] ?? ''));
        $area = trim((string) ($_POST['area'] ?? ''));
        
        $categories = (array) ($_POST['category'] ?? ['']);
        $jobRoles = (array) ($_POST['job_role'] ?? ['']);
        $timings = (array) ($_POST['timing'] ?? ['']);
        $genderPreferences = (array) ($_POST['gender_preference'] ?? ['Any']);
        $requiredPersonCounts = (array) ($_POST['required_person_count'] ?? [0]);
        $budgets = (array) ($_POST['budget'] ?? ['']);
        $expectations = (array) ($_POST['expectation'] ?? ['']);
        
        $remarks = trim((string) ($_POST['remarks'] ?? ''));
        $followerName = trim((string) ($_POST['follower_name'] ?? ''));
        $status = trim((string) ($_POST['status'] ?? 'Active'));
        $followUp1 = normalize_datetime_input((string) ($_POST['follow_up_1'] ?? ''));
        $followUp2 = normalize_datetime_input((string) ($_POST['follow_up_2'] ?? ''));
        $followUp3 = normalize_datetime_input((string) ($_POST['follow_up_3'] ?? ''));

        set_old($_POST);

        if ($companyName === '') {
            set_flash('Company name is required.', 'danger');
            redirect_with_filters('clients');
        }

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_flash('Please enter a valid client email.', 'danger');
            redirect_with_filters('clients');
        }

        if (!in_array($status, client_status_options(), true)) {
            $status = 'Active';
        }

        // Duplicate Check (Case-insensitive & NULL-safe)
        $checkStmt = db()->prepare('SELECT id FROM clients WHERE LOWER(company_name) = LOWER(:company) AND mobile_number = :phone AND LOWER(IFNULL(job_role, "")) = LOWER(:role) LIMIT 1');
        
        $count = max(1, count($jobRoles));
        $processedRoles = [];
        $firstClientId = 0;

        for ($i = 0; $i < $count; $i++) {
            $currentRole = trim((string) ($jobRoles[$i] ?? ''));
            
            // Internal Duplicate Check (within the same request)
            if (in_array(strtolower($currentRole), $processedRoles)) {
                set_flash("Duplicate job role '$currentRole' provided for '$companyName' in the same form.", 'danger');
                redirect_with_filters('clients');
            }
            $processedRoles[] = strtolower($currentRole);

            $checkStmt->execute(['company' => $companyName, 'phone' => $mobileNumber, 'role' => $currentRole]);
            if ($checkStmt->fetch()) {
                set_flash("Client requirement for '$companyName' with role '$currentRole' already exists.", 'danger');
                redirect_with_filters('clients');
            }
        }

        $stmt = db()->prepare(
            'INSERT INTO clients (
                tenant_name, company_name, job_code, reference_code, contact_person, mobile_number, mobile_number_2,
                email, phone, website, area, category, job_role, timing, gender_preference,
                required_person_count, budget, expectation, remarks, follower_name, status,
                follow_up_1, follow_up_2, follow_up_3, open_positions
            ) VALUES (
                :tenant_name, :company_name, :job_code, :reference_code, :contact_person, :mobile_number, :mobile_number_2,
                :email, :phone, :website, :area, :category, :job_role, :timing, :gender_preference,
                :required_person_count, :budget, :expectation, :remarks, :follower_name, :status,
                :follow_up_1, :follow_up_2, :follow_up_3, :open_positions
            )'
        );

        for ($i = 0; $i < $count; $i++) {
            $reqCount = max(0, (int) ($requiredPersonCounts[$i] ?? 0));
            $stmt->execute([
                'tenant_name' => $_SESSION['tenant_name'] ?? '',
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
                'category' => trim((string) ($categories[$i] ?? '')),
                'job_role' => trim((string) ($jobRoles[$i] ?? '')),
                'timing' => trim((string) ($timings[$i] ?? '')),
                'gender_preference' => trim((string) ($genderPreferences[$i] ?? 'Any')),
                'required_person_count' => $reqCount,
                'budget' => trim((string) ($budgets[$i] ?? '')),
                'expectation' => trim((string) ($expectations[$i] ?? '')),
                'remarks' => $remarks,
                'follower_name' => $followerName,
                'status' => $status,
                'follow_up_1' => $followUp1,
                'follow_up_2' => $followUp2,
                'follow_up_3' => $followUp3,
                'open_positions' => $reqCount,
            ]);

            if ($i === 0) {
                $firstClientId = (int) db()->lastInsertId();
                log_activity('client', $firstClientId, 'Client Created', "New client profile created: $companyName");
            } else {
                $newId = (int) db()->lastInsertId();
                log_activity('client', $newId, 'Job Role Added', "Additional job role ($jobRoles[$i]) added for $companyName");
            }
        }

        if ($firstClientId > 0) {
            self::createFollowUpReminders($firstClientId, $companyName, $followUp1, $followUp2, $followUp3, $email, $mobileNumber);
        }

        clear_old();
        set_flash('Client(s) created successfully.', 'success');
        redirect_with_filters('clients');
    }

    public static function updateClient(): void
    {
        require_auth();
        verify_csrf();

        if (!is_post()) {
            redirect_with_filters('clients');
        }

        $clientId = (int) ($_POST['client_id'] ?? 0);
        $companyName = trim((string) ($_POST['company_name'] ?? ''));
        $jobCode = trim((string) ($_POST['job_code'] ?? ''));
        $referenceCode = trim((string) ($_POST['reference_code'] ?? ''));
        $contactPerson = trim((string) ($_POST['contact_person'] ?? ''));
        $mobileNumber = trim((string) ($_POST['mobile_number'] ?? ''));
        $mobileNumber2 = trim((string) ($_POST['mobile_number_2'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $website = trim((string) ($_POST['website'] ?? ''));
        $area = trim((string) ($_POST['area'] ?? ''));
        
        $categories = (array) ($_POST['category'] ?? ['']);
        $jobRoles = (array) ($_POST['job_role'] ?? ['']);
        $timings = (array) ($_POST['timing'] ?? ['']);
        $genderPreferences = (array) ($_POST['gender_preference'] ?? ['Any']);
        $requiredPersonCounts = (array) ($_POST['required_person_count'] ?? [0]);
        $budgets = (array) ($_POST['budget'] ?? ['']);
        $expectations = (array) ($_POST['expectation'] ?? ['']);
        
        $category = trim((string) ($categories[0] ?? ''));
        $jobRole = trim((string) ($jobRoles[0] ?? ''));
        $timing = trim((string) ($timings[0] ?? ''));
        $genderPreference = trim((string) ($genderPreferences[0] ?? 'Any'));
        $requiredPersonCount = max(0, (int) ($requiredPersonCounts[0] ?? 0));
        $budget = trim((string) ($budgets[0] ?? ''));
        $expectation = trim((string) ($expectations[0] ?? ''));

        $remarks = trim((string) ($_POST['remarks'] ?? ''));
        $followerName = trim((string) ($_POST['follower_name'] ?? ''));
        $status = trim((string) ($_POST['status'] ?? 'Active'));
        $followUp1 = normalize_datetime_input((string) ($_POST['follow_up_1'] ?? ''));
        $followUp2 = normalize_datetime_input((string) ($_POST['follow_up_2'] ?? ''));
        $followUp3 = normalize_datetime_input((string) ($_POST['follow_up_3'] ?? ''));

        if ($clientId <= 0 || $companyName === '') {
            set_flash('Valid client ID and Company name are required.', 'danger');
            redirect_with_filters('clients');
        }

        if (!in_array($status, client_status_options(), true)) {
            $status = 'Active';
        }

        $stmt = db()->prepare(
            'UPDATE clients SET 
                company_name = :company_name, job_code = :job_code, reference_code = :reference_code, 
                contact_person = :contact_person, mobile_number = :mobile_number, mobile_number_2 = :mobile_number_2,
                email = :email, phone = :phone, website = :website, area = :area, category = :category, 
                job_role = :job_role, timing = :timing, gender_preference = :gender_preference,
                required_person_count = :required_person_count, budget = :budget, expectation = :expectation, 
                remarks = :remarks, follower_name = :follower_name, status = :status,
                follow_up_1 = :follow_up_1, follow_up_2 = :follow_up_2, follow_up_3 = :follow_up_3, 
                open_positions = :open_positions
            WHERE id = :id'
        );

        $stmt->execute([
            'id' => $clientId,
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

        log_activity('client', $clientId, 'Client Updated', 'Company profile details were updated.');

        set_flash('Client updated successfully.', 'success');
        redirect_with_filters('clients');
    }

    public static function updateStatus(): void
    {
        require_auth();
        verify_csrf();

        $clientId = (int) ($_POST['client_id'] ?? 0);
        $status = trim((string) ($_POST['status'] ?? ''));

        if ($clientId <= 0 || !in_array($status, client_status_options(), true)) {
            set_flash('Invalid client status update request.', 'danger');
            redirect_with_filters('clients');
        }

        $stmt = db()->prepare('UPDATE clients SET status = :status WHERE id = :id');
        $stmt->execute([
            'status' => $status,
            'id' => $clientId,
        ]);

        set_flash('Client status updated.', 'success');
        redirect_with_filters('clients');
    }

    public static function deleteSelected(): void
    {
        require_auth();
        verify_csrf();

        $ids = $_POST['selected_ids'] ?? [];
        if (!is_array($ids)) {
            $ids = explode(',', (string) $ids);
        }

        $cleanIds = [];
        foreach ($ids as $id) {
            $intId = (int)$id;
            if ($intId > 0) $cleanIds[] = $intId;
        }

        if (empty($cleanIds)) {
            set_flash('No clients selected for deletion.', 'danger');
            redirect_with_filters('clients');
        }

        $placeholders = str_repeat('?,', count($cleanIds) - 1) . '?';
        $stmt = db()->prepare("DELETE FROM clients WHERE id IN ($placeholders)");
        $stmt->execute(array_values($cleanIds));

        set_flash('Selected clients deleted successfully.', 'success');
        redirect_with_filters('clients');
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
            redirect_with_filters('clients');
        }

        $insert = db()->prepare(
            'INSERT INTO reminders (
                tenant_name, reminder_type, reference_id, title, reminder_message, remind_at,
                email_to, phone_to, notify_email, notify_sms, notify_web
            ) VALUES (
                :tenant_name, :reminder_type, :reference_id, :title, :reminder_message, :remind_at,
                :email_to, :phone_to, :notify_email, :notify_sms, :notify_web
            )'
        );

        $insert->execute([
            'tenant_name' => $_SESSION['tenant_name'] ?? '',
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
        redirect_with_filters('clients');
    }

    public static function importCsv(): void
    {
        require_auth();
        verify_csrf();

        if (empty($_FILES['csv_file']['tmp_name']) || !is_uploaded_file($_FILES['csv_file']['tmp_name'])) {
            set_flash('Please select a CSV file to import.', 'warning');
            redirect_with_filters('clients');
        }

        $handle = fopen($_FILES['csv_file']['tmp_name'], 'rb');

        if (!$handle) {
            set_flash('Unable to read uploaded CSV file.', 'danger');
            redirect_with_filters('clients');
        }

        $first = fgetcsv($handle, 0, ',', '"', "\\");
        $rows = [];
        $errors = [];
        $lineNumber = 1;

        if ($first !== false) {
            // Skip Excel separator hint if present
            if (count($first) === 1 && stripos($first[0], 'sep=') === 0) {
                $first = fgetcsv($handle, 0, ',', '"', "\\");
            }

            if ($first !== false) {
                // Clean BOM and lowercase headers
                $header = array_map(static function($value) {
                    $value = (string) $value;
                    // Remove UTF-8 BOM if present
                    if (strpos($value, "\xEF\xBB\xBF") === 0) {
                        $value = substr($value, 3);
                    }
                    return strtolower(trim($value));
                }, $first);
                
                while (($data = fgetcsv($handle, 0, ',', '"', "\\")) !== false) {
                    $lineNumber++;
                    $clientData = self::rowToClient($data, $header);
                    
                    // Validation
                    $rowErrors = [];
                    if (empty($clientData['company_name'])) {
                        $rowErrors[] = "Company Name is missing";
                    }
                    if (empty($clientData['mobile_number'])) {
                        $rowErrors[] = "Mobile Number is missing";
                    } elseif (!preg_match('/[0-9]{5,}/', $clientData['mobile_number'])) {
                        $rowErrors[] = "Invalid Mobile Number format (needs at least 5 digits): '" . $clientData['mobile_number'] . "'";
                    }
                    
                    if (!empty($rowErrors)) {
                        $errors[] = "Row $lineNumber: " . implode(', ', $rowErrors);
                        continue;
                    }

                    $rows[] = $clientData;
                }
            }
        }

        fclose($handle);

        if (empty($rows) && empty($errors)) {
            set_flash('No valid data found in CSV.', 'warning');
            redirect_with_filters('clients');
        }

        $imported = 0;
        $insert = db()->prepare(
            'INSERT INTO clients (
                tenant_name, company_name, job_code, reference_code, contact_person, mobile_number, mobile_number_2,
                email, phone, website, area, category, job_role, timing, gender_preference,
                required_person_count, budget, expectation, remarks, follower_name, status,
                follow_up_1, follow_up_2, follow_up_3, open_positions
            ) VALUES (
                :tenant_name, :company_name, :job_code, :reference_code, :contact_person, :mobile_number, :mobile_number_2,
                :email, :phone, :website, :area, :category, :job_role, :timing, :gender_preference,
                :required_person_count, :budget, :expectation, :remarks, :follower_name, :status,
                :follow_up_1, :follow_up_2, :follow_up_3, :open_positions
            )'
        );

        $checkStmt = db()->prepare('SELECT id FROM clients WHERE LOWER(company_name) = LOWER(:company) AND mobile_number = :phone AND LOWER(IFNULL(job_role, "")) = LOWER(:role) LIMIT 1');

        $lineNumber = 2;
        foreach ($rows as $item) {
            // Duplicate Check (Case-insensitive & NULL-safe)
            $checkStmt->execute([
                'company' => $item['company_name'],
                'phone' => $item['mobile_number'],
                'role' => $item['job_role']
            ]);
            
            if ($checkStmt->fetch()) {
                $errors[] = "Row $lineNumber: Duplicate found for '{$item['company_name']}' with role '{$item['job_role']}'";
                $lineNumber++;
                continue;
            }

            try {
                $item['tenant_name'] = $_SESSION['tenant_name'] ?? '';
                $insert->execute($item);
                $imported++;
            } catch (Exception $e) {
                $errors[] = 'Row ' . $lineNumber . ' for ' . ($item['company_name'] ?? 'Unknown') . ': Database error - ' . $e->getMessage();
            }
            $lineNumber++;
        }

        if (!empty($errors)) {
            $errorMsg = 'Imported ' . $imported . ' clients. Errors found in ' . count($errors) . ' rows:<br>' . implode('<br>', array_slice($errors, 0, 10));
            if (count($errors) > 10) {
                $errorMsg .= '<br>...and ' . (count($errors) - 10) . ' more errors.';
            }
            set_flash($errorMsg, 'danger');
        } else {
            set_flash('Successfully imported ' . $imported . ' clients from CSV.', 'success');
        }
        redirect_with_filters('clients');
    }

    private static function rowToClient(array $row, array $header): array
    {
        $defaultMap = [
            'company_name' => trim((string) ($row[0] ?? '')),
            'job_code' => trim((string) ($row[1] ?? '')),
            'reference_code' => trim((string) ($row[2] ?? '')),
            'contact_person' => trim((string) ($row[3] ?? '')),
            'mobile_number' => trim((string) ($row[4] ?? '')),
            'mobile_number_2' => trim((string) ($row[5] ?? '')),
            'email' => strtolower(trim((string) ($row[6] ?? ''))),
            'website' => trim((string) ($row[7] ?? '')),
            'area' => trim((string) ($row[8] ?? '')),
            'category' => trim((string) ($row[9] ?? '')),
            'job_role' => trim((string) ($row[10] ?? '')),
            'timing' => trim((string) ($row[11] ?? '')),
            'gender_preference' => trim((string) ($row[12] ?? 'Any')),
            'required_person_count' => (int) ($row[13] ?? 0),
            'budget' => trim((string) ($row[14] ?? '')),
            'expectation' => trim((string) ($row[15] ?? '')),
            'remarks' => trim((string) ($row[16] ?? '')),
            'follower_name' => trim((string) ($row[17] ?? '')),
            'status' => trim((string) ($row[18] ?? 'Active')),
            'follow_up_1' => normalize_datetime_input((string) ($row[19] ?? '')),
            'follow_up_2' => normalize_datetime_input((string) ($row[20] ?? '')),
            'follow_up_3' => normalize_datetime_input((string) ($row[21] ?? '')),
        ];

        if (!empty($header)) {
            foreach ($header as $index => $column) {
                if ($column === 'company' || $column === 'company name') $column = 'company_name';
                if ($column === 'mobile' || $column === 'mobile number' || $column === 'mobile num' || $column === 'phone') $column = 'mobile_number';
                if ($column === 'mobile 2' || $column === 'mobile number 2' || $column === 'mobile num 2') $column = 'mobile_number_2';
                if ($column === 'contact' || $column === 'contact person') $column = 'contact_person';
                if ($column === 'role' || $column === 'job role') $column = 'job_role';
                if ($column === 'reference' || $column === 'reference code') $column = 'reference_code';

                $value = trim((string) ($row[$index] ?? ''));
                
                // Handle Excel scientific notation (e.g., 9.20E+11)
                if (preg_match('/^[+-]?[0-9]*\.?[0-9]+[eE][+-]?[0-9]+$/', $value)) {
                    $value = sprintf("%.0f", (float)$value);
                }

                if (array_key_exists($column, $defaultMap)) {
                    $defaultMap[$column] = $value;
                }
            }
        }

        return [
            'company_name' => $defaultMap['company_name'],
            'job_code' => $defaultMap['job_code'] !== '' ? $defaultMap['job_code'] : generate_client_job_code(),
            'reference_code' => $defaultMap['reference_code'],
            'contact_person' => $defaultMap['contact_person'],
            'mobile_number' => $defaultMap['mobile_number'],
            'mobile_number_2' => $defaultMap['mobile_number_2'],
            'email' => $defaultMap['email'],
            'phone' => $defaultMap['mobile_number'],
            'website' => $defaultMap['website'],
            'area' => $defaultMap['area'],
            'category' => match_option_case_insensitive($defaultMap['category'], client_category_options()),
            'job_role' => $defaultMap['job_role'],
            'timing' => match_option_case_insensitive($defaultMap['timing'], client_timing_options()),
            'gender_preference' => match_option_case_insensitive($defaultMap['gender_preference'], client_gender_options(), 'Any'),
            'required_person_count' => (int) $defaultMap['required_person_count'],
            'budget' => $defaultMap['budget'],
            'expectation' => $defaultMap['expectation'],
            'remarks' => $defaultMap['remarks'],
            'follower_name' => $defaultMap['follower_name'],
            'status' => match_option_case_insensitive($defaultMap['status'], client_status_options(), 'Active'),
            'follow_up_1' => $defaultMap['follow_up_1'],
            'follow_up_2' => $defaultMap['follow_up_2'],
            'follow_up_3' => $defaultMap['follow_up_3'],
            'open_positions' => (int) $defaultMap['required_person_count'],
        ];
    }

    public static function exportSelected(): void
    {
        require_auth();

        $ids = selected_ids_from_request();

        if (empty($ids)) {
            set_flash('Please select clients before exporting selected.', 'warning');
            redirect_with_filters('clients');
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = 'SELECT company_name, job_code, reference_code, contact_person, email, mobile_number, mobile_number_2, website, area, category, job_role, timing, gender_preference, required_person_count, budget, expectation, follower_name, remarks, status, follow_up_1, follow_up_2, follow_up_3
                FROM clients
                WHERE id IN (' . $placeholders . ')
                ORDER BY id DESC';

        $stmt = db()->prepare($sql);
        $stmt->execute($ids);

        self::streamCsv('clients_selected_' . date('Ymd_His') . '.csv', $stmt->fetchAll());
    }

    public static function exportFiltered(): void
    {
        require_auth();

        $search = trim((string) ($_GET['search'] ?? ''));
        $status = trim((string) ($_GET['status'] ?? ''));

        $where = [];
        $params = [];

        if (($_SESSION['role'] ?? 'user') !== 'admin') {
            $where[] = 'tenant_name = :tenant_name';
            $params['tenant_name'] = $_SESSION['tenant_name'] ?? '';
        }

        if ($search !== '') {
            $where[] = '(company_name LIKE :search OR job_code LIKE :search OR reference_code LIKE :search OR contact_person LIKE :search OR mobile_number LIKE :search OR category LIKE :search OR job_role LIKE :search OR area LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        if ($status !== '' && in_array($status, client_status_options(), true)) {
            $where[] = 'status = :status';
            $params['status'] = $status;
        }

        $sql = 'SELECT company_name, job_code, reference_code, contact_person, email, mobile_number, mobile_number_2, website, area, category, job_role, timing, gender_preference, required_person_count, budget, expectation, follower_name, remarks, status, follow_up_1, follow_up_2, follow_up_3
                FROM clients';

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY id DESC';

        $stmt = db()->prepare($sql);
        $stmt->execute($params);

        self::streamCsv('clients_filtered_' . date('Ymd_His') . '.csv', $stmt->fetchAll());
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
                    tenant_name, reminder_type, reference_id, title, reminder_message, remind_at,
                    email_to, phone_to, notify_email, notify_sms, notify_web
                ) VALUES (
                    :tenant_name, :reminder_type, :reference_id, :title, :reminder_message, :remind_at,
                    :email_to, :phone_to, 1, 1, 1
                )'
            );

            $insert->execute([
                'tenant_name' => $_SESSION['tenant_name'] ?? '',
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
