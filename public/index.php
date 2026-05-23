<?php

declare(strict_types=1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require dirname(__DIR__) . '/app/bootstrap.php';

$action = (string) ($_GET['action'] ?? '');

switch ($action) {
    case 'dashboard':
    case 'candidate':
        CandidateController::dashboard();
        break;

    case 'home':
        HomeController::index();
        break;

    case 'clients':
        ClientController::index();
        break;

    case 'interviews':
        InterviewController::index();
        break;

    case 'ai_matcher':
        AiMatcherController::index();
        break;

    case 'settings':
        SettingsController::index();
        break;

    case 'save_settings':
        if (!is_post()) {
            redirect('index.php?action=settings');
        }
        SettingsController::save();
        break;

    case 'admin':
        AdminController::index();
        break;

    case 'admin_tracking':
        AdminController::tracking();
        break;

    case 'admin_user_update':
        if (!is_post()) redirect('index.php?action=admin');
        AdminController::updateUser();
        break;

    case 'admin_user_delete':
        if (!is_post()) redirect('index.php?action=admin');
        AdminController::deleteUser();
        break;

    case 'log_activity_ajax':
        if (!is_post()) exit;
        HomeController::logActivityAjax();
        break;

    case 'reset_database':
        require_auth();
        db()->exec('DELETE FROM candidates');
        db()->exec('DELETE FROM clients');
        db()->exec('DELETE FROM interviews');
        db()->exec('DELETE FROM reminders');
        db()->exec('DELETE FROM notifications');
        echo "<h1>Database Reset Successful</h1><p>All dummy data has been removed.</p><a href='index.php'>Go back to Dashboard</a>";
        exit;

    case 'log_view':
        require_auth();
        $module = $_GET['module'] ?? '';
        $id = (int) ($_GET['id'] ?? 0);
        if ($id > 0) {
            log_activity($module, $id, 'Profile Viewed', 'The profile was opened and viewed.');
        }
        echo json_encode(['status' => 'ok']);
        exit;

    case 'get_timeline':
        require_auth();
        $module = $_GET['module'] ?? '';
        $id = (int) ($_GET['id'] ?? 0);
        $stmt = db()->prepare('SELECT action_title, action_details, created_by, created_at FROM timelines WHERE module_type = :module AND reference_id = :id ORDER BY created_at DESC');
        $stmt->execute(['module' => $module, 'id' => $id]);
        header('Content-Type: application/json');
        echo json_encode($stmt->fetchAll());
        exit;

    case 'read_notification':
        HomeController::markNotificationRead();
        break;

    case 'login':
        if (!is_post()) {
            redirect('index.php?view=login');
        }
        AuthController::login();
        break;

    case 'register':
        if (!is_post()) {
            redirect('index.php?view=register');
        }
        AuthController::register();
        break;

    case 'logout':
        AuthController::logout();
        break;

    case 'add_candidate':
        if (!is_post()) {
            redirect('index.php?action=candidate');
        }
        CandidateController::addCandidate();
        break;

    case 'update_candidate':
        if (!is_post()) {
            redirect('index.php?action=candidate');
        }
        CandidateController::updateCandidate();
        break;

    case 'add_client':
        if (!is_post()) {
            redirect('index.php?action=clients');
        }
        ClientController::addClient();
        break;

    case 'update_client':
        if (!is_post()) {
            redirect('index.php?action=clients');
        }
        ClientController::updateClient();
        break;

    case 'update_client_status':
        if (!is_post()) {
            redirect('index.php?action=clients');
        }
        ClientController::updateStatus();
        break;

    case 'add_client_reminder':
        if (!is_post()) {
            redirect('index.php?action=clients');
        }
        ClientController::addReminder();
        break;

    case 'add_candidate_reminder':
        if (!is_post()) {
            redirect('index.php?action=candidate');
        }
        CandidateController::addReminder();
        break;

    case 'add_interview':
        if (!is_post()) {
            redirect('index.php?action=interviews');
        }
        InterviewController::addInterview();
        break;

    case 'update_interview':
        if (!is_post()) {
            redirect('index.php?action=interviews');
        }
        InterviewController::updateInterview();
        break;

    case 'update_interview_stage':
        if (!is_post()) {
            redirect('index.php?action=interviews');
        }
        InterviewController::updateStage();
        break;

    case 'bulk_status':
        if (!is_post()) {
            redirect('index.php?action=candidate');
        }
        CandidateController::bulkStatus();
        break;

    case 'delete_candidate':
        if (!is_post()) {
            redirect('index.php?action=candidate');
        }
        CandidateController::deleteSelected();
        break;


    case 'delete_client':
        if (!is_post()) {
            redirect('index.php?action=clients');
        }
        ClientController::deleteSelected();
        break;

    case 'delete_interview':
        if (!is_post()) {
            redirect('index.php?action=interviews');
        }
        InterviewController::deleteSelected();
        break;

    case 'import_csv':
        if (!is_post()) {
            redirect('index.php?action=candidate');
        }
        CandidateController::importCsv();
        break;

    case 'import_client_csv':
        if (!is_post()) {
            redirect('index.php?action=clients');
        }
        ClientController::importCsv();
        break;

    case 'export_filtered':
        CandidateController::exportFiltered();
        break;

    case 'export_client_filtered':
        ClientController::exportFiltered();
        break;

    case 'export_client_selected':
        ClientController::exportSelected();
        break;

    case 'export_interview_filtered':
        InterviewController::exportFiltered();
        break;

    case 'export_interview_selected':
        InterviewController::exportSelected();
        break;

    case 'export_selected':
        CandidateController::exportSelected();
        break;

    default:
        if (is_logged_in()) {
            redirect('index.php?action=home');
        }

        AuthController::showLogin();
        break;
}
