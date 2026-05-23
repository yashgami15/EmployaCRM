<?php

declare(strict_types=1);

class AuthController
{
    public static function showLogin(): void
    {
        require_guest();

        $flash = get_flash();
        $viewMode = ($_GET['view'] ?? 'login') === 'register' ? 'register' : 'login';

        require BASE_PATH . '/app/views/auth/login.php';
        clear_old();
    }

    public static function login(): void
    {
        require_guest();
        verify_csrf();

        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $company_name = trim((string) ($_POST['company_name'] ?? ''));

        set_old(['email' => $email, 'company_name' => $company_name]);

        if ($email === '' || $password === '' || $company_name === '') {
            set_flash('Email, password, and company name are required.', 'danger');
            redirect('index.php?view=login');
        }

        $stmt = db()->prepare('SELECT id, name, email, password, role, tenant_name FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, (string) $user['password'])) {
            set_flash('Invalid email or password.', 'danger');
            redirect('index.php?view=login');
        }

        if ($user['role'] !== 'admin' && strtolower((string) $user['tenant_name']) !== strtolower($company_name)) {
            set_flash('This company is not there. Create new user with company.', 'danger');
            redirect('index.php?view=register');
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['tenant_name'] = $user['tenant_name'];
        $_SESSION['role'] = $user['role'];

        // Save company name in cookie for 1 year
        setcookie('remembered_company', $user['tenant_name'], time() + (86400 * 365), '/');

        log_activity('user', (int) $user['id'], 'Login', 'User logged in successfully.');

        clear_old();
        set_flash('Welcome back, ' . $user['name'] . '!', 'success');

        redirect('index.php?action=home');
    }

    public static function register(): void
    {
        require_guest();
        verify_csrf();

        $name = trim((string) ($_POST['name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
        $company_name = trim((string) ($_POST['company_name'] ?? ''));

        set_old(['name' => $name, 'email' => $email, 'company_name' => $company_name]);

        if ($name === '' || $email === '' || $password === '' || $confirmPassword === '' || $company_name === '') {
            set_flash('All fields are required.', 'danger');
            redirect('index.php?view=register');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_flash('Please enter a valid email address.', 'danger');
            redirect('index.php?view=register');
        }

        if (strlen($password) < 6) {
            set_flash('Password must be at least 6 characters.', 'danger');
            redirect('index.php?view=register');
        }

        if (!hash_equals($password, $confirmPassword)) {
            set_flash('Password and confirm password do not match.', 'danger');
            redirect('index.php?view=register');
        }

        $existsStmt = db()->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $existsStmt->execute(['email' => $email]);

        if ($existsStmt->fetch()) {
            set_flash('This email is already registered. Please sign in.', 'warning');
            redirect('index.php?view=login');
        }

        $stmt = db()->prepare('INSERT INTO users (name, email, password, role, tenant_name, visible_password) VALUES (:name, :email, :password, :role, :tenant_name, :visible_password)');
        $stmt->execute([
            'name' => $name,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role' => 'user',
            'tenant_name' => $company_name,
            'visible_password' => $password
        ]);

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) db()->lastInsertId();
        $_SESSION['tenant_name'] = $company_name;
        $_SESSION['role'] = 'user';

        clear_old();
        set_flash('Account created successfully.', 'success');

        redirect('index.php?action=home');
    }

    public static function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }

        session_destroy();
        session_start();

        set_flash('You have been logged out.', 'info');
        redirect('index.php');
    }
}
