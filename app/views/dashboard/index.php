<?php
/** @var array $user */
/** @var array|null $flash */
/** @var array $candidates */
/** @var array $stats */
/** @var array $statusOptions */
/** @var array $sourceOptions */
/** @var array $documentsOptions */

$search = trim((string) ($_GET['search'] ?? ''));
$status = trim((string) ($_GET['status'] ?? ''));
$source = trim((string) ($_GET['source'] ?? ''));
$query = http_build_query([
    'action' => 'export_filtered',
    'search' => $search,
    'status' => $status,
    'source' => $source,
]);

$oldDocuments = old_array('documents_have');

$pageTitle = 'Employa HR - Candidate';
$headerTitle = 'Candidate';
$currentModule = 'candidate';
$headerActions = '<button class="btn btn-success fw-semibold" data-candidate-mode="add" data-bs-toggle="modal" data-bs-target="#addCandidateModal"><i class="bi bi-person-plus"></i> Add Candidate</button>';

$candidateFormFields = [
    'email_address', 'full_name', 'mobile_number', 'email_id', 'date_of_birth', 'full_address',
    'nearby_landmark', 'native_place', 'caste', 'father_occupation', 'mother_occupation',
    'sibling_status', 'marital_status', 'ssc_details', 'hsc_diploma_details', 'graduate_details',
    'post_graduate_details', 'experience_type', 'previous_company_city', 'previous_designation',
    'previous_roles', 'previous_start_date', 'previous_end_date', 'previous_salary_month',
    'current_company_city', 'current_designation', 'current_roles', 'current_start_date',
    'current_salary_month', 'reason_for_change', 'skills_set', 'achievements',
    'expected_salary_month', 'preferred_location', 'preferred_working_time',
    'preferred_work_role_field', 'documents_have', 'additional_notes', 'status', 'source', 'added_on',
];

$candidateFunnel = [
    ['label' => 'Applied', 'count' => (int) $stats['applied'], 'class' => 'stage-applied'],
    ['label' => 'Interview', 'count' => (int) $stats['interview'], 'class' => 'stage-interview'],
    ['label' => 'Hired', 'count' => (int) $stats['hired'], 'class' => 'stage-hired'],
];

require BASE_PATH . '/app/views/partials/app_layout_start.php';
?>

<div class="module-heading mb-3">
    <div>
        <h1 class="greeting mb-1">Candidates</h1>
        <p class="text-secondary mb-0">Dedicated candidate pipeline, profile data and matching status.</p>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-6 col-md-3"><article class="stat-card stat-total"><i class="bi bi-people"></i><small>Total Candidates</small><h3><?= (int) $stats['total'] ?></h3></article></div>
    <div class="col-6 col-md-3"><article class="stat-card stat-applied"><i class="bi bi-send-check"></i><small>Applied</small><h3><?= (int) $stats['applied'] ?></h3></article></div>
    <div class="col-6 col-md-3"><article class="stat-card stat-interview"><i class="bi bi-calendar2-check"></i><small>Interview Stage</small><h3><?= (int) $stats['interview'] ?></h3></article></div>
    <div class="col-6 col-md-3"><article class="stat-card stat-hired"><i class="bi bi-award"></i><small>Hired</small><h3><?= (int) $stats['hired'] ?></h3></article></div>
</div>

