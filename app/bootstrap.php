<?php

declare(strict_types=1);

session_start();
date_default_timezone_set('Asia/Kolkata');

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/config/database.php';
require BASE_PATH . '/app/helpers/functions.php';
require BASE_PATH . '/app/controllers/AuthController.php';
require BASE_PATH . '/app/controllers/HomeController.php';
require BASE_PATH . '/app/controllers/ClientController.php';
require BASE_PATH . '/app/controllers/InterviewController.php';
require BASE_PATH . '/app/controllers/CandidateController.php';

process_due_reminders();
