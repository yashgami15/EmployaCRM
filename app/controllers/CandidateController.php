<?php

declare(strict_types=1);

class CandidateController
{
    public static function dashboard(): void
    {
        require_auth();

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

        require BASE_PATH . '/app/views/dashboard/index.php';
    }

    public static function addCandidate(): void
    {
        require_auth();
        verify_csrf();

        if (!is_post()) {
            redirect('index.php?action=candidate');
        }

        $input = self::prepareCandidateInput($_POST, $_FILES);
        set_old($_POST);

        if ($input['full_name'] === '' || $input['email_id'] === '') {
            set_flash('Full name and Email ID are required.', 'danger');
            redirect('index.php?action=candidate');
        }

        if (!filter_var($input['email_id'], FILTER_VALIDATE_EMAIL)) {
            set_flash('Please enter a valid Email ID.', 'danger');
            redirect('index.php?action=candidate');
        }

        $insert = db()->prepare(
            'INSERT INTO candidates (
                full_name, email, phone, role, skills, status, source, added_on,
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
                :full_name, :email, :phone, :role, :skills, :status, :source, :added_on,
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

        clear_old();
        set_flash('Candidate saved with full profile successfully.', 'success');
        redirect('index.php?action=candidate');
    }

    public static function updateCandidate(): void
    {
        require_auth();
        verify_csrf();

        if (!is_post()) {
            redirect('index.php?action=candidate');
        }

        $candidateId = (int) ($_POST['candidate_id'] ?? 0);

        if ($candidateId <= 0) {
            set_flash('Invalid candidate selected for update.', 'danger');
            redirect('index.php?action=candidate');
        }

        $existingStmt = db()->prepare('SELECT resume_path FROM candidates WHERE id = :id LIMIT 1');
        $existingStmt->execute(['id' => $candidateId]);
        $existing = $existingStmt->fetch();

        if (!$existing) {
            set_flash('Candidate record was not found.', 'danger');
            redirect('index.php?action=candidate');
        }

        $input = self::prepareCandidateInput($_POST, $_FILES);
        set_old($_POST);

        if ($input['full_name'] === '' || $input['email_id'] === '') {
            set_flash('Full name and Email ID are required.', 'danger');
            redirect('index.php?action=candidate');
        }

        if (!filter_var($input['email_id'], FILTER_VALIDATE_EMAIL)) {
            set_flash('Please enter a valid Email ID.', 'danger');
            redirect('index.php?action=candidate');
        }

        if ($input['resume_path'] === '') {
            $input['resume_path'] = (string) ($existing['resume_path'] ?? '');
        }

        $input['id'] = $candidateId;

        $update = db()->prepare(
            'UPDATE candidates SET
                full_name = :full_name,
                email = :email,
                phone = :phone,
                role = :role,
                skills = :skills,
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

        clear_old();
        set_flash('Candidate updated successfully.', 'success');
        redirect('index.php?action=candidate');
    }

    public static function bulkStatus(): void
    {
        require_auth();
        verify_csrf();

        $ids = selected_ids_from_request();
        $status = self::normalizeStatus((string) ($_POST['bulk_status'] ?? ''));

        if (empty($ids)) {
            set_flash('Please select at least one candidate.', 'warning');
            redirect('index.php?action=candidate');
        }

        if ($status === '') {
            set_flash('Please select a valid status.', 'warning');
            redirect('index.php?action=candidate');
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = 'UPDATE candidates SET status = ? WHERE id IN (' . $placeholders . ')';

        $stmt = db()->prepare($sql);
        $stmt->execute(array_merge([$status], $ids));

        set_flash('Updated status for selected candidates.', 'success');
        redirect('index.php?action=candidate');
    }

    public static function deleteSelected(): void
    {
        require_auth();
        verify_csrf();

        $ids = selected_ids_from_request();

        if (empty($ids)) {
            set_flash('Please select candidates to delete.', 'warning');
            redirect('index.php?action=candidate');
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
        redirect('index.php?action=candidate');
    }

    public static function importCsv(): void
    {
        require_auth();
        verify_csrf();

        if (empty($_FILES['csv_file']['tmp_name']) || !is_uploaded_file($_FILES['csv_file']['tmp_name'])) {
            set_flash('Please select a CSV file to import.', 'warning');
            redirect('index.php?action=candidate');
        }

        $handle = fopen($_FILES['csv_file']['tmp_name'], 'rb');

        if (!$handle) {
            set_flash('Unable to read uploaded CSV file.', 'danger');
            redirect('index.php?action=candidate');
        }

        $first = fgetcsv($handle);
        $rows = [];

        if ($first !== false) {
            $header = array_map(static fn($value) => strtolower(trim((string) $value)), $first);
            $headerLooksValid = in_array('full_name', $header, true) || in_array('name', $header, true);

            if ($headerLooksValid) {
                while (($data = fgetcsv($handle)) !== false) {
                    $rows[] = self::rowToCandidate($data, $header);
                }
            } else {
                $rows[] = self::rowToCandidate($first, []);
                while (($data = fgetcsv($handle)) !== false) {
                    $rows[] = self::rowToCandidate($data, []);
                }
            }
        }

        fclose($handle);

        $insert = db()->prepare(
            'INSERT INTO candidates (
                full_name, email, phone, role, skills, status, source, added_on,
                email_address, mobile_number, email_id, skills_set, preferred_work_role_field, preferred_location,
                experience_type
            ) VALUES (
                :full_name, :email, :phone, :role, :skills, :status, :source, :added_on,
                :email_address, :mobile_number, :email_id, :skills_set, :preferred_work_role_field, :preferred_location,
                :experience_type
            )'
        );

        $imported = 0;

        foreach ($rows as $item) {
            if ($item['full_name'] === '' || !filter_var($item['email_id'], FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $insert->execute($item);
            $imported++;
        }

        set_flash('Imported ' . $imported . ' candidates from CSV.', 'success');
        redirect('index.php?action=candidate');
    }

    public static function exportFiltered(): void
    {
        require_auth();

        $search = trim((string) ($_GET['search'] ?? ''));
        $status = trim((string) ($_GET['status'] ?? ''));
        $source = trim((string) ($_GET['source'] ?? ''));

        $params = [];
        $whereSql = self::buildFilters($search, $status, $source, $params);

        $sql = 'SELECT full_name, mobile_number, email_id, date_of_birth, preferred_work_role_field,
                       skills_set, preferred_location, experience_type, status, source, added_on
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
            redirect('index.php?action=candidate');
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = 'SELECT full_name, mobile_number, email_id, date_of_birth, preferred_work_role_field,
                       skills_set, preferred_location, experience_type, status, source, added_on
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
        ];

        $total = db()->query('SELECT COUNT(*) AS count FROM candidates')->fetch();
        $stats['total'] = (int) ($total['count'] ?? 0);

        $statusCounts = db()->query('SELECT status, COUNT(*) AS count FROM candidates GROUP BY status')->fetchAll();

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
        $status = trim($status);

        if ($status === '') {
            return '';
        }

        return in_array($status, status_options(), true) ? $status : 'Applied';
    }

    private static function normalizeSource(string $source): string
    {
        $source = trim($source);

        if ($source === '') {
            return 'Direct';
        }

        return $source;
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
            'full_name' => $fullName,
            'email' => $emailId,
            'phone' => $mobile,
            'role' => $preferredRole,
            'skills' => $skillsSet,
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
        $defaultMap = [
            'full_name' => trim((string) ($row[0] ?? '')),
            'email_id' => strtolower(trim((string) ($row[1] ?? ''))),
            'mobile_number' => trim((string) ($row[2] ?? '')),
            'preferred_work_role_field' => trim((string) ($row[3] ?? '')),
            'skills_set' => trim((string) ($row[4] ?? '')),
            'status' => trim((string) ($row[5] ?? 'Applied')),
            'source' => trim((string) ($row[6] ?? 'Direct')),
            'added_on' => trim((string) ($row[7] ?? date('Y-m-d'))),
            'preferred_location' => trim((string) ($row[8] ?? '')),
            'experience_type' => trim((string) ($row[9] ?? 'Fresher')),
        ];

        if (!empty($header)) {
            foreach ($header as $index => $column) {
                $value = trim((string) ($row[$index] ?? ''));

                if ($column === 'name') {
                    $column = 'full_name';
                }

                if ($column === 'email') {
                    $column = 'email_id';
                }

                if (array_key_exists($column, $defaultMap)) {
                    $defaultMap[$column] = $value;
                }
            }
        }

        $addedOn = preg_match('/^\d{4}-\d{2}-\d{2}$/', $defaultMap['added_on']) ? $defaultMap['added_on'] : date('Y-m-d');

        return [
            'full_name' => $defaultMap['full_name'],
            'email' => $defaultMap['email_id'],
            'phone' => $defaultMap['mobile_number'],
            'role' => $defaultMap['preferred_work_role_field'],
            'skills' => $defaultMap['skills_set'],
            'status' => self::normalizeStatus($defaultMap['status']) ?: 'Applied',
            'source' => self::normalizeSource($defaultMap['source']),
            'added_on' => $addedOn,
            'email_address' => $defaultMap['email_id'],
            'mobile_number' => $defaultMap['mobile_number'],
            'email_id' => $defaultMap['email_id'],
            'skills_set' => $defaultMap['skills_set'],
            'preferred_work_role_field' => $defaultMap['preferred_work_role_field'],
            'preferred_location' => $defaultMap['preferred_location'],
            'experience_type' => $defaultMap['experience_type'] !== '' ? $defaultMap['experience_type'] : 'Fresher',
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
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        $output = fopen('php://output', 'wb');

        fputcsv($output, ['Full Name', 'Mobile Number', 'Email ID', 'Date Of Birth', 'Preferred Role', 'Skills', 'Preferred Location', 'Experience Type', 'Status', 'Source', 'Added On']);

        foreach ($rows as $row) {
            fputcsv($output, [
                $row['full_name'] ?? '',
                $row['mobile_number'] ?? '',
                $row['email_id'] ?? '',
                $row['date_of_birth'] ?? '',
                $row['preferred_work_role_field'] ?? '',
                $row['skills_set'] ?? '',
                $row['preferred_location'] ?? '',
                $row['experience_type'] ?? '',
                $row['status'] ?? '',
                $row['source'] ?? '',
                $row['added_on'] ?? '',
            ]);
        }

        fclose($output);
        exit;
    }
}