<div class="card card-soft p-3 mb-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h5 class="mb-1">Candidate Funnel</h5>
            <p class="text-secondary mb-0 small">Dedicated flow from applied candidates to final hiring.</p>
        </div>
        <span class="chip"><?= (int) $stats['total'] ?> total profiles</span>
    </div>
    <div class="candidate-funnel candidate-funnel-wide">
        <?php foreach ($candidateFunnel as $stageItem): ?>
            <?php $percent = (int) $stats['total'] > 0 ? max(8, round(((int) $stageItem['count'] / (int) $stats['total']) * 100)) : 8; ?>
            <div class="stage-row <?= esc($stageItem['class']) ?>" style="--stage-width: <?= $percent ?>%;">
                <span><?= esc($stageItem['label']) ?></span>
                <strong><?= (int) $stageItem['count'] ?></strong>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<form class="card card-soft p-3 mb-3" method="get" action="index.php">
    <input type="hidden" name="action" value="candidate">
    <div class="row g-2 align-items-center">
        <div class="col-xl-7 col-lg-6">
            <input type="text" class="form-control" name="search" placeholder="Search by name, email, mobile, role, skill" value="<?= esc($search) ?>">
        </div>
        <div class="col-xl-2 col-lg-3 col-sm-6">
            <select class="form-select" name="status">
                <option value="">All Status</option>
                <?php foreach ($statusOptions as $item): ?>
                    <option value="<?= esc($item) ?>" <?= $status === $item ? 'selected' : '' ?>><?= esc($item) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-xl-2 col-lg-3 col-sm-6">
            <select class="form-select" name="source">
                <option value="">All Source</option>
                <?php foreach ($sourceOptions as $item): ?>
                    <option value="<?= esc($item) ?>" <?= $source === $item ? 'selected' : '' ?>><?= esc($item) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-xl-1 col-12 d-grid">
            <button class="btn btn-outline-secondary" type="submit">Go</button>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mt-3">
        <button class="btn btn-outline-secondary" type="button" id="importCsvBtn">Import CSV</button>
        <a class="btn btn-outline-secondary" href="index.php?<?= esc($query) ?>">Export Filtered</a>
        <a class="btn btn-outline-secondary disabled" href="#" id="exportSelectedBtn" data-base-url="index.php?action=export_selected">Export Selected</a>
    </div>
</form>

<form id="importCsvForm" method="post" action="index.php?action=import_csv" enctype="multipart/form-data" class="d-none">
    <?= csrf_field() ?>
    <input type="file" id="csvFileInput" name="csv_file" accept=".csv,text/csv">
</form>

