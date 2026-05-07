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
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">
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
                    <span>CRM Workspace</span>
                </div>
            </div>

            <nav class="sidebar-nav mt-4">
                <a class="nav-link <?= $currentModule === 'home' ? 'active' : '' ?>" href="index.php?action=home"><i class="bi bi-speedometer2"></i><span>Dashboard</span></a>
                <a class="nav-link <?= $currentModule === 'candidate' ? 'active' : '' ?>" href="index.php?action=candidate"><i class="bi bi-person-vcard"></i><span>Candidate</span></a>
                <a class="nav-link <?= $currentModule === 'clients' ? 'active' : '' ?>" href="index.php?action=clients"><i class="bi bi-building-check"></i><span>Client</span></a>
                <a class="nav-link <?= $currentModule === 'interviews' ? 'active' : '' ?>" href="index.php?action=interviews"><i class="bi bi-calendar2-week"></i><span>Interview</span></a>
            </nav>
        </div>

        <div class="sidebar-bottom">
            <h6 class="mb-2"><?= esc($user['name']) ?></h6>
            <a href="index.php?action=logout" class="btn btn-outline-secondary btn-sm">Logout</a>
        </div>
    </aside>

    <main class="app-main">
        <header class="app-header d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-light border btn-sm" id="sidebarToggle" type="button" aria-label="Toggle sidebar">
                    <i class="bi bi-list"></i>
                </button>
                <h2 class="app-title mb-0"><?= esc($headerTitle) ?></h2>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
                <?php if ($showHeaderMeta): ?>
                    <span class="chip"><?= date('d/m/Y') ?></span>
                    <span class="chip text-lowercase">employa hr</span>
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

        <section class="content-section">
            <?php if (!empty($flash)): ?>
                <div class="alert alert-<?= esc((string) $flash['type']) ?> mb-3" role="alert">
                    <?= esc((string) $flash['message']) ?>
                </div>
            <?php endif; ?>
