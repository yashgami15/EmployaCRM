<?php
/** @var array $user */
/** @var array|null $flash */
/** @var array $candidateStats */
/** @var array $clientStats */
/** @var array $interviewStats */
/** @var array $pipeline */
/** @var array $chartLabels */
/** @var array $chartValues */
/** @var array $periodStats */
/** @var array $clientChart */
/** @var array $interviewChart */
/** @var array $upcomingInterviews */
/** @var array $upcomingReminders */

$pageTitle = 'Employa HR - Dashboard';
$headerTitle = 'Dashboard';
$currentModule = 'home';
$showHeaderMeta = false;
$headerActions = '';
$extraHead = '<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.5.0/chart.umd.min.js"></script>';

require BASE_PATH . '/app/views/partials/app_layout_start.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h1 class="greeting mb-1">Dashboard</h1>
        <p class="text-secondary mb-0">Profile overview, reminders and hiring performance by day, month and year.</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-success" onclick="syncReminders(this)"><i class="bi bi-arrow-repeat"></i> Sync Reminders</button>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-4">
        <div class="profile-panel h-100">
            <div class="profile-avatar"><?= esc(strtoupper(substr((string) $user['name'], 0, 1))) ?></div>
            <div>
                <span class="panel-eyebrow">Profile</span>
                <h5 class="mb-1"><?= esc((string) $user['name']) ?></h5>
                <p class="mb-0 text-secondary"><?= esc((string) $user['email']) ?></p>
            </div>
        </div>
    </div>
    <div class="col-xl-8">
        <div class="reminder-strip h-100">
            <div>
                <span class="panel-eyebrow">Reminder</span>
                <h5 class="mb-1">Upcoming Follow-ups</h5>
                <p class="mb-0 text-secondary"><?= count($upcomingReminders) ?> reminder<?= count($upcomingReminders) === 1 ? '' : 's' ?> available in your queue.</p>
            </div>
            <i class="bi bi-bell"></i>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3">
        <article class="stat-card stat-total">
            <i class="bi bi-people"></i>
            <small>Total Candidates</small>
            <h3><?= (int) $candidateStats['total'] ?></h3>
        </article>
    </div>
    <div class="col-6 col-lg-3">
        <article class="stat-card stat-client">
            <i class="bi bi-briefcase"></i>
            <small>Open Positions</small>
            <h3><?= (int) $clientStats['open_positions'] ?></h3>
        </article>
    </div>
    <div class="col-6 col-lg-3">
        <article class="stat-card stat-interview">
            <i class="bi bi-calendar2-check"></i>
            <small>Interviews</small>
            <h3><?= (int) $interviewStats['total'] ?></h3>
        </article>
    </div>
    <div class="col-6 col-lg-3">
        <article class="stat-card stat-hired">
            <i class="bi bi-award"></i>
            <small>Hired</small>
            <h3><?= (int) $candidateStats['hired'] ?></h3>
        </article>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4"><article class="mini-tile period-tile"><span>Today</span><strong><?= (int) $periodStats['today']['candidates'] ?> candidates</strong><small><?= (int) $periodStats['today']['interviews'] ?> interviews</small></article></div>
    <div class="col-md-4"><article class="mini-tile period-tile"><span>This Month</span><strong><?= (int) $periodStats['month']['candidates'] ?> candidates</strong><small><?= (int) $periodStats['month']['interviews'] ?> interviews</small></article></div>
    <div class="col-md-4"><article class="mini-tile period-tile"><span>This Year</span><strong><?= (int) $periodStats['year']['candidates'] ?> candidates</strong><small><?= (int) $periodStats['year']['interviews'] ?> interviews</small></article></div>
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-8">
        <div class="card card-soft h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Monthly Candidate Update</h5>
                    <a href="index.php?action=candidate" class="btn btn-sm btn-outline-secondary">View Candidates</a>
                </div>
                <div class="chart-wrap">
                    <canvas id="candidateTrendChart" height="120"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card card-soft h-100">
            <div class="card-body">
                <h5 class="mb-3">Hiring Funnel</h5>
                <div class="candidate-funnel">
                    <?php foreach ($pipeline as $stage): ?>
                        <?php $percent = (int) $candidateStats['total'] > 0 ? max(8, round(((int) $stage['count'] / (int) $candidateStats['total']) * 100)) : 8; ?>
                        <div class="stage-row <?= esc($stage['class']) ?>" style="--stage-width: <?= $percent ?>%;">
                            <span><?= esc($stage['label']) ?></span>
                            <strong><?= (int) $stage['count'] ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
                <hr>
                <p class="small text-secondary mb-0">Clients: <strong><?= (int) $clientStats['total'] ?></strong> total, <strong><?= (int) $clientStats['active'] ?></strong> active</p>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-6">
        <div class="card card-soft h-100">
            <div class="card-body">
                <h5 class="mb-3">Client Status</h5>
                <div class="chart-wrap chart-wrap-sm">
                    <canvas id="clientStatusChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card card-soft h-100">
            <div class="card-body">
                <h5 class="mb-3">Interview Outcome</h5>
                <div class="chart-wrap chart-wrap-sm">
                    <canvas id="interviewStatusChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-12">
        <div class="card card-soft h-100">
            <div class="card-body">
                <h5 class="mb-3">Upcoming Reminders</h5>
                <div class="table-responsive">
                    <table class="table mb-0 align-middle">
                        <thead>
                        <tr>
                            <th>Title</th>
                            <th>Date Time</th>
                            <th>Email</th>
                            <th>SMS</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($upcomingReminders)): ?>
                            <tr>
                                <td colspan="4" class="text-secondary py-4 text-center">No reminders added yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($upcomingReminders as $reminder): ?>
                                <tr>
                                    <td>
                                        <p class="fw-bold mb-0 text-dark"><?= esc((string) $reminder['title']) ?></p>
                                        <small class="text-secondary"><?= esc((string) ($reminder['reminder_message'] ?? '')) ?></small>
                                    </td>
                                    <td><?= esc((string) $reminder['remind_at']) ?></td>
                                    <td><?= esc((string) strtoupper((string) $reminder['email_status'])) ?></td>
                                    <td><?= esc((string) strtoupper((string) $reminder['sms_status'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$chartPayload = json_encode([
    'labels' => $chartLabels,
    'values' => $chartValues,
    'client' => $clientChart,
    'interview' => $interviewChart,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$extraScripts = [
    '<script>window.candidateTrendData = ' . $chartPayload . ';</script>',
    '<script src="js/home.js"></script>',
];

require BASE_PATH . '/app/views/partials/app_layout_end.php';
