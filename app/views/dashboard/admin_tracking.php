<?php
$pageTitle = 'User Tracking - Employa HR';
$headerTitle = 'User Tracking';
$currentModule = 'admin_tracking';

ob_start();
?>
<div class="d-flex flex-wrap gap-2">
    <form class="d-flex flex-wrap align-items-center gap-2" method="GET" action="index.php">
        <input type="hidden" name="action" value="admin_tracking">
        <label for="period" class="fw-medium text-secondary mb-0">Period:</label>
        <select name="period" id="period" class="form-select form-select-sm shadow-none" onchange="this.form.submit()">
            <option value="today" <?= $period === 'today' ? 'selected' : '' ?>>Today</option>
            <option value="month" <?= $period === 'month' ? 'selected' : '' ?>>This Month</option>
            <option value="all" <?= $period === 'all' ? 'selected' : '' ?>>All Time</option>
        </select>
    </form>
</div>
<?php
$headerActions = ob_get_clean();

require BASE_PATH . '/app/views/partials/app_layout_start.php';
?>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-semibold text-dark">User Work Summary (<?= ucfirst($period) ?>)</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 custom-table">
                <thead class="table-light text-secondary">
                    <tr>
                        <th class="px-4 py-3 fw-medium">User Name</th>
                        <th class="py-3 fw-medium text-center">Logins</th>
                        <th class="py-3 fw-medium text-center">Candidates Added</th>
                        <th class="py-3 fw-medium text-center">Clients Added</th>
                        <th class="py-3 fw-medium text-center">Calls Made</th>
                        <th class="py-3 fw-medium text-center">Emails Sent</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($userStats)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <div class="mb-2"><i class="bi bi-bar-chart fs-1"></i></div>
                                <div>No activity recorded for this period.</div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($userStats as $username => $stats): ?>
                            <tr>
                                <td class="px-4 fw-medium text-dark">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-size: 14px;">
                                            <?= esc(strtoupper(substr($username, 0, 1))) ?>
                                        </div>
                                        <?= esc((string) $username) ?>
                                    </div>
                                </td>
                                <td class="text-center"><span class="badge bg-light text-dark border px-2 py-1"><?= $stats['Login'] ?></span></td>
                                <td class="text-center"><span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1"><?= $stats['Profile Created'] ?></span></td>
                                <td class="text-center"><span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-2 py-1"><?= $stats['Client Created'] ?? 0 ?></span></td>
                                <td class="text-center"><span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-2 py-1"><?= $stats['Clicked on Call'] ?></span></td>
                                <td class="text-center"><span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2 py-1"><?= $stats['Clicked on Mail'] ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4">
        <h5 class="mb-0 fw-semibold text-dark">Detailed Activity Log</h5>
    </div>
    <div class="card-body p-4">
        <?php if (empty($details)): ?>
            <div class="text-center py-4 text-muted">No activities found.</div>
        <?php else: ?>
            <div class="timeline">
                <?php foreach ($details as $detail): ?>
                    <div class="timeline-item d-flex gap-3 mb-3 pb-3 border-bottom position-relative">
                        <div class="timeline-icon bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; min-width: 40px;">
                            <i class="bi bi-activity"></i>
                        </div>
                        <div>
                            <div class="fw-medium text-dark d-flex align-items-center gap-2">
                                <?= esc((string) $detail['created_by']) ?> 
                                <span class="badge bg-secondary bg-opacity-10 text-secondary fw-normal"><?= esc((string) $detail['action_title']) ?></span>
                            </div>
                            <div class="text-muted small mt-1"><?= esc((string) $detail['action_details']) ?></div>
                            <div class="text-secondary small mt-1 opacity-75">
                                <i class="bi bi-clock me-1"></i> <?= esc(date('d M Y, h:i A', strtotime($detail['created_at']))) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.timeline-item:last-child {
    border-bottom: none !important;
    margin-bottom: 0 !important;
    padding-bottom: 0 !important;
}
</style>

<?php
require BASE_PATH . '/app/views/partials/app_layout_end.php';
?>
