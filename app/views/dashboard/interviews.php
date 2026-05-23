<?php
/** @var array $user */
/** @var array|null $flash */
/** @var array $interviews */
/** @var array $candidates */
/** @var array $clients */
/** @var array $stageOptions */
/** @var array $modeOptions */
/** @var array $stats */

$stage = trim((string) ($_GET['stage'] ?? ''));
$date = trim((string) ($_GET['date'] ?? ''));

$pageTitle = 'Employa HR - Interviews';
$headerTitle = 'Interview';
$currentModule = 'interviews';
$filterQuery = http_build_query(['stage' => $stage, 'date' => $date]);
$headerActions = '<button class="btn btn-success fw-semibold" data-bs-toggle="modal" data-bs-target="#addInterviewModal"><i class="bi bi-calendar-plus"></i> Add Interview</button>';

require BASE_PATH . '/app/views/partials/app_layout_start.php';
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <h1 class="greeting mb-0">Interview Tracker</h1>
    <p class="text-secondary mb-0">Manage interview rounds, schedule dates and update outcomes.</p>
</div>

<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3"><article class="stat-card stat-total"><i class="bi bi-calendar2-week"></i><small>Total Interviews</small><h3><?= (int) $stats['total'] ?></h3></article></div>
    <div class="col-6 col-lg-3"><article class="stat-card stat-applied"><i class="bi bi-clock-history"></i><small>Scheduled</small><h3><?= (int) $stats['scheduled'] ?></h3></article></div>
    <div class="col-6 col-lg-3"><article class="stat-card stat-interview"><i class="bi bi-clipboard-check"></i><small>Completed</small><h3><?= (int) $stats['completed'] ?></h3></article></div>
    <div class="col-6 col-lg-3"><article class="stat-card stat-hired"><i class="bi bi-person-check"></i><small>Selected</small><h3><?= (int) $stats['selected'] ?></h3></article></div>
</div>

<form class="card card-soft p-3 mb-3" method="get" action="index.php">
    <input type="hidden" name="action" value="interviews">
    <div class="row g-2">
        <div class="col-lg-5 col-sm-6">
            <select class="form-select" name="stage" onchange="this.form.submit()">
                <option value="">All Stages</option>
                <?php foreach ($stageOptions as $option): ?>
                    <option value="<?= esc($option) ?>" <?= $stage === $option ? 'selected' : '' ?>><?= esc($option) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-lg-5 col-sm-6">
            <input class="form-control" type="date" name="date" value="<?= esc($date) ?>" onchange="this.form.submit()">
        </div>
    </div>
    <div class="d-flex flex-wrap gap-2 mt-3">
        <a class="btn btn-outline-secondary" href="index.php?<?= http_build_query(['action' => 'export_interview_filtered', 'stage' => $stage, 'date' => $date]) ?>">Export Filtered</a>
        <a class="btn btn-outline-secondary disabled" href="#" id="exportInterviewSelectedBtn" data-base-url="index.php?action=export_interview_selected">Export Selected</a>
    </div>
</form>

