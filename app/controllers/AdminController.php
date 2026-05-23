<?php

declare(strict_types=1);

class AdminController
{
    private static function requireAdmin(): void
    {
        require_auth();
        if (($_SESSION['role'] ?? '') !== 'admin') {
            set_flash('You do not have permission to access the Admin Module.', 'danger');
            redirect('index.php?action=home');
        }
    }

    public static function index(): void
    {
        self::requireAdmin();

        $user = current_user();
        $flash = get_flash();

        $stmt = db()->query('SELECT id, name, email, tenant_name, role, permissions, visible_password, gemini_api_key FROM users ORDER BY id DESC');
        $users = $stmt->fetchAll();

        require BASE_PATH . '/app/views/dashboard/admin.php';
    }

    public static function tracking(): void
    {
        self::requireAdmin();

        $user = current_user();
        $flash = get_flash();

        $period = trim((string) ($_GET['period'] ?? 'today'));
        $dateFilter = "DATE(created_at) = DATE('now', 'localtime')";
        if ($period === 'month') {
            $dateFilter = "strftime('%Y-%m', created_at) = strftime('%Y-%m', 'now', 'localtime')";
        } elseif ($period === 'all') {
            $dateFilter = "1=1";
        }

        // Get activities grouped by user and action
        $sql = "SELECT created_by, action_title, COUNT(*) as count 
                FROM timelines 
                WHERE {$dateFilter} 
                GROUP BY created_by, action_title";
        $stmt = db()->query($sql);
        $activities = $stmt->fetchAll();

        // Organize stats per user
        $userStats = [];
        foreach ($activities as $act) {
            $username = $act['created_by'];
            if (!isset($userStats[$username])) {
                $userStats[$username] = [
                    'Login' => 0,
                    'Profile Created' => 0, // Candidates Added
                    'Client Created' => 0,
                    'Clicked on Call' => 0,
                    'Clicked on Mail' => 0,
                ];
            }
            if (isset($userStats[$username][$act['action_title']])) {
                $userStats[$username][$act['action_title']] = (int) $act['count'];
            }
        }

        // Get detailed recent timelines
        $detailsSql = "SELECT module_type, action_title, action_details, created_by, created_at 
                       FROM timelines 
                       WHERE {$dateFilter} 
                       ORDER BY created_at DESC LIMIT 100";
        $details = db()->query($detailsSql)->fetchAll();

        require BASE_PATH . '/app/views/dashboard/admin_tracking.php';
    }

    public static function updateUser(): void
    {
        self::requireAdmin();
        verify_csrf();

        $userId = (int) ($_POST['user_id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $tenantName = trim((string) ($_POST['tenant_name'] ?? ''));
        $role = trim((string) ($_POST['role'] ?? 'user'));
        $password = (string) ($_POST['password'] ?? '');
        $permissions = isset($_POST['permissions']) && is_array($_POST['permissions']) ? $_POST['permissions'] : [];
        $permissionsJson = json_encode($permissions);

        if ($userId <= 0 || $name === '' || $email === '' || $tenantName === '') {
            set_flash('Name, Email, and Company Name are required.', 'danger');
            redirect('index.php?action=admin');
        }

        // Duplicate email check
        $check = db()->prepare('SELECT id FROM users WHERE email = :email AND id != :id');
        $check->execute(['email' => $email, 'id' => $userId]);
        if ($check->fetch()) {
            set_flash('Email is already registered by another user.', 'danger');
            redirect('index.php?action=admin');
        }

        $geminiApiKey = trim((string) ($_POST['gemini_api_key'] ?? ''));

        if ($password !== '') {
            $stmt = db()->prepare('UPDATE users SET name = :name, email = :email, tenant_name = :tenant_name, role = :role, permissions = :permissions, password = :password, visible_password = :visible_password, gemini_api_key = :gemini_api_key WHERE id = :id');
            $stmt->execute([
                'name' => $name,
                'email' => $email,
                'tenant_name' => $tenantName,
                'role' => $role,
                'permissions' => $permissionsJson,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'visible_password' => $password,
                'gemini_api_key' => $geminiApiKey,
                'id' => $userId
            ]);
        } else {
            $stmt = db()->prepare('UPDATE users SET name = :name, email = :email, tenant_name = :tenant_name, role = :role, permissions = :permissions, gemini_api_key = :gemini_api_key WHERE id = :id');
            $stmt->execute([
                'name' => $name,
                'email' => $email,
                'tenant_name' => $tenantName,
                'role' => $role,
                'permissions' => $permissionsJson,
                'gemini_api_key' => $geminiApiKey,
                'id' => $userId
            ]);
        }

        set_flash('User updated successfully.', 'success');
        redirect('index.php?action=admin');
    }

    public static function deleteUser(): void
    {
        self::requireAdmin();
        verify_csrf();

        $userId = (int) ($_POST['user_id'] ?? 0);

        if ($userId <= 0) {
            set_flash('Invalid user selection.', 'danger');
            redirect('index.php?action=admin');
        }

        if ($userId === (int) $_SESSION['user_id']) {
            set_flash('You cannot delete your own admin account.', 'danger');
            redirect('index.php?action=admin');
        }

        $stmt = db()->prepare('DELETE FROM users WHERE id = :id');
        $stmt->execute(['id' => $userId]);

        set_flash('User deleted successfully.', 'success');
        redirect('index.php?action=admin');
    }
}