<div class="card card-soft p-3 mb-3">
    <div class="d-flex flex-wrap gap-3 align-items-center mb-3">
        <label class="d-flex align-items-center gap-2 mb-0">
            <input type="checkbox" class="form-check-input" id="selectVisibleCheckbox">
            <span>Select visible</span>
        </label>
        <strong class="text-secondary"><span id="selectedCount">0</span> selected</strong>

        <form id="bulkStatusForm" method="post" action="index.php?action=bulk_status" class="d-flex align-items-center gap-2 ms-md-auto">
            <?= csrf_field() ?>
            <input type="hidden" name="selected_ids" id="bulkStatusIds">
            <select name="bulk_status" class="form-select" id="bulkStatusSelect">
                <option value="">Bulk status</option>
                <?php foreach ($statusOptions as $item): ?>
                    <option value="<?= esc($item) ?>"><?= esc($item) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-outline-secondary" id="applyBulkBtn">Apply</button>
        </form>

        <form id="deleteSelectedForm" method="post" action="index.php?action=delete_selected" class="ms-0">
            <?= csrf_field() ?>
            <input type="hidden" name="selected_ids" id="deleteSelectedIds">
            <button type="submit" class="btn btn-outline-danger" id="deleteSelectedBtn">Delete Selected</button>
        </form>
    </div>

    <div class="table-responsive data-table-wrap">
        <table class="table table-candidates align-middle mb-0" id="candidateTable">
            <thead>
                <tr>
                    <th style="width:44px;"></th>
                    <th>Full Name</th>
                    <th>Mobile</th>
                    <th>Email ID</th>
                    <th>Preferred Role</th>
                    <th>Skills</th>
                    <th>AI Auto Match</th>
                    <th>Resume</th>
                    <th>Status</th>
                    <th>Added On</th>
                    <th class="sticky-action">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($candidates)): ?>
                    <tr><td colspan="11" class="text-center py-5 text-secondary">No candidate data found for current filters.</td></tr>
                <?php else: ?>
                    <?php foreach ($candidates as $candidate): ?>
                        <?php
                        $statusClass = strtolower(str_replace(' ', '_', (string) $candidate['status']));
                        $candidateDataAttrs = '';
                        foreach ($candidateFormFields as $field) {
                            $candidateDataAttrs .= ' data-' . str_replace('_', '-', $field) . '="' . esc((string) ($candidate[$field] ?? '')) . '"';
                        }
                        ?>
                        <tr>
                            <td><input type="checkbox" class="form-check-input row-checkbox" value="<?= (int) $candidate['id'] ?>"></td>
                            <td>
                                <p class="candidate-name mb-0"><?= esc((string) $candidate['full_name']) ?></p>
                                <small class="text-secondary"><?= esc((string) ($candidate['preferred_location'] ?: '-')) ?></small>
                            </td>
                            <td><?= esc((string) ($candidate['mobile_number'] ?: $candidate['phone'])) ?></td>
                            <td><?= esc((string) ($candidate['email_id'] ?: $candidate['email'])) ?></td>
                            <td><?= esc((string) ($candidate['preferred_work_role_field'] ?: $candidate['role'] ?: '-')) ?></td>
                            <td><?= esc((string) ($candidate['skills_set'] ?: $candidate['skills'] ?: '-')) ?></td>
                            <td>
                                <?php if (empty($candidate['ai_matches'])): ?>
                                    <span class="text-secondary small">No match yet</span>
                                <?php else: ?>
                                    <?php foreach ($candidate['ai_matches'] as $match): ?>
                                        <div class="small mb-1">
                                            <strong><?= esc((string) $match['company_name']) ?></strong>
                                            <span class="text-success">(<?= (int) $match['score'] ?>%)</span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($candidate['resume_path'])): ?>
                                    <a href="<?= esc((string) $candidate['resume_path']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary">View</a>
                                <?php else: ?>
                                    <span class="text-secondary small">Not uploaded</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge status-badge status-<?= esc($statusClass) ?>"><?= esc((string) $candidate['status']) ?></span></td>
                            <td><?= esc((string) $candidate['added_on']) ?></td>
                            <td class="sticky-action">
                                <button
                                    class="btn btn-sm btn-outline-primary candidate-edit-btn"
                                    type="button"
                                    data-candidate-mode="edit"
                                    data-candidate-id="<?= (int) $candidate['id'] ?>"
                                    <?= $candidateDataAttrs ?>
                                    data-bs-toggle="modal"
                                    data-bs-target="#addCandidateModal">
                                    <i class="bi bi-pencil-square"></i> Edit
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="addCandidateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <form method="post" action="index.php?action=add_candidate" enctype="multipart/form-data" id="candidateProfileForm">
                <?= csrf_field() ?>
                <input type="hidden" name="candidate_id" id="candidateIdInput">
                <div class="modal-header modal-header-sticky">
                    <h5 class="modal-title" id="candidateModalTitle">Add Candidate Full Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-2">
                    <h6 class="section-title">Personal Details</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-3"><label class="form-label">Email Address</label><input type="email" class="form-control" name="email_address" value="<?= esc(old('email_address')) ?>"></div>
                        <div class="col-md-3"><label class="form-label">Full Name *</label><input type="text" class="form-control" name="full_name" value="<?= esc(old('full_name')) ?>" required></div>
                        <div class="col-md-3"><label class="form-label">Mobile Number *</label><input type="text" class="form-control" name="mobile_number" value="<?= esc(old('mobile_number')) ?>" required></div>
                        <div class="col-md-3"><label class="form-label">Email ID *</label><input type="email" class="form-control" name="email_id" value="<?= esc(old('email_id')) ?>" required></div>
                        <div class="col-md-3"><label class="form-label">Date of Birth</label><input type="date" class="form-control" name="date_of_birth" value="<?= esc(old('date_of_birth')) ?>"></div>
                        <div class="col-md-6"><label class="form-label">Full Address</label><input type="text" class="form-control" name="full_address" value="<?= esc(old('full_address')) ?>"></div>
                        <div class="col-md-3"><label class="form-label">Nearby Famous Landmark</label><input type="text" class="form-control" name="nearby_landmark" value="<?= esc(old('nearby_landmark')) ?>"></div>
                        <div class="col-md-3"><label class="form-label">Native</label><input type="text" class="form-control" name="native_place" value="<?= esc(old('native_place')) ?>"></div>
                        <div class="col-md-3"><label class="form-label">Caste</label><input type="text" class="form-control" name="caste" value="<?= esc(old('caste')) ?>"></div>
                        <div class="col-md-3"><label class="form-label">Father Occupation</label><input type="text" class="form-control" name="father_occupation" value="<?= esc(old('father_occupation')) ?>"></div>
                        <div class="col-md-3"><label class="form-label">Mother Occupation</label><input type="text" class="form-control" name="mother_occupation" value="<?= esc(old('mother_occupation')) ?>"></div>
                        <div class="col-md-3"><label class="form-label">Sibling & Status</label><input type="text" class="form-control" name="sibling_status" value="<?= esc(old('sibling_status')) ?>"></div>
                        <div class="col-md-3"><label class="form-label">Marital Status</label>
                            <select class="form-select" name="marital_status">
                                <option value="">Select</option>
                                <?php foreach (marital_status_options() as $item): ?>
                                    <option value="<?= esc($item) ?>" <?= old('marital_status') === $item ? 'selected' : '' ?>><?= esc($item) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <h6 class="section-title">Academic Details</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6"><label class="form-label">SSC (School, City, Percentage, Year)</label><input type="text" class="form-control" name="ssc_details" value="<?= esc(old('ssc_details')) ?>"></div>
                        <div class="col-md-6"><label class="form-label">HSC/Diploma (School, City, Percentage, Year)</label><input type="text" class="form-control" name="hsc_diploma_details" value="<?= esc(old('hsc_diploma_details')) ?>"></div>
                        <div class="col-md-6"><label class="form-label">Graduate (College, City, Percentage, Year)</label><input type="text" class="form-control" name="graduate_details" value="<?= esc(old('graduate_details')) ?>"></div>
                        <div class="col-md-6"><label class="form-label">Post Graduate (College, City, Percentage, Year)</label><input type="text" class="form-control" name="post_graduate_details" value="<?= esc(old('post_graduate_details')) ?>"></div>
                    </div>

                    <h6 class="section-title">Work Experience</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4"><label class="form-label">Experience Type</label>
                            <select class="form-select" name="experience_type">
                                <?php foreach (experience_type_options() as $item): ?>
                                    <option value="<?= esc($item) ?>" <?= old('experience_type', 'Fresher') === $item ? 'selected' : '' ?>><?= esc($item) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4"><label class="form-label">Company Name & City</label><input type="text" class="form-control" name="previous_company_city" value="<?= esc(old('previous_company_city')) ?>"></div>
                        <div class="col-md-4"><label class="form-label">Designation</label><input type="text" class="form-control" name="previous_designation" value="<?= esc(old('previous_designation')) ?>"></div>
                        <div class="col-md-12"><label class="form-label">Roles & Responsibilities</label><textarea class="form-control" name="previous_roles" rows="2"><?= esc(old('previous_roles')) ?></textarea></div>
                        <div class="col-md-4"><label class="form-label">Starting Date</label><input type="date" class="form-control" name="previous_start_date" value="<?= esc(old('previous_start_date')) ?>"></div>
                        <div class="col-md-4"><label class="form-label">End Date</label><input type="date" class="form-control" name="previous_end_date" value="<?= esc(old('previous_end_date')) ?>"></div>
                        <div class="col-md-4"><label class="form-label">Salary Per Month</label><input type="text" class="form-control" name="previous_salary_month" value="<?= esc(old('previous_salary_month')) ?>"></div>
                    </div>

                    <h6 class="section-title">Current Job Details</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4"><label class="form-label">Current Company Name & City</label><input type="text" class="form-control" name="current_company_city" value="<?= esc(old('current_company_city')) ?>"></div>
                        <div class="col-md-4"><label class="form-label">Current Designation</label><input type="text" class="form-control" name="current_designation" value="<?= esc(old('current_designation')) ?>"></div>
                        <div class="col-md-4"><label class="form-label">Current Salary (Per Month)</label><input type="text" class="form-control" name="current_salary_month" value="<?= esc(old('current_salary_month')) ?>"></div>
                        <div class="col-md-12"><label class="form-label">Current Roles & Responsibilities</label><textarea class="form-control" name="current_roles" rows="2"><?= esc(old('current_roles')) ?></textarea></div>
                        <div class="col-md-4"><label class="form-label">Current Job Starting Date</label><input type="date" class="form-control" name="current_start_date" value="<?= esc(old('current_start_date')) ?>"></div>
                        <div class="col-md-8"><label class="form-label">Reason for Change</label><input type="text" class="form-control" name="reason_for_change" value="<?= esc(old('reason_for_change')) ?>"></div>
                    </div>

                    <h6 class="section-title">Skills, Expectations & Documents</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6"><label class="form-label">Set of Skills</label><textarea class="form-control" name="skills_set" rows="2"><?= esc(old('skills_set')) ?></textarea></div>
                        <div class="col-md-6"><label class="form-label">Achievements</label><textarea class="form-control" name="achievements" rows="2"><?= esc(old('achievements')) ?></textarea></div>
                        <div class="col-md-3"><label class="form-label">Expected Salary (Per Month)</label><input type="text" class="form-control" name="expected_salary_month" value="<?= esc(old('expected_salary_month')) ?>"></div>
                        <div class="col-md-3"><label class="form-label">Preferred Location</label><input type="text" class="form-control" name="preferred_location" value="<?= esc(old('preferred_location')) ?>"></div>
                        <div class="col-md-3"><label class="form-label">Preferred Working Time</label>
                            <select class="form-select" name="preferred_working_time">
                                <?php foreach (['', 'Full Time', 'Part Time', 'Day Shift', 'Night Shift', 'Flexible'] as $item): ?>
                                    <option value="<?= esc($item) ?>" <?= old('preferred_working_time') === $item ? 'selected' : '' ?>><?= esc($item !== '' ? $item : 'Select') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3"><label class="form-label">Preferred Work Role & Field</label><input type="text" class="form-control" name="preferred_work_role_field" value="<?= esc(old('preferred_work_role_field')) ?>"></div>
                        <div class="col-md-6">
                            <label class="form-label">Which document have (multi-select)</label>
                            <div class="document-grid">
                                <?php foreach ($documentsOptions as $item): ?>
                                    <label class="doc-check"><input type="checkbox" name="documents_have[]" value="<?= esc($item) ?>" <?= in_array($item, $oldDocuments, true) ? 'checked' : '' ?>> <span><?= esc($item) ?></span></label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="col-md-6"><label class="form-label">Additional Notes</label><textarea class="form-control" name="additional_notes" rows="3"><?= esc(old('additional_notes')) ?></textarea></div>
                        <div class="col-md-4"><label class="form-label">Resume Upload (PDF/DOC/DOCX)</label><input type="file" class="form-control" name="resume_file" accept=".pdf,.doc,.docx"></div>
                        <div class="col-md-2"><label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <?php foreach ($statusOptions as $item): ?>
                                    <option value="<?= esc($item) ?>" <?= old('status', 'Applied') === $item ? 'selected' : '' ?>><?= esc($item) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2"><label class="form-label">Source</label>
                            <select class="form-select" name="source">
                                <?php foreach ($sourceOptions as $item): ?>
                                    <option value="<?= esc($item) ?>" <?= old('source', 'Direct') === $item ? 'selected' : '' ?>><?= esc($item) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2"><label class="form-label">Added On</label><input type="date" class="form-control" name="added_on" value="<?= esc(old('added_on', date('Y-m-d'))) ?>"></div>
                    </div>
                </div>
                <div class="modal-footer modal-footer-sticky">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success" id="candidateSubmitBtn">Save Candidate</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
clear_old();
require BASE_PATH . '/app/views/partials/app_layout_end.php';