<div class="card card-soft p-3">
    <div class="d-flex flex-wrap gap-3 align-items-center mb-3">
        <label class="d-flex align-items-center gap-2 mb-0">
            <input type="checkbox" class="form-check-input" id="selectVisibleCheckbox">
            <span>Select visible</span>
        </label>
        <strong class="text-secondary"><span id="selectedCount">0</span> selected</strong>

        <form id="deleteSelectedForm" method="post" action="index.php?action=delete_interview&<?= $filterQuery ?>" class="ms-auto">
            <?= csrf_field() ?>
            <input type="hidden" name="selected_ids" id="deleteSelectedIds">
            <button type="submit" class="btn btn-outline-danger" id="deleteSelectedBtn">Delete Selected</button>
        </form>
    </div>

    <div class="table-responsive data-table-wrap">
        <table class="table table-candidates align-middle mb-0">
            <thead>
                <tr>
                    <th style="width:44px;"></th>
                    <th>Candidate</th>
                    <th>Client</th>
                    <th>Round</th>
                    <th>Date</th>
                    <th>Mode</th>
                    <th>Interviewer</th>
                    <th>Stage</th>
                    <th class="sticky-action">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($interviews)): ?>
                    <tr><td colspan="9" class="text-center py-5 text-secondary">No interview data found.</td></tr>
                <?php else: ?>
                    <?php foreach ($interviews as $item): ?>
                        <tr>
                            <td><input type="checkbox" class="form-check-input row-checkbox" value="<?= (int) $item['id'] ?>"></td>
                            <td>
                                <p class="candidate-name mb-0"><?= esc((string) ($item['candidate_name'] ?: 'N/A')) ?></p>
                                <small class="text-secondary">ID #<?= (int) $item['candidate_id'] ?></small>
                            </td>
                            <td><?= esc((string) ($item['client_name'] ?: '-')) ?></td>
                            <td><?= esc((string) ($item['round_name'])) ?></td>
                            <td><?= esc((string) ($item['interview_date'])) ?></td>
                            <td><?= esc((string) ($item['mode'])) ?></td>
                            <td><?= esc((string) ($item['interviewer'] ?: '-')) ?></td>
                            <td><span class="badge stage-pill"><?= esc((string) $item['stage']) ?></span></td>
                            <?php
                            $interviewFormFields = [
                                'candidate_id', 'client_id', 'round_name', 'interview_date', 'interviewer', 'mode', 'stage', 'feedback'
                            ];
                            $interviewDataAttrs = 'data-interview-id="' . (int) $item['id'] . '"';
                            foreach ($interviewFormFields as $field) {
                                $interviewDataAttrs .= ' data-interview-' . str_replace('_', '-', $field) . '="' . esc((string) ($item[$field] ?? '')) . '"';
                            }
                            ?>
                            <td class="sticky-action action-cell">
                                <div class="d-flex flex-nowrap align-items-center gap-1">
                                    <button
                                        class="btn btn-sm btn-outline-primary interview-edit-btn"
                                        type="button"
                                        title="Edit"
                                        data-interview-mode="edit"
                                        <?= $interviewDataAttrs ?>
                                        data-bs-toggle="modal"
                                        data-bs-target="#addInterviewModal">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

?>

<div class="modal fade" id="addInterviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <form method="post" action="index.php?action=add_interview&<?= $filterQuery ?>">
                <?= csrf_field() ?>
                <div class="modal-header modal-header-sticky">
                    <h5 class="modal-title">Add Interview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <input type="hidden" name="interview_id" id="interviewIdInput">
                <div class="modal-body pt-2">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Candidate <span class="text-danger-asterisk">*</span></label>
                            <select name="candidate_id" class="form-select" required>
                                <option value="">Select Candidate</option>
                                <?php foreach ($candidates as $candidate): ?>
                                    <option value="<?= (int) $candidate['id'] ?>" <?= old('candidate_id') == $candidate['id'] ? 'selected' : '' ?>><?= esc((string) $candidate['full_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Client</label>
                            <select name="client_id" class="form-select">
                                <option value="">Select Client</option>
                                <?php foreach ($clients as $client): ?>
                                    <option value="<?= (int) $client['id'] ?>" <?= old('client_id') == $client['id'] ? 'selected' : '' ?>><?= esc((string) $client['company_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Round Name <span class="text-danger-asterisk">*</span></label>
                            <input type="text" class="form-control" name="round_name" value="<?= esc(old('round_name')) ?>" placeholder="Technical Round 1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Interview Date <span class="text-danger-asterisk">*</span></label>
                            <input type="date" class="form-control" name="interview_date" value="<?= esc(old('interview_date', date('Y-m-d'))) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Interviewer</label>
                            <input type="text" class="form-control" name="interviewer" value="<?= esc(old('interviewer')) ?>" placeholder="Anuj Patel">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Mode</label>
                            <select name="mode" class="form-select">
                                <?php foreach ($modeOptions as $option): ?>
                                    <option value="<?= esc($option) ?>" <?= old('mode', 'Online') === $option ? 'selected' : '' ?>><?= esc($option) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Stage</label>
                            <select name="stage" class="form-select">
                                <?php foreach ($stageOptions as $option): ?>
                                    <option value="<?= esc($option) ?>" <?= old('stage', 'Scheduled') === $option ? 'selected' : '' ?>><?= esc($option) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Feedback</label>
                            <textarea name="feedback" rows="3" class="form-control" placeholder="Optional notes"><?= esc(old('feedback')) ?></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer modal-footer-sticky">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Save Interview</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
clear_old();
require BASE_PATH . '/app/views/partials/app_layout_end.php';
