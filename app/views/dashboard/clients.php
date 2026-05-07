<?php
/** @var array $user */
/** @var array|null $flash */
/** @var array $clients */
/** @var array $stats */
/** @var array $statusOptions */

$search = trim((string) ($_GET['search'] ?? ''));
$status = trim((string) ($_GET['status'] ?? ''));

$pageTitle = 'Employa HR - Clients';
$headerTitle = 'Client';
$currentModule = 'clients';
$headerActions = '<button class="btn btn-success fw-semibold" data-bs-toggle="modal" data-bs-target="#addClientModal"><i class="bi bi-building-add"></i> Add Client</button>';
$categoryOptions = ['', 'IT Services', 'Manufacturing', 'Retail', 'Healthcare', 'Education', 'Finance', 'Other'];
$timingOptions = ['', 'Full Time', 'Part Time', 'Day Shift', 'Night Shift', 'Flexible'];
$genderOptions = ['Any', 'Male', 'Female'];

require BASE_PATH . '/app/views/partials/app_layout_start.php';
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <h1 class="greeting mb-0">Clients</h1>
    <p class="text-secondary mb-0">Track jobs, follow-ups and reminders for every client.</p>
</div>

<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3"><article class="stat-card stat-client"><i class="bi bi-buildings"></i><small>Total Clients</small><h3><?= (int) $stats['total'] ?></h3></article></div>
    <div class="col-6 col-lg-3"><article class="stat-card stat-hired"><i class="bi bi-check2-circle"></i><small>Active Clients</small><h3><?= (int) $stats['active'] ?></h3></article></div>
    <div class="col-6 col-lg-3"><article class="stat-card stat-interview"><i class="bi bi-hourglass-split"></i><small>In Progress</small><h3><?= (int) $stats['in_progress'] ?></h3></article></div>
    <div class="col-6 col-lg-3"><article class="stat-card stat-applied"><i class="bi bi-person-workspace"></i><small>Open Requirements</small><h3><?= (int) $stats['open_positions'] ?></h3></article></div>
</div>

<form class="card card-soft p-3 mb-3" method="get" action="index.php">
    <input type="hidden" name="action" value="clients">
    <div class="row g-2">
        <div class="col-lg-8">
            <input type="text" class="form-control" name="search" placeholder="Search by company, job code, contact, mobile, role" value="<?= esc($search) ?>">
        </div>
        <div class="col-lg-3 col-sm-8">
            <select class="form-select" name="status">
                <option value="">All Status</option>
                <?php foreach ($statusOptions as $option): ?>
                    <option value="<?= esc($option) ?>" <?= $status === $option ? 'selected' : '' ?>><?= esc($option) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-lg-1 col-sm-4 d-grid">
            <button type="submit" class="btn btn-outline-secondary">Go</button>
        </div>
    </div>
</form>

