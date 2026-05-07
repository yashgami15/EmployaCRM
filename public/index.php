<?php

declare(strict_types=1);

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

    case 'add_interview':
        if (!is_post()) {
            redirect('index.php?action=interviews');
        }
        InterviewController::addInterview();
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

    case 'delete_selected':
        if (!is_post()) {
            redirect('index.php?action=candidate');
        }
        CandidateController::deleteSelected();
        break;

    case 'import_csv':
        if (!is_post()) {
            redirect('index.php?action=candidate');
        }
        CandidateController::importCsv();
        break;

    case 'export_filtered':
        CandidateController::exportFiltered();
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
