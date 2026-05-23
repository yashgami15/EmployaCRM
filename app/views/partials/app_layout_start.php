<?php

$pageTitle = $pageTitle ?? 'Employa HR';
$headerTitle = $headerTitle ?? 'Dashboard';
$currentModule = $currentModule ?? 'home';
$headerActions = $headerActions ?? '';
$extraHead = $extraHead ?? '';
$showHeaderMeta = $showHeaderMeta ?? true;
$logoPath = app_logo_path();
$unreadCount = unread_notification_count();
$notifications = recent_notifications();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($pageTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/style.css">
    <?= $extraHead ?>
</head>
<body class="dashboard-page">
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
<div class="app-shell" id="appShell">
    <aside class="app-sidebar" id="appSidebar">
        <div class="sidebar-top">
            <div class="brand-box">
                <img src="<?= esc($logoPath) ?>" alt="Employa" class="brand-logo">
                <div class="brand-text">
                    <h6 class="mb-0">Employa HR</h6>
                </div>
            </div>

            <nav class="sidebar-nav mt-4">
                <?php
                $userPerms = [];
                if (($_SESSION['role'] ?? '') !== 'admin') {
                    $u = current_user();
                    if ($u) {
                        $userPerms = json_decode((string) ($u['permissions'] ?? '[]'), true) ?: [];
                    }
                }
                function has_perm($module, $perms) {
                    return ($_SESSION['role'] ?? '') === 'admin' || in_array($module, $perms, true);
                }
                ?>
                <a class="nav-link <?= $currentModule === 'home' ? 'active' : '' ?>" href="index.php?action=home"><i class="bi bi-speedometer2"></i><span>Dashboard</span></a>
                
                <?php if (has_perm('candidate', $userPerms)): ?>
                <a class="nav-link <?= $currentModule === 'candidate' ? 'active' : '' ?>" href="index.php?action=candidate"><i class="bi bi-person-vcard"></i><span>Candidate</span></a>
                <?php endif; ?>
                
                <?php if (has_perm('clients', $userPerms)): ?>
                <a class="nav-link <?= $currentModule === 'clients' ? 'active' : '' ?>" href="index.php?action=clients"><i class="bi bi-building-check"></i><span>Client</span></a>
                <?php endif; ?>
                
                <?php if (has_perm('interviews', $userPerms)): ?>
                <a class="nav-link <?= $currentModule === 'interviews' ? 'active' : '' ?>" href="index.php?action=interviews"><i class="bi bi-calendar2-week"></i><span>Interview</span></a>
                <?php endif; ?>
                
                <?php if (has_perm('ai_matcher', $userPerms)): ?>
                <a class="nav-link <?= $currentModule === 'ai_matcher' ? 'active' : '' ?>" href="index.php?action=ai_matcher"><i class="bi bi-robot"></i><span>AI Matcher</span></a>
                <?php endif; ?>
                
                <?php if (has_perm('settings', $userPerms)): ?>
                <a class="nav-link <?= $currentModule === 'settings' ? 'active' : '' ?>" href="index.php?action=settings"><i class="bi bi-gear"></i><span>Settings</span></a>
                <?php endif; ?>
                <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                <a class="nav-link <?= $currentModule === 'admin' ? 'active' : '' ?>" href="index.php?action=admin"><i class="bi bi-shield-lock"></i><span>Admin Module</span></a>
                <a class="nav-link <?= $currentModule === 'admin_tracking' ? 'active' : '' ?>" href="index.php?action=admin_tracking"><i class="bi bi-activity"></i><span>User Tracking</span></a>
                <?php endif; ?>
            </nav>
        </div>

        <div class="sidebar-bottom">
            <h6 class="mb-2"><?= esc($user['name']) ?></h6>
            <a href="index.php?action=logout" class="btn btn-outline-secondary btn-sm">Logout</a>
        </div>
    </aside>

    <main class="app-main">
        <header class="app-header d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-light border btn-sm" id="sidebarToggle" type="button" aria-label="Toggle sidebar">
                    <i class="bi bi-list"></i>
                </button>
                <h2 class="app-title mb-0"><?= esc($headerTitle) ?></h2>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
                <?php if ($showHeaderMeta): ?>
                <?php endif; ?>

                <div class="dropdown">
                    <button class="btn btn-light border notification-btn" data-bs-toggle="dropdown" type="button" aria-expanded="false" aria-label="Notifications">
                        <i class="bi bi-bell"></i>
                        <?php if ($unreadCount > 0): ?>
                            <span class="notif-dot"><?= $unreadCount > 9 ? '9+' : $unreadCount ?></span>
                        <?php endif; ?>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end notification-menu p-0">
                        <div class="notification-header px-3 py-2 d-flex justify-content-between align-items-center">
                            <strong><i class="bi bi-bell-fill me-1"></i> Notifications</strong>
                            <small class="text-secondary"><?= (int) $unreadCount ?> unread</small>
                        </div>
                        <div class="notification-list">
                            <?php if (empty($notifications)): ?>
                                <div class="notification-empty">
                                    <i class="bi bi-check2-circle"></i>
                                    <span>No notifications yet.</span>
                                </div>
                            <?php else: ?>
                                <?php foreach ($notifications as $note): ?>
                                    <a class="dropdown-item py-2 px-3 notification-item <?= (int) $note['is_read'] === 0 ? 'unread' : '' ?>" href="index.php?action=read_notification&id=<?= (int) $note['id'] ?>">
                                        <span class="notification-icon"><i class="bi bi-bell"></i></span>
                                        <span class="notification-copy">
                                            <span class="small fw-semibold"><?= esc((string) $note['title']) ?></span>
                                            <span class="small text-secondary"><?= esc((string) ($note['notification_message'] ?? '')) ?></span>
                                            <span class="small text-muted mt-1"><?= esc((string) $note['created_at']) ?></span>
                                        </span>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <?= $headerActions ?>
            </div>
        </header>

        <?php if ($unreadCount > 0): ?>
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const lastNotifCount = localStorage.getItem('lastNotifCount') || 0;
                    if (<?= $unreadCount ?> > lastNotifCount) {
                        try {
                            const audio = new Audio('assets/notification.mp3');
                            audio.play().catch(e => console.log('Audio autoplay blocked by browser', e));
                            
                            if (Notification.permission === 'granted') {
                                new Notification('Employa HR', { body: 'You have <?= $unreadCount ?> new notifications!' });
                            } else if (Notification.permission !== 'denied') {
                                Notification.requestPermission().then(permission => {
                                    if (permission === 'granted') {
                                        new Notification('Employa HR', { body: 'You have <?= $unreadCount ?> new notifications!' });
                                    }
                                });
                            }
                        } catch (e) {
                            console.log('Notification error', e);
                        }
                    }
                    localStorage.setItem('lastNotifCount', <?= $unreadCount ?>);
                });
            </script>
        <?php else: ?>
            <script>
                localStorage.setItem('lastNotifCount', 0);
            </script>
        <?php endif; ?>

        <section class="content-section">
            <?php if (!empty($flash)): ?>
                <div class="alert alert-<?= esc((string) $flash['type']) ?> mb-3" role="alert">
                    <?= (string) $flash['message'] ?>
                </div>
            <?php endif; ?>
