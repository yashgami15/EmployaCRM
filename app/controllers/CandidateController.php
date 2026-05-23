<?php

declare(strict_types=1);

class CandidateController
{
    public static function dashboard(): void
    {
        require_permission('candidate');

        $user = current_user();
        $flash = get_flash();

        $search = trim((string) ($_GET['search'] ?? ''));
        $status = trim((string) ($_GET['status'] ?? ''));
        $source = trim((string) ($_GET['source'] ?? ''));

        $params = [];
        $whereSql = self::buildFilters($search, $status, $source, $params);

        $sql = 'SELECT *
                FROM candidates';

        if ($whereSql !== '') {
            $sql .= ' WHERE ' . $whereSql;
        }

        $sql .= ' ORDER BY DATE(added_on) DESC, id DESC';

        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $candidates = $stmt->fetchAll();

        $clients = db()->query('SELECT id, company_name, job_role, category, expectation, area, budget, status FROM clients ORDER BY id DESC')->fetchAll();

        foreach ($candidates as &$candidate) {
            $candidate['ai_matches'] = self::findBestMatches($candidate, $clients);
        }
        unset($candidate);

        $stats = self::stats();
        $statusOptions = status_options();

        $dbSources = db()->query('SELECT DISTINCT source FROM candidates ORDER BY source ASC')->fetchAll();
        $sourceOptions = source_options();

        foreach ($dbSources as $item) {
            $value = trim((string) ($item['source'] ?? ''));
            if ($value !== '' && !in_array($value, $sourceOptions, true)) {
                $sourceOptions[] = $value;
            }
        }

        $documentsOptions = documents_options();
        $timingOptions = client_timing_options();
        array_unshift($timingOptions, '');

        require BASE_PATH . '/app/views/dashboard/index.php';
    }

    public static function addCandidate(): void
    {
        require_auth();
        verify_csrf();

        if (!is_post()) {
            redirect_with_filters('candidate');
        }

        $input = self::prepareCandidateInput($_POST, $_FILES);
        set_old($_POST);

        if ($input['full_name'] === '' || $input['email_id'] === '') {
            set_flash('Full name and Email ID are required.', 'danger');
            redirect_with_filters('candidate');
        }

        if (!filter_var($input['email_id'], FILTER_VALIDATE_EMAIL)) {
            set_flash('Please enter a valid Email ID.', 'danger');
            redirect_with_filters('candidate');
        }

        // Duplicate Check (Case-insensitive)
        $checkStmt = db()->prepare('SELECT id FROM candidates WHERE (email_id != "" AND LOWER(email_id) = LOWER(:email)) OR (mobile_number != "" AND mobile_number = :phone) LIMIT 1');
        $checkStmt->execute(['email' => $input['email_id'], 'phone' => $input['mobile_number']]);
        if ($checkStmt->fetch()) {
            set_flash('Candidate already exists with this Email or Mobile Number.', 'danger');
            redirect_with_filters('candidate');
        }

        $insert = db()->prepare(
            'INSERT INTO candidates (
                tenant_name, full_name, email, phone, role, skills, status, source, added_on,
                email_address, mobile_number, email_id, date_of_birth, full_address,
                nearby_landmark, native_place, caste, father_occupation, mother_occupation,
                sibling_status, marital_status, ssc_details, hsc_diploma_details, graduate_details,
                post_graduate_details, experience_type, previous_company_city, previous_designation,
                previous_roles, previous_start_date, previous_end_date, previous_salary_month,
                current_company_city, current_designation, current_roles, current_start_date,
                current_salary_month, reason_for_change, skills_set, achievements,
                expected_salary_month, preferred_location, preferred_working_time,
                preferred_work_role_field, documents_have, additional_notes, resume_path
            ) VALUES (
                :tenant_name, :full_name, :main_email, :phone, :role, :main_skills, :status, :source, :added_on,
                :email_address, :mobile_number, :email_id, :date_of_birth, :full_address,
                :nearby_landmark, :native_place, :caste, :father_occupation, :mother_occupation,
                :sibling_status, :marital_status, :ssc_details, :hsc_diploma_details, :graduate_details,
                :post_graduate_details, :experience_type, :previous_company_city, :previous_designation,
                :previous_roles, :previous_start_date, :previous_end_date, :previous_salary_month,
                :current_company_city, :current_designation, :current_roles, :current_start_date,
                :current_salary_month, :reason_for_change, :skills_set, :achievements,
                :expected_salary_month, :preferred_location, :preferred_working_time,
                :preferred_work_role_field, :documents_have, :additional_notes, :resume_path
            )'
        );

        $insert->execute($input);
        $newId = (int) db()->lastInsertId();
        log_activity('candidate', $newId, 'Profile Created', 'New candidate profile added via form.');