<div class="card card-soft p-3 mb-3">
    <div class="table-responsive data-table-wrap">
        <table class="table table-candidates align-middle mb-0">
            <thead>
                <tr>
                    <th>Company Name</th>
                    <th>Job Code</th>
                    <th>Reference</th>
                    <th>Contact Person</th>
                    <th>Mobile 1</th>
                    <th>Mobile 2</th>
                    <th>Website</th>
                    <th>Area</th>
                    <th>Category</th>
                    <th>Job Role</th>
                    <th>Timing</th>
                    <th>Male/Female</th>
                    <th>Required Person</th>
                    <th>Budget</th>
                    <th>Expectation</th>
                    <th>Remarks</th>
                    <th>Follower Name</th>
                    <th>Status</th>
                    <th>Follow-up 1</th>
                    <th>Follow-up 2</th>
                    <th>Follow-up 3</th>
                    <th class="sticky-action">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($clients)): ?>
                    <tr><td colspan="22" class="text-center py-5 text-secondary">No client records found.</td></tr>
                <?php else: ?>
                    <?php foreach ($clients as $client): ?>
                        <tr>
                            <td><strong><?= esc((string) $client['company_name']) ?></strong></td>
                            <td><?= esc((string) ($client['job_code'] ?: '-')) ?></td>
                            <td><?= esc((string) ($client['reference_code'] ?: '-')) ?></td>
                            <td><?= esc((string) ($client['contact_person'] ?: '-')) ?></td>
                            <td><?= esc((string) ($client['mobile_number'] ?: $client['phone'] ?: '-')) ?></td>
                            <td><?= esc((string) ($client['mobile_number_2'] ?: '-')) ?></td>
                            <td><?php if (!empty($client['website'])): ?><a href="<?= esc((string) $client['website']) ?>" target="_blank">Open</a><?php else: ?>-<?php endif; ?></td>
                            <td><?= esc((string) ($client['area'] ?: '-')) ?></td>
                            <td><?= esc((string) ($client['category'] ?: '-')) ?></td>
                            <td><?= esc((string) ($client['job_role'] ?: '-')) ?></td>
                            <td><?= esc((string) ($client['timing'] ?: '-')) ?></td>
                            <td><?= esc((string) ($client['gender_preference'] ?: '-')) ?></td>
                            <td><?= (int) $client['required_person_count'] ?></td>
                            <td><?= esc((string) ($client['budget'] ?: '-')) ?></td>
                            <td><?= esc((string) ($client['expectation'] ?: '-')) ?></td>
                            <td><?= esc((string) ($client['remarks'] ?: '-')) ?></td>
                            <td><?= esc((string) ($client['follower_name'] ?: '-')) ?></td>
                            <?php $statusClass = strtolower(str_replace(' ', '_', (string) $client['status'])); ?>
                            <td><span class="badge status-badge client-status-<?= esc($statusClass) ?>"><?= esc((string) $client['status']) ?></span></td>
                            <td><?= esc((string) ($client['follow_up_1'] ?: '-')) ?></td>
                            <td><?= esc((string) ($client['follow_up_2'] ?: '-')) ?></td>
                            <td><?= esc((string) ($client['follow_up_3'] ?: '-')) ?></td>
                            <td class="sticky-action action-cell">
                                <form method="post" action="index.php?action=update_client_status" class="mb-2">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="client_id" value="<?= (int) $client['id'] ?>">
                                    <div class="d-flex gap-1">
                                        <select name="status" class="form-select form-select-sm">
                                            <?php foreach ($statusOptions as $option): ?>
                                                <option value="<?= esc($option) ?>" <?= $client['status'] === $option ? 'selected' : '' ?>><?= esc($option) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="btn btn-sm btn-outline-secondary" type="submit">Save</button>
                                    </div>
                                </form>
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#clientReminderModal" data-client-id="<?= (int) $client['id'] ?>" data-client-name="<?= esc((string) $client['company_name']) ?>" data-client-email="<?= esc((string) ($client['email'] ?? '')) ?>" data-client-phone="<?= esc((string) ($client['mobile_number'] ?: $client['phone'] ?: '')) ?>"><i class="bi bi-bell"></i> Reminder</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="addClientModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <form method="post" action="index.php?action=add_client">
                <?= csrf_field() ?>
                <div class="modal-header modal-header-sticky">
                    <h5 class="modal-title">Add Client Full Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-2">
                    <div class="row g-3">
                        <div class="col-md-3"><label class="form-label">Company Name *</label><input type="text" class="form-control" name="company_name" value="<?= esc(old('company_name')) ?>" required></div>
                        <div class="col-md-2"><label class="form-label">Job Code</label><input type="text" class="form-control" name="job_code" value="<?= esc(old('job_code')) ?>"></div>
                        <div class="col-md-2"><label class="form-label">Reference</label><input type="text" class="form-control" name="reference_code" value="<?= esc(old('reference_code')) ?>"></div>
                        <div class="col-md-2"><label class="form-label">Contact Person</label><input type="text" class="form-control" name="contact_person" value="<?= esc(old('contact_person')) ?>"></div>
                        <div class="col-md-3"><label class="form-label">Email</label><input type="email" class="form-control" name="email" value="<?= esc(old('email')) ?>"></div>

                        <div class="col-md-3"><label class="form-label">Mobile Number</label><input type="text" class="form-control" name="mobile_number" value="<?= esc(old('mobile_number')) ?>"></div>
                        <div class="col-md-3"><label class="form-label">Mobile Number 2</label><input type="text" class="form-control" name="mobile_number_2" value="<?= esc(old('mobile_number_2')) ?>"></div>
                        <div class="col-md-3"><label class="form-label">Website</label><input type="url" class="form-control" name="website" value="<?= esc(old('website')) ?>"></div>
                        <div class="col-md-3"><label class="form-label">Area</label><input type="text" class="form-control" name="area" value="<?= esc(old('area')) ?>"></div>

                        <div class="col-md-3"><label class="form-label">Category</label>
                            <select class="form-select" name="category">
                                <?php foreach ($categoryOptions as $option): ?>
                                    <option value="<?= esc($option) ?>" <?= old('category') === $option ? 'selected' : '' ?>><?= esc($option !== '' ? $option : 'Select') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3"><label class="form-label">Job Role</label><input type="text" class="form-control" name="job_role" value="<?= esc(old('job_role')) ?>"></div>
                        <div class="col-md-3"><label class="form-label">Timing</label>
                            <select class="form-select" name="timing">
                                <?php foreach ($timingOptions as $option): ?>
                                    <option value="<?= esc($option) ?>" <?= old('timing') === $option ? 'selected' : '' ?>><?= esc($option !== '' ? $option : 'Select') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3"><label class="form-label">Male/Female</label>
                            <select class="form-select" name="gender_preference">
                                <?php foreach ($genderOptions as $option): ?>
                                    <option value="<?= esc($option) ?>" <?= old('gender_preference', 'Any') === $option ? 'selected' : '' ?>><?= esc($option) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-2"><label class="form-label">Number of Person Required</label><input type="number" min="0" class="form-control" name="required_person_count" value="<?= esc(old('required_person_count', '0')) ?>"></div>
                        <div class="col-md-2"><label class="form-label">Budget</label><input type="text" class="form-control" name="budget" value="<?= esc(old('budget')) ?>"></div>
                        <div class="col-md-4"><label class="form-label">Expectation</label><input type="text" class="form-control" name="expectation" value="<?= esc(old('expectation')) ?>"></div>
                        <div class="col-md-4"><label class="form-label">Follower Name</label><input type="text" class="form-control" name="follower_name" value="<?= esc(old('follower_name')) ?>"></div>

                        <div class="col-md-6"><label class="form-label">Remarks</label><textarea class="form-control" name="remarks" rows="2"><?= esc(old('remarks')) ?></textarea></div>
                        <div class="col-md-2"><label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <?php foreach ($statusOptions as $option): ?>
                                    <option value="<?= esc($option) ?>" <?= old('status', 'Active') === $option ? 'selected' : '' ?>><?= esc($option) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4"><label class="form-label">Follow-up 1 (Date & Time)</label><input type="datetime-local" class="form-control" name="follow_up_1" value="<?= esc(old('follow_up_1')) ?>"></div>
                        <div class="col-md-4"><label class="form-label">Follow-up 2 (Date & Time)</label><input type="datetime-local" class="form-control" name="follow_up_2" value="<?= esc(old('follow_up_2')) ?>"></div>
                        <div class="col-md-4"><label class="form-label">Follow-up 3 (Date & Time)</label><input type="datetime-local" class="form-control" name="follow_up_3" value="<?= esc(old('follow_up_3')) ?>"></div>
                    </div>
                </div>
                <div class="modal-footer modal-footer-sticky">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Save Client</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="clientReminderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <form method="post" action="index.php?action=add_client_reminder">
                <?= csrf_field() ?>
                <div class="modal-header modal-header-sticky">
                    <h5 class="modal-title">Set Reminder</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-2">
                    <input type="hidden" name="client_id" id="reminderClientId">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Title</label><input type="text" class="form-control" name="title" id="reminderTitle" value="Client Follow-up Reminder"></div>
                        <div class="col-md-6"><label class="form-label">Reminder Date & Time *</label><input type="datetime-local" class="form-control" name="remind_at" required></div>
                        <div class="col-md-6"><label class="form-label">Email To</label><input type="email" class="form-control" name="email_to" id="reminderEmail"></div>
                        <div class="col-md-6"><label class="form-label">Phone To</label><input type="text" class="form-control" name="phone_to" id="reminderPhone"></div>
                        <div class="col-12"><label class="form-label">Reminder Message</label><textarea class="form-control" name="reminder_message" rows="3"></textarea></div>
                        <div class="col-12">
                            <div class="d-flex gap-3 flex-wrap">
                                <label class="doc-check"><input type="checkbox" name="notify_email" value="1" checked> <span>Email</span></label>
                                <label class="doc-check"><input type="checkbox" name="notify_sms" value="1" checked> <span>SMS/Number</span></label>
                                <label class="doc-check"><input type="checkbox" name="notify_web" value="1" checked> <span>Website Notification</span></label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer modal-footer-sticky">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Save Reminder</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
clear_old();
$extraScripts = [
    '<script src="js/clients.js"></script>',
];
require BASE_PATH . '/app/views/partials/app_layout_end.php';