        clear_old();
        set_flash('Candidate saved with full profile successfully.', 'success');
        redirect_with_filters('candidate');
    }

    public static function updateCandidate(): void
    {
        require_auth();
        verify_csrf();

        if (!is_post()) {
            redirect_with_filters('candidate');
        }

        $candidateId = (int) ($_POST['candidate_id'] ?? 0);

        if ($candidateId <= 0) {
            set_flash('Invalid candidate selected for update.', 'danger');
            redirect_with_filters('candidate');
        }

        $existingStmt = db()->prepare('SELECT resume_path FROM candidates WHERE id = :id LIMIT 1');
        $existingStmt->execute(['id' => $candidateId]);
        $existing = $existingStmt->fetch();

        if (!$existing) {
            set_flash('Candidate record was not found.', 'danger');
            redirect_with_filters('candidate');
        }

        $input = self::prepareCandidateInput($_POST, $_FILES);
        set_old($_POST);

        if ($input['full_name'] === '') {
            set_flash('Full name is required.', 'danger');
            redirect_with_filters('candidate');
        }

        if ($input['email_id'] !== '' && !filter_var($input['email_id'], FILTER_VALIDATE_EMAIL)) {
            set_flash('Please enter a valid Email ID.', 'danger');
            redirect_with_filters('candidate');
        }

        if ($input['resume_path'] === '') {
            $input['resume_path'] = (string) ($existing['resume_path'] ?? '');
        }

        $input['id'] = $candidateId;
        unset($input['tenant_name']);

        $update = db()->prepare(
            'UPDATE candidates SET
                full_name = :full_name,
                email = :main_email,
                phone = :phone,
                role = :role,
                skills = :main_skills,
                status = :status,
                source = :source,
                added_on = :added_on,
                email_address = :email_address,
                mobile_number = :mobile_number,
                email_id = :email_id,
                date_of_birth = :date_of_birth,
                full_address = :full_address,
                nearby_landmark = :nearby_landmark,
                native_place = :native_place,
                caste = :caste,
                father_occupation = :father_occupation,
                mother_occupation = :mother_occupation,
                sibling_status = :sibling_status,
                marital_status = :marital_status,
                ssc_details = :ssc_details,
                hsc_diploma_details = :hsc_diploma_details,
                graduate_details = :graduate_details,
                post_graduate_details = :post_graduate_details,
                experience_type = :experience_type,
                previous_company_city = :previous_company_city,
                previous_designation = :previous_designation,
                previous_roles = :previous_roles,
                previous_start_date = :previous_start_date,
                previous_end_date = :previous_end_date,
                previous_salary_month = :previous_salary_month,
                current_company_city = :current_company_city,
                current_designation = :current_designation,
                current_roles = :current_roles,
                current_start_date = :current_start_date,
                current_salary_month = :current_salary_month,
                reason_for_change = :reason_for_change,
                skills_set = :skills_set,
                achievements = :achievements,
                expected_salary_month = :expected_salary_month,
                preferred_location = :preferred_location,
                preferred_working_time = :preferred_working_time,
                preferred_work_role_field = :preferred_work_role_field,
                documents_have = :documents_have,
                additional_notes = :additional_notes,
                resume_path = :resume_path
             WHERE id = :id'
        );

        $update->execute($input);
        log_activity('candidate', $candidateId, 'Profile Updated', 'Candidate profile details were updated.');

        clear_old();
        set_flash('Candidate updated successfully.', 'success');
        redirect_with_filters('candidate');
    }

    public static function bulkStatus(): void
    {
        require_auth();
        verify_csrf();

        $ids = selected_ids_from_request();
        $status = self::normalizeStatus((string) ($_POST['bulk_status'] ?? ''));

        if (empty($ids)) {
            set_flash('Please select at least one candidate.', 'warning');
            redirect_with_filters('candidate');
        }

        if ($status === '') {
            set_flash('Please select a valid status.', 'warning');
            redirect_with_filters('candidate');
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = 'UPDATE candidates SET status = ? WHERE id IN (' . $placeholders . ')';

        $stmt = db()->prepare($sql);
        $stmt->execute(array_merge([$status], $ids));

        foreach ($ids as $id) {
            log_activity('candidate', (int) $id, 'Status Changed', 'Bulk status update: ' . $status);
        }

        set_flash('Updated status for selected candidates.', 'success');
        redirect_with_filters('candidate');
    }

    public static function deleteSelected(): void
    {
        require_auth();
        verify_csrf();

        $ids = selected_ids_from_request();

        if (empty($ids)) {
            set_flash('Please select candidates to delete.', 'warning');
            redirect_with_filters('candidate');
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $filesStmt = db()->prepare('SELECT resume_path FROM candidates WHERE id IN (' . $placeholders . ')');
        $filesStmt->execute($ids);
        $files = $filesStmt->fetchAll();

        foreach ($files as $fileRow) {
            $resume = trim((string) ($fileRow['resume_path'] ?? ''));
            if ($resume !== '') {
                $path = BASE_PATH . '/public/' . ltrim($resume, '/');
                if (is_file($path)) {
                    @unlink($path);
                }
            }
        }

        $sql = 'DELETE FROM candidates WHERE id IN (' . $placeholders . ')';
        $stmt = db()->prepare($sql);
        $stmt->execute($ids);

        set_flash('Deleted selected candidates.', 'success');
        redirect_with_filters('candidate');
    }

    public static function importCsv(): void
    {
        require_auth();
        verify_csrf();

        if (empty($_FILES['csv_file']['tmp_name']) || !is_uploaded_file($_FILES['csv_file']['tmp_name'])) {
            set_flash('Please select a CSV file to import.', 'warning');
            redirect_with_filters('candidate');
        }

        $handle = fopen($_FILES['csv_file']['tmp_name'], 'rb');

        if (!$handle) {
            set_flash('Unable to read uploaded CSV file.', 'danger');
            redirect_with_filters('candidate');
        }

        $first = fgetcsv($handle, 0, ',', '"', "\\");
        $rows = [];
        $errors = [];
        $lineNumber = 1;

        if ($first !== false) {
            // Skip Excel separator hint if present
            if (count($first) === 1 && stripos($first[0], 'sep=') === 0) {
                $first = fgetcsv($handle, 0, ',', '"', "\\");
                $lineNumber++;
            }

            if ($first !== false) {
                // Detect delimiter if not comma
                $delimiter = ',';
                if (count($first) === 1 && str_contains($first[0], ';')) {
                    $delimiter = ';';
                    $first = str_getcsv($first[0], ';');
                }

                // Clean BOM and lowercase headers
                $header = array_map(static function($value) {
                    $value = (string) $value;
                    // Remove UTF-8 BOM if present
                    if (strpos($value, "\xEF\xBB\xBF") === 0) {
                        $value = substr($value, 3);
                    }
                    return strtolower(trim($value));
                }, $first);
                
                while (($data = fgetcsv($handle, 0, $delimiter, '"', "\\")) !== false) {
                    $lineNumber++;
                    // Skip empty rows
                    if (empty(array_filter($data, fn($v) => trim((string)$v) !== ''))) {
                        continue;
                    }

                    $candidateData = self::rowToCandidate($data, $header);
                    
                    // Validation
                    $rowErrors = [];
                    if (empty($candidateData['full_name'])) {
                        $rowErrors[] = "Full Name is missing or column not found (found headers: " . implode(', ', $header) . ")";
                    }
                    if (empty($candidateData['mobile_number']) && empty($candidateData['phone'])) {
                        $rowErrors[] = "Mobile Number is missing or column not found";
                    } elseif (!empty($candidateData['mobile_number']) && !preg_match('/[0-9]{5,}/', $candidateData['mobile_number'])) {
                        $rowErrors[] = "Invalid Mobile Number format (needs at least 5 digits): '" . $candidateData['mobile_number'] . "'";
                    }
                    if ($candidateData['email_id'] !== '' && !filter_var($candidateData['email_id'], FILTER_VALIDATE_EMAIL)) {
                        $rowErrors[] = "Invalid email format: '" . $candidateData['email_id'] . "'";
                    }
                    
                    if (!empty($rowErrors)) {
                        $errors[] = "Row $lineNumber: " . implode(', ', $rowErrors);
                        continue;
                    }

                    $candidateData['_line'] = $lineNumber;
                    $rows[] = $candidateData;
                }
            }
        }

        fclose($handle);

        if (empty($rows) && empty($errors)) {
            set_flash('No valid data found in CSV.', 'warning');
            redirect_with_filters('candidate');
        }

        $imported = 0;
        $insert = db()->prepare(
            'INSERT INTO candidates (
                tenant_name, full_name, email, phone, role, skills, status, source, added_on,
                email_address, mobile_number, email_id, date_of_birth, full_address,
                nearby_landmark, native_place, caste, father_occupation, mother_occupation,
                sibling_status, marital_status, ssc_details, hsc_diploma_details,
                graduate_details, post_graduate_details, experience_type,
                previous_company_city, previous_designation, previous_roles,
                previous_start_date, previous_end_date, previous_salary_month,
                current_company_city, current_designation, current_roles,
                current_start_date, current_salary_month, reason_for_change,
                skills_set, achievements, expected_salary_month, preferred_location,
                preferred_working_time, preferred_work_role_field, documents_have,
                additional_notes
            ) VALUES (
                :tenant_name, :full_name, :main_email, :phone, :role, :main_skills, :status, :source, :added_on,
                :email_address, :mobile_number, :email_id, :date_of_birth, :full_address,
                :nearby_landmark, :native_place, :caste, :father_occupation, :mother_occupation,
                :sibling_status, :marital_status, :ssc_details, :hsc_diploma_details,
                :graduate_details, :post_graduate_details, :experience_type,
                :previous_company_city, :previous_designation, :previous_roles,
                :previous_start_date, :previous_end_date, :previous_salary_month,
                :current_company_city, :current_designation, :current_roles,
                :current_start_date, :current_salary_month, :reason_for_change,
                :skills_set, :achievements, :expected_salary_month, :preferred_location,
                :preferred_working_time, :preferred_work_role_field, :documents_have,
                :additional_notes
            )'
        );
        foreach ($rows as $item) {
            $currentLine = $item['_line'] ?? 'Unknown';
            unset($item['_line']);

            // Duplicate Check (Case-insensitive)
            // Use both email and phone for check
            $checkStmt = db()->prepare('SELECT id, full_name FROM candidates WHERE (email_id != "" AND LOWER(email_id) = LOWER(:email)) OR (mobile_number != "" AND mobile_number = :phone) LIMIT 1');
            $checkStmt->execute(['email' => $item['email_id'], 'phone' => $item['mobile_number']]);
            $dup = $checkStmt->fetch();
            if ($dup) {
                $errors[] = 'Row ' . $currentLine . ': Duplicate found - Candidate "' . $dup['full_name'] . '" already exists with Email: ' . $item['email_id'] . ' or Phone: ' . $item['mobile_number'];
                continue;
            }

            try {
                $insert->execute($item);
                $imported++;
            } catch (Exception $e) {
                $errors[] = 'Row ' . $currentLine . ': Database error - ' . $e->getMessage();
            }
        }

        if (!empty($errors)) {
            $errorMsg = 'Imported ' . $imported . ' candidates. Errors found in ' . count($errors) . ' rows:<br><div class="import-errors" style="max-height: 200px; overflow-y: auto;">' . implode('<br>', $errors) . '</div>';
            set_flash($errorMsg, 'danger');
        } else {
            set_flash('Successfully imported ' . $imported . ' candidates from CSV.', 'success');
        }
        redirect_with_filters('candidate');
    }

    public static function addReminder(): void
    {
        require_auth();
        verify_csrf();

        $candidateId = (int) ($_POST['candidate_id'] ?? 0);
        $title = trim((string) ($_POST['title'] ?? 'Candidate Follow-up Reminder'));
        $remindAt = normalize_datetime_input((string) ($_POST['remind_at'] ?? ''));
        $message = trim((string) ($_POST['reminder_message'] ?? ''));
        $emailTo = trim((string) ($_POST['email_to'] ?? ''));
        $phoneTo = trim((string) ($_POST['phone_to'] ?? ''));
        $notifyEmail = !empty($_POST['notify_email']) ? 1 : 0;
        $notifySms = !empty($_POST['notify_sms']) ? 1 : 0;
        $notifyWeb = !empty($_POST['notify_web']) ? 1 : 0;

        if ($candidateId <= 0 || $remindAt === '') {
            set_flash('Please select valid candidate reminder details.', 'danger');
            redirect_with_filters('candidate');
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
            'reminder_type' => 'candidate',
            'reference_id' => $candidateId,
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
        redirect_with_filters('candidate');
    }

    public static function exportFiltered(): void
    {
        require_auth();

        $search = trim((string) ($_GET['search'] ?? ''));
        $status = trim((string) ($_GET['status'] ?? ''));
        $source = trim((string) ($_GET['source'] ?? ''));

        $params = [];
        $whereSql = self::buildFilters($search, $status, $source, $params);

        $sql = 'SELECT full_name, mobile_number, email_id, date_of_birth, full_address, nearby_landmark, native_place, caste, father_occupation, mother_occupation, sibling_status, marital_status, ssc_details, hsc_diploma_details, graduate_details, post_graduate_details, experience_type, previous_company_city, previous_designation, previous_roles, previous_start_date, previous_end_date, previous_salary_month, current_company_city, current_designation, current_roles, current_start_date, current_salary_month, reason_for_change, skills_set, achievements, expected_salary_month, preferred_location, preferred_working_time, preferred_work_role_field, documents_have, additional_notes, status, source, added_on
                FROM candidates';

        if ($whereSql !== '') {
            $sql .= ' WHERE ' . $whereSql;
        }

        $sql .= ' ORDER BY DATE(added_on) DESC, id DESC';

        $stmt = db()->prepare($sql);
        $stmt->execute($params);

        self::streamCsv('candidates_filtered_' . date('Ymd_His') . '.csv', $stmt->fetchAll());
    }

    public static function exportSelected(): void
    {
        require_auth();

        $ids = selected_ids_from_request();

        if (empty($ids)) {
            set_flash('Please select candidates before exporting selected.', 'warning');
            redirect_with_filters('candidate');
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = 'SELECT full_name, mobile_number, email_id, date_of_birth, full_address, nearby_landmark, native_place, caste, father_occupation, mother_occupation, sibling_status, marital_status, ssc_details, hsc_diploma_details, graduate_details, post_graduate_details, experience_type, previous_company_city, previous_designation, previous_roles, previous_start_date, previous_end_date, previous_salary_month, current_company_city, current_designation, current_roles, current_start_date, current_salary_month, reason_for_change, skills_set, achievements, expected_salary_month, preferred_location, preferred_working_time, preferred_work_role_field, documents_have, additional_notes, status, source, added_on
                FROM candidates
                WHERE id IN (' . $placeholders . ')
                ORDER BY DATE(added_on) DESC, id DESC';

        $stmt = db()->prepare($sql);
        $stmt->execute($ids);

        self::streamCsv('candidates_selected_' . date('Ymd_His') . '.csv', $stmt->fetchAll());
    }

    private static function stats(): array
    {
        $stats = [
            'total' => 0,
            'applied' => 0,
            'interview' => 0,
            'hired' => 0,
            'rejected' => 0,
        ];

        $companyFilter = (($_SESSION['role'] ?? 'user') !== 'admin') ? "WHERE tenant_name = '" . ($_SESSION['tenant_name'] ?? '') . "'" : "";
        $total = db()->query("SELECT COUNT(*) AS count FROM candidates $companyFilter")->fetch();
        $stats['total'] = (int) ($total['count'] ?? 0);

        $statusCounts = db()->query("SELECT status, COUNT(*) AS count FROM candidates $companyFilter GROUP BY status")->fetchAll();

        foreach ($statusCounts as $row) {
            $key = strtolower((string) ($row['status'] ?? ''));

            if (array_key_exists($key, $stats)) {
                $stats[$key] = (int) ($row['count'] ?? 0);
            }
        }

        return $stats;
    }

    private static function buildFilters(string $search, string $status, string $source, array &$params): string
    {
        $where = [];

        if (($_SESSION['role'] ?? 'user') !== 'admin') {
            $where[] = 'tenant_name = :tenant_name';
            $params['tenant_name'] = $_SESSION['tenant_name'] ?? '';
        }

        if ($search !== '') {
            $where[] = '(full_name LIKE :search OR email_id LIKE :search OR mobile_number LIKE :search OR preferred_work_role_field LIKE :search OR skills_set LIKE :search OR preferred_location LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        if ($status !== '' && in_array($status, status_options(), true)) {
            $where[] = 'status = :status';
            $params['status'] = $status;
        }

        if ($source !== '') {
            $where[] = 'source = :source';
            $params['source'] = $source;
        }

        return implode(' AND ', $where);
    }

    private static function normalizeStatus(string $status): string
    {
        return match_option_case_insensitive($status, status_options(), 'Applied');
    }

    private static function normalizeSource(string $source): string
    {
        return match_option_case_insensitive($source, source_options(), 'Direct');
    }

    private static function prepareCandidateInput(array $post, array $files): array
    {
        $documents = $post['documents_have'] ?? [];
        if (!is_array($documents)) {
            $documents = [];
        }

        $fullName = trim((string) ($post['full_name'] ?? ''));
        $emailId = strtolower(trim((string) ($post['email_id'] ?? $post['email'] ?? '')));
        $mobile = trim((string) ($post['mobile_number'] ?? $post['phone'] ?? ''));
        $skillsSet = trim((string) ($post['skills_set'] ?? $post['skills'] ?? ''));
        $preferredRole = trim((string) ($post['preferred_work_role_field'] ?? $post['role'] ?? ''));
        $addedOnRaw = trim((string) ($post['added_on'] ?? date('Y-m-d')));
        $addedOn = preg_match('/^\d{4}-\d{2}-\d{2}$/', $addedOnRaw) ? $addedOnRaw : date('Y-m-d');

        $resumePath = self::uploadResume($files['resume_file'] ?? null);

        return [
            'tenant_name' => $_SESSION['tenant_name'] ?? '',
            'full_name' => $fullName,
            'main_email' => $emailId,
            'phone' => $mobile,
            'role' => $preferredRole,
            'main_skills' => $skillsSet,
            'status' => self::normalizeStatus((string) ($post['status'] ?? 'Applied')) ?: 'Applied',
            'source' => self::normalizeSource((string) ($post['source'] ?? 'Direct')),
            'added_on' => $addedOn,
            'email_address' => trim((string) ($post['email_address'] ?? $emailId)),
            'mobile_number' => $mobile,
            'email_id' => $emailId,
            'date_of_birth' => trim((string) ($post['date_of_birth'] ?? '')),
            'full_address' => trim((string) ($post['full_address'] ?? '')),
            'nearby_landmark' => trim((string) ($post['nearby_landmark'] ?? '')),
            'native_place' => trim((string) ($post['native_place'] ?? '')),
            'caste' => trim((string) ($post['caste'] ?? '')),
            'father_occupation' => trim((string) ($post['father_occupation'] ?? '')),
            'mother_occupation' => trim((string) ($post['mother_occupation'] ?? '')),
            'sibling_status' => trim((string) ($post['sibling_status'] ?? '')),
            'marital_status' => trim((string) ($post['marital_status'] ?? '')),
            'ssc_details' => trim((string) ($post['ssc_details'] ?? '')),
            'hsc_diploma_details' => trim((string) ($post['hsc_diploma_details'] ?? '')),
            'graduate_details' => trim((string) ($post['graduate_details'] ?? '')),
            'post_graduate_details' => trim((string) ($post['post_graduate_details'] ?? '')),
            'experience_type' => trim((string) ($post['experience_type'] ?? 'Fresher')),
            'previous_company_city' => trim((string) ($post['previous_company_city'] ?? '')),
            'previous_designation' => trim((string) ($post['previous_designation'] ?? '')),
            'previous_roles' => trim((string) ($post['previous_roles'] ?? '')),
            'previous_start_date' => trim((string) ($post['previous_start_date'] ?? '')),
            'previous_end_date' => trim((string) ($post['previous_end_date'] ?? '')),
            'previous_salary_month' => trim((string) ($post['previous_salary_month'] ?? '')),
            'current_company_city' => trim((string) ($post['current_company_city'] ?? '')),
            'current_designation' => trim((string) ($post['current_designation'] ?? '')),
            'current_roles' => trim((string) ($post['current_roles'] ?? '')),
            'current_start_date' => trim((string) ($post['current_start_date'] ?? '')),
            'current_salary_month' => trim((string) ($post['current_salary_month'] ?? '')),
            'reason_for_change' => trim((string) ($post['reason_for_change'] ?? '')),
            'skills_set' => $skillsSet,
            'achievements' => trim((string) ($post['achievements'] ?? '')),
            'expected_salary_month' => trim((string) ($post['expected_salary_month'] ?? '')),
            'preferred_location' => trim((string) ($post['preferred_location'] ?? '')),
            'preferred_working_time' => trim((string) ($post['preferred_working_time'] ?? '')),
            'preferred_work_role_field' => $preferredRole,
            'documents_have' => implode(', ', array_map('trim', $documents)),
            'additional_notes' => trim((string) ($post['additional_notes'] ?? '')),
            'resume_path' => $resumePath,
        ];
    }

    private static function uploadResume(?array $file): string
    {
        if (!$file || empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return '';
        }

        $allowed = ['pdf', 'doc', 'docx'];
        $name = (string) ($file['name'] ?? '');
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed, true)) {
            return '';
        }

        $directory = BASE_PATH . '/public/uploads/resumes';

        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $safeName = preg_replace('/[^a-zA-Z0-9._-]+/', '_', pathinfo($name, PATHINFO_FILENAME)) ?: 'resume';
        $filename = time() . '_' . bin2hex(random_bytes(3)) . '_' . $safeName . '.' . $ext;
        $destination = $directory . '/' . $filename;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return 'uploads/resumes/' . $filename;
        }

        return '';
    }

    private static function rowToCandidate(array $row, array $header): array
    {
        $fields = [
            'full_name', 'mobile_number', 'email_id', 'date_of_birth', 'full_address',
            'nearby_landmark', 'native_place', 'caste', 'father_occupation', 'mother_occupation',
            'sibling_status', 'marital_status', 'ssc_details', 'hsc_diploma_details', 'graduate_details',
            'post_graduate_details', 'experience_type', 'previous_company_city', 'previous_designation',
            'previous_roles', 'previous_start_date', 'previous_end_date', 'previous_salary_month',
            'current_company_city', 'current_designation', 'current_roles', 'current_start_date',
            'current_salary_month', 'reason_for_change', 'skills_set', 'achievements',
            'expected_salary_month', 'preferred_location', 'preferred_working_time',
            'preferred_work_role_field', 'documents_have', 'additional_notes', 'status', 'source', 'added_on'
        ];

        $data = array_fill_keys($fields, '');

        if (!empty($header)) {
            foreach ($header as $index => $column) {
                if ($column === 'name' || $column === 'full name' || $column === 'candidate name' || $column === 'candidate_name' || $column === 'full_name') $column = 'full_name';
                if ($column === 'email' || $column === 'email id' || $column === 'email address' || $column === 'email_address' || $column === 'email_id') {
                    // Both go to email_id and email_address for redundancy
                    $data['email_id'] = trim((string)($row[$index] ?? ''));
                    $data['email_address'] = trim((string)($row[$index] ?? ''));
                    continue; 
                }
                if ($column === 'mobile' || $column === 'phone' || $column === 'mobile num' || $column === 'mobile number' || $column === 'contact' || $column === 'contact number' || $column === 'mobile_number') {
                    $data['mobile_number'] = trim((string)($row[$index] ?? ''));
                    $data['phone'] = trim((string)($row[$index] ?? ''));
                    continue;
                }
                if ($column === 'role' || $column === 'job role' || $column === 'designation' || $column === 'preferred role' || $column === 'preferred_work_role_field') $column = 'preferred_work_role_field';
                if ($column === 'skills' || $column === 'skills set' || $column === 'skills_set') $column = 'skills_set';
                if ($column === 'location' || $column === 'area' || $column === 'preferred location' || $column === 'preferred_location') $column = 'preferred_location';
                if ($column === 'salary' || $column === 'expected salary' || $column === 'expected_salary_month') $column = 'expected_salary_month';
                if ($column === 'experience' || $column === 'exp' || $column === 'experience type' || $column === 'experience_type') $column = 'experience_type';
                if ($column === 'dob' || $column === 'birth date' || $column === 'date of birth' || $column === 'date_of_birth') $column = 'date_of_birth';
                if ($column === 'address' || $column === 'full address' || $column === 'full_address') $column = 'full_address';
                if ($column === 'marital status' || $column === 'marital' || $column === 'marital_status') $column = 'marital_status';
                if ($column === 'landmark' || $column === 'nearby landmark' || $column === 'nearby_landmark') $column = 'nearby_landmark';
                if ($column === 'native' || $column === 'native place' || $column === 'native_place') $column = 'native_place';
                if ($column === 'caste' || $column === 'category') $column = 'caste';

                if ($column === 'ssc' || $column === '10th' || $column === '10th details' || $column === 'ssc_details') $column = 'ssc_details';
                if ($column === 'hsc' || $column === '12th' || $column === '12th details' || $column === 'diploma' || $column === 'hsc_diploma_details') $column = 'hsc_diploma_details';
                if ($column === 'graduate' || $column === 'degree' || $column === 'graduation' || $column === 'graduate_details') $column = 'graduate_details';
                if ($column === 'post graduate' || $column === 'pg' || $column === 'masters' || $column === 'post_graduate_details') $column = 'post_graduate_details';
                
                if ($column === 'current company' || $column === 'present company' || $column === 'current company name' || $column === 'current company name & city' || $column === 'current_company_city') $column = 'current_company_city';
                if ($column === 'current designation' || $column === 'present designation' || $column === 'current_designation') $column = 'current_designation';
                if ($column === 'current roles' || $column === 'present roles' || $column === 'current_roles') $column = 'current_roles';
                if ($column === 'current salary' || $column === 'present salary' || $column === 'current_salary_month') $column = 'current_salary_month';
                if ($column === 'previous company' || $column === 'past company' || $column === 'previous company name' || $column === 'previous company name & city' || $column === 'previous_company_city') $column = 'previous_company_city';
                if ($column === 'previous designation' || $column === 'past designation' || $column === 'previous_designation') $column = 'previous_designation';
                if ($column === 'previous roles' || $column === 'past roles' || $column === 'previous_roles') $column = 'previous_roles';
                if ($column === 'previous salary' || $column === 'past salary' || $column === 'previous_salary_month') $column = 'previous_salary_month';
                if ($column === 'reason' || $column === 'reason for change' || $column === 'reason_for_change') $column = 'reason_for_change';
                
                if ($column === 'expected salary' || $column === 'exp salary' || $column === 'expected_salary_month') $column = 'expected_salary_month';
                if ($column === 'additional notes' || $column === 'notes' || $column === 'remark' || $column === 'remarks' || $column === 'comment' || $column === 'comments' || $column === 'additional_notes') $column = 'additional_notes';
                if ($column === 'status' || $column === 'candidate status' || $column === 'current status') $column = 'status';
                if ($column === 'source' || $column === 'lead source' || $column === 'candidate source') $column = 'source';
                if ($column === 'timing' || $column === 'working time' || $column === 'preferred timing' || $column === 'preferred_working_time') $column = 'preferred_working_time';
                if ($column === 'documents' || $column === 'docs' || $column === 'documents have' || $column === 'documents_have') $column = 'documents_have';

                $val = trim((string)($row[$index] ?? ''));
                
                // Handle Excel scientific notation (e.g., 9.20E+11)
                if (preg_match('/^[+-]?[0-9]*\.?[0-9]+[eE][+-]?[0-9]+$/', $val)) {
                    $val = sprintf("%.0f", (float)$val);
                }

                if (array_key_exists($column, $data)) {
                    $data[$column] = $val;
                }
            }
        } else {
            // Fallback for no header
            $data['full_name'] = trim((string)($row[0] ?? ''));
            $data['email_id'] = trim((string)($row[1] ?? ''));
            $data['mobile_number'] = trim((string)($row[2] ?? ''));
        }

        $addedOn = preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['added_on']) ? $data['added_on'] : date('Y-m-d');
        
        return [
            'full_name' => $data['full_name'],
            'main_email' => $data['email_id'],
            'phone' => $data['mobile_number'],
            'role' => $data['preferred_work_role_field'],
            'main_skills' => $data['skills_set'],
            'status' => self::normalizeStatus($data['status']) ?: 'Applied',
            'source' => self::normalizeSource($data['source']),
            'added_on' => $addedOn,
            'email_address' => $data['email_id'],
            'mobile_number' => $data['mobile_number'],
            'email_id' => $data['email_id'],
            'date_of_birth' => $data['date_of_birth'],
            'full_address' => $data['full_address'],
            'nearby_landmark' => $data['nearby_landmark'],
            'native_place' => $data['native_place'],
            'caste' => $data['caste'],
            'father_occupation' => $data['father_occupation'],
            'mother_occupation' => $data['mother_occupation'],
            'sibling_status' => $data['sibling_status'],
            'marital_status' => match_option_case_insensitive($data['marital_status'], marital_status_options()),
            'ssc_details' => $data['ssc_details'],
            'hsc_diploma_details' => $data['hsc_diploma_details'],
            'graduate_details' => $data['graduate_details'],
            'post_graduate_details' => $data['post_graduate_details'],
            'experience_type' => (function($val) {
                $val = trim((string)$val);
                if (strcasecmp($val, 'Experience') === 0) return 'Experienced';
                return match_option_case_insensitive($val, experience_type_options(), 'Fresher');
            })($data['experience_type']),
            'previous_company_city' => $data['previous_company_city'],
            'previous_designation' => $data['previous_designation'],
            'previous_roles' => $data['previous_roles'],
            'previous_start_date' => $data['previous_start_date'] ?: null,
            'previous_end_date' => $data['previous_end_date'] ?: null,
            'previous_salary_month' => $data['previous_salary_month'],
            'current_company_city' => $data['current_company_city'],
            'current_designation' => $data['current_designation'],
            'current_roles' => $data['current_roles'],
            'current_start_date' => $data['current_start_date'] ?: null,
            'current_salary_month' => $data['current_salary_month'],
            'reason_for_change' => $data['reason_for_change'],
            'skills_set' => $data['skills_set'],
            'achievements' => $data['achievements'],
            'expected_salary_month' => $data['expected_salary_month'],
            'preferred_location' => $data['preferred_location'],
            'preferred_working_time' => match_option_case_insensitive($data['preferred_working_time'], client_timing_options()),
            'preferred_work_role_field' => $data['preferred_work_role_field'],
            'documents_have' => $data['documents_have'],
            'additional_notes' => $data['additional_notes']
        ];
    }

    private static function findBestMatches(array $candidate, array $clients): array
    {
        $matches = [];

        foreach ($clients as $client) {
            if (strtolower((string) ($client['status'] ?? '')) === 'closed') {
                continue;
            }

            $score = match_score($candidate, $client);

            if ($score <= 0) {
                continue;
            }

            $matches[] = [
                'company_name' => (string) ($client['company_name'] ?? ''),
                'job_role' => (string) ($client['job_role'] ?? ''),
                'score' => $score,
                'budget' => (string) ($client['budget'] ?? ''),
            ];
        }

        usort($matches, static fn($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($matches, 0, 3);
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

    public static function parseResumeAjax(): void
    {
        // Suppress warnings/notices so they don't break the JSON output
        error_reporting(0);
        ini_set('display_errors', '0');

        require_auth();
        header('Content-Type: application/json');

        if (!isset($_FILES['resume']) || $_FILES['resume']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['status' => 'error', 'message' => 'No file uploaded or upload error.']);
            exit;
        }

        $tmpFile = $_FILES['resume']['tmp_name'];
        $mimeType = function_exists('mime_content_type') ? mime_content_type($tmpFile) : $_FILES['resume']['type'];

        if ($mimeType !== 'application/pdf') {
            echo json_encode(['status' => 'error', 'message' => 'Please upload a PDF file for AI parsing.']);
            exit;
        }

        if (!function_exists('curl_init')) {
            echo json_encode(['status' => 'error', 'message' => 'cURL is not installed on this server. Please contact hosting support.']);
            exit;
        }

        // Get API Key from current user's company
        $tenantName = $_SESSION['tenant_name'] ?? '';
        $stmt = db()->prepare('SELECT gemini_api_key FROM users WHERE tenant_name = :tenant AND gemini_api_key IS NOT NULL AND gemini_api_key != "" LIMIT 1');
        $stmt->execute(['tenant' => $tenantName]);
        $apiKeyRow = $stmt->fetch();

        if (!$apiKeyRow || empty($apiKeyRow['gemini_api_key'])) {
            echo json_encode(['status' => 'error', 'message' => 'Gemini API Key is missing. Please ask your Admin to configure the AI API Key in the Settings/Admin panel.']);
            exit;
        }

        $apiKey = trim($apiKeyRow['gemini_api_key']);
        $base64Data = base64_encode(file_get_contents($tmpFile));

        $payload = [
            "contents" => [
                [
                    "parts" => [
                        [
                            "text" => "Extract candidate details from this resume into JSON. Format the output as a valid JSON object ONLY. Keys must be exactly: full_name, email_address, mobile_number, preferred_work_role_field (string, guessed job role), skills_set (comma separated string), current_company_city (string), current_designation (string), expected_salary_month (numeric string), experience_type (choose 'Fresher' or 'Experienced'). Return ONLY the JSON object, no markdown, no backticks."
                        ],
                        [
                            "inlineData" => [
                                "mimeType" => "application/pdf",
                                "data" => $base64Data
                            ]
                        ]
                    ]
                ]
            ],
            "generationConfig" => [
                "temperature" => 0.2,
                "responseMimeType" => "application/json"
            ]
        ];

        $ch = curl_init("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent?key=" . $apiKey);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Fix for hostinger SSL CA issues
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        // Removed curl_close($ch) because it is deprecated in PHP 8.4+ and causes a warning

        if ($response === false) {
            echo json_encode(['status' => 'error', 'message' => 'Failed to connect to Google API: ' . $curlError]);
            exit;
        }

        if ($httpCode !== 200) {
            $errData = json_decode((string)$response, true);
            $msg = $errData['error']['message'] ?? 'API responded with HTTP ' . $httpCode;
            echo json_encode(['status' => 'error', 'message' => 'Google AI Error: ' . $msg]);
            exit;
        }

        $data = json_decode($response, true);
        $extractedText = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

        if (empty($extractedText)) {
            echo json_encode(['status' => 'error', 'message' => 'AI failed to extract data from this resume.']);
            exit;
        }

        // Cleanup potential markdown wrappers from Gemini
        $extractedText = preg_replace('/```json/i', '', $extractedText);
        $extractedText = preg_replace('/```/i', '', $extractedText);
        $parsedJson = json_decode(trim($extractedText, " \t\n\r\0\x0B`"), true);

        if (!$parsedJson) {
            echo json_encode(['status' => 'error', 'message' => 'AI returned invalid JSON format. Raw output: ' . substr($extractedText, 0, 50)]);
            exit;
        }

        echo json_encode(['status' => 'success', 'data' => $parsedJson]);
        exit;
    }
}
