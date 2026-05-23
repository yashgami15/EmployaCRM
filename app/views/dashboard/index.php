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

$filterQuery = http_build_query([
    'search' => $search,
    'status' => $status,
    'source' => $source
]);

$oldDocuments = old_array('documents_have');

$pageTitle = 'Employa HR - Candidate';
$headerTitle = 'Candidate';
$currentModule = 'candidate';
$showHeaderMeta = false;
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
    'preferred_work_role_field', 'documents_have', 'additional_notes', 'status', 'source', 'added_on', 'resume_path'
];

$candidateFunnel = [
    ['label' => 'Applied', 'count' => (int) $stats['applied'], 'class' => 'stage-applied'],
    ['label' => 'Interview', 'count' => (int) $stats['interview'], 'class' => 'stage-interview'],
    ['label' => 'Hired', 'count' => (int) $stats['hired'], 'class' => 'stage-hired'],
    ['label' => 'Rejected', 'count' => (int) $stats['rejected'], 'class' => 'stage-rejected'],
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
            <select class="form-select" name="status" onchange="this.form.submit()">
                <option value="">All Status</option>
                <?php foreach ($statusOptions as $item): ?>
                    <option value="<?= esc($item) ?>" <?= $status === $item ? 'selected' : '' ?>><?= esc($item) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-xl-2 col-lg-3 col-sm-6">
            <select class="form-select" name="source" onchange="this.form.submit()">
                <option value="">All Source</option>
                <?php foreach ($sourceOptions as $item): ?>
                    <option value="<?= esc($item) ?>" <?= $source === $item ? 'selected' : '' ?>><?= esc($item) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mt-3">
        <button class="btn btn-outline-secondary" type="button" id="importCsvBtn">Import CSV</button>
        <a class="btn btn-outline-info" href="download_sample.php?type=candidate" download>Download Sample CSV</a>
        <a class="btn btn-outline-secondary" href="index.php?<?= esc($query) ?>">Export Filtered</a>
        <a class="btn btn-outline-secondary disabled" href="#" id="exportSelectedBtn" data-base-url="index.php?action=export_selected">Export Selected</a>
    </div>
</form>

<form id="importCsvForm" method="post" action="index.php?action=import_csv&<?= $filterQuery ?>" enctype="multipart/form-data" class="d-none">
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

        <form id="bulkStatusForm" method="post" action="index.php?action=bulk_status&<?= $filterQuery ?>" class="d-flex align-items-center gap-2 ms-md-auto">
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

        <form id="deleteSelectedForm" method="post" action="index.php?action=delete_candidate&<?= $filterQuery ?>" class="ms-0">
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
                    <th>Email Address</th>
                    <th>Mobile</th>
                    <th>Email ID</th>
                    <th>Date of Birth</th>
                    <th>Full Address</th>
                    <th>Nearby Landmark</th>
                    <th>Native</th>
                    <th>Caste</th>
                    <th>Father Occ.</th>
                    <th>Mother Occ.</th>
                    <th>Sibling & Status</th>
                    <th>Marital Status</th>
                    <th>SSC Details</th>
                    <th>HSC/Diploma</th>
                    <th>Graduate Details</th>
                    <th>Post Grad Details</th>
                    <th>Experience Type</th>
                    <th>Prev Company</th>
                    <th>Prev Desig</th>
                    <th>Prev Roles</th>
                    <th>Prev Start</th>
                    <th>Prev End</th>
                    <th>Prev Salary</th>
                    <th>Curr Company</th>
                    <th>Curr Desig</th>
                    <th>Curr Roles</th>
                    <th>Curr Start</th>
                    <th>Curr Salary</th>
                    <th>Reason Change</th>
                    <th>Preferred Role</th>
                    <th>Skills Set</th>
                    <th>Achievements</th>
                    <th>Expected Salary</th>
                    <th>Pref Location</th>
                    <th>Pref Time</th>
                    <th>Documents</th>
                    <th>Additional Notes</th>
                    <th>Resume</th>
                    <th>Status</th>
                    <th>Source</th>
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
                            </td>
                            <td><?= esc((string) ($candidate['email_address'] ?? '')) ?></td>
                            <td><?= esc((string) ($candidate['mobile_number'] ?: $candidate['phone'])) ?></td>
                            <td><?= esc((string) ($candidate['email_id'] ?: $candidate['email'])) ?></td>
                            <td><?= esc((string) ($candidate['date_of_birth'] ?? '')) ?></td>
                            <td><?= esc((string) ($candidate['full_address'] ?? '')) ?></td>
                            <td><?= esc((string) ($candidate['nearby_landmark'] ?? '')) ?></td>
                            <td><?= esc((string) ($candidate['native_place'] ?? '')) ?></td>
                            <td><?= esc((string) ($candidate['caste'] ?? '')) ?></td>
                            <td><?= esc((string) ($candidate['father_occupation'] ?? '')) ?></td>
                            <td><?= esc((string) ($candidate['mother_occupation'] ?? '')) ?></td>
                            <td><?= esc((string) ($candidate['sibling_status'] ?? '')) ?></td>
                            <td><?= esc((string) ($candidate['marital_status'] ?? '')) ?></td>
                            <td><?= esc((string) ($candidate['ssc_details'] ?? '')) ?></td>
                            <td><?= esc((string) ($candidate['hsc_diploma_details'] ?? '')) ?></td>
                            <td><?= esc((string) ($candidate['graduate_details'] ?? '')) ?></td>
                            <td><?= esc((string) ($candidate['post_graduate_details'] ?? '')) ?></td>
                            <td><?= esc((string) ($candidate['experience_type'] ?? '')) ?></td>
                            <td><?= esc((string) ($candidate['previous_company_city'] ?? '')) ?></td>
                            <td><?= esc((string) ($candidate['previous_designation'] ?? '')) ?></td>
                            <td><?= esc((string) ($candidate['previous_roles'] ?? '')) ?></td>
                            <td><?= esc((string) ($candidate['previous_start_date'] ?? '')) ?></td>
                            <td><?= esc((string) ($candidate['previous_end_date'] ?? '')) ?></td>
                            <td><?= esc((string) ($candidate['previous_salary_month'] ?? '')) ?></td>
                            <td><?= esc((string) ($candidate['current_company_city'] ?? '')) ?></td>
                            <td><?= esc((string) ($candidate['current_designation'] ?? '')) ?></td>
                            <td><?= esc((string) ($candidate['current_roles'] ?? '')) ?></td>
                            <td><?= esc((string) ($candidate['current_start_date'] ?? '')) ?></td>
                            <td><?= esc((string) ($candidate['current_salary_month'] ?? '')) ?></td>
                            <td><?= esc((string) ($candidate['reason_for_change'] ?? '')) ?></td>
                            <td><?= esc((string) ($candidate['preferred_work_role_field'] ?: $candidate['role'] ?: '-')) ?></td>
                            <td><?= esc((string) ($candidate['skills_set'] ?: $candidate['skills'] ?: '-')) ?></td>
                            <td><?= esc((string) ($candidate['achievements'] ?? '')) ?></td>
                            <td><?= esc((string) ($candidate['expected_salary_month'] ?? '')) ?></td>
                            <td><?= esc((string) ($candidate['preferred_location'] ?: '-')) ?></td>
                            <td><?= esc((string) ($candidate['preferred_working_time'] ?? '')) ?></td>
                            <td><?= esc((string) ($candidate['documents_have'] ?? '')) ?></td>
                            <td><?= esc((string) ($candidate['additional_notes'] ?? '')) ?></td>
                            <td>
                                <?php if (!empty($candidate['resume_path'])): ?>
                                    <a href="<?= esc((string) $candidate['resume_path']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary">View</a>
                                <?php else: ?>
                                    <span class="text-secondary small">Not uploaded</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge status-badge status-<?= esc($statusClass) ?>"><?= esc((string) $candidate['status']) ?></span></td>
                            <td><?= esc((string) $candidate['source'] ?? '') ?></td>
                            <td><?= esc((string) $candidate['added_on']) ?></td>
                            <td class="sticky-action">
                                <div class="d-flex gap-1 flex-nowrap align-items-center">
                                    <a href="tel:<?= esc((string) ($candidate['mobile_number'] ?: $candidate['phone'])) ?>" class="btn btn-sm btn-success" title="Call" onclick="logContactActivity(event, 'candidate', <?= (int)$candidate['id'] ?>, 'Call', this.href)"><i class="bi bi-telephone"></i></a>
                                    <a href="mailto:<?= esc((string) ($candidate['email_id'] ?: $candidate['email'])) ?>" class="btn btn-sm btn-warning" title="Email" onclick="logContactActivity(event, 'candidate', <?= (int)$candidate['id'] ?>, 'Mail', this.href)"><i class="bi bi-envelope"></i></a>
                                    <button class="btn btn-sm btn-outline-primary candidate-edit-btn" title="Edit" data-candidate-mode="edit" data-candidate-id="<?= (int) $candidate['id'] ?>" <?= $candidateDataAttrs ?> data-bs-toggle="modal" data-bs-target="#addCandidateModal"><i class="bi bi-pencil-square"></i></button>
                                    <button class="btn btn-sm btn-outline-info" title="Remind" data-bs-toggle="modal" data-bs-target="#candidateReminderModal" data-candidate-id="<?= (int) $candidate['id'] ?>" data-candidate-name="<?= esc((string) $candidate['full_name']) ?>" data-candidate-email="<?= esc((string) ($candidate['email_id'] ?: $candidate['email'])) ?>" data-candidate-phone="<?= esc((string) ($candidate['mobile_number'] ?: $candidate['phone'])) ?>"><i class="bi bi-bell"></i></button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$oldDocuments = old_array('documents_have');
?>

<div class="modal fade" id="addCandidateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <form method="post" action="index.php?action=add_candidate&<?= $filterQuery ?>" enctype="multipart/form-data" id="candidateProfileForm">
                <?= csrf_field() ?>
                <input type="hidden" name="candidate_id" id="candidateIdInput">
                <div class="modal-header modal-header-sticky">
                    <h5 class="modal-title" id="candidateModalTitle">Add Candidate Full Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-2">
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom bg-light p-3 rounded">
                        <div>
                            <h6 class="mb-1 text-primary"><i class="bi bi-magic me-1"></i> Autofill with AI (Resume Parser)</h6>
                            <p class="mb-0 small text-muted">Upload a Candidate's Resume (PDF) and our AI will automatically read it and fill out this form for you to review.</p>
                        </div>
                        <div>
                            <input type="file" id="aiResumeUpload" class="d-none" accept="application/pdf">
                            <button type="button" class="btn btn-primary btn-sm px-3 shadow-sm" onclick="document.getElementById('aiResumeUpload').click();">
                                <i class="bi bi-upload me-1"></i> Upload Resume (PDF)
                            </button>
                        </div>
                    </div>
                    
                    <div id="aiLoadingIndicator" class="d-none text-center p-4 bg-light rounded mb-3 border border-primary border-opacity-25">
                        <div class="spinner-border text-primary mb-2" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <h6 class="text-primary mb-0">AI is analyzing the resume...</h6>
                        <p class="small text-muted mb-0 mt-1">This takes about 5 to 10 seconds. Please wait.</p>
                    </div>
                    
                    <h6 class="section-title">Personal Details</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4"><label class="form-label">Full Name <span class="text-danger-asterisk">*</span></label><input type="text" class="form-control" name="full_name" value="<?= esc(old('full_name')) ?>" required></div>
                        <div class="col-md-4"><label class="form-label">Mobile Number <span class="text-danger-asterisk">*</span></label><input type="tel" class="form-control" name="mobile_number" value="<?= esc(old('mobile_number')) ?>" required pattern="\d+" oninput="this.value=this.value.replace(/[^0-9]/g,'');"></div>
                        <div class="col-md-4"><label class="form-label">Email ID</label><input type="email" class="form-control" name="email_id" value="<?= esc(old('email_id')) ?>"></div>
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
                        <div class="col-md-12"><label class="form-label">Experience Type</label>
                            <select class="form-select" name="experience_type" id="experienceTypeSelect">
                                <?php foreach (experience_type_options() as $item): ?>
                                    <option value="<?= esc($item) ?>" <?= old('experience_type', 'Fresher') === $item ? 'selected' : '' ?>><?= esc($item) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 experienced-only"><label class="form-label">Company Name & City</label><input type="text" class="form-control" name="previous_company_city" value="<?= esc(old('previous_company_city')) ?>"></div>
                        <div class="col-md-4 experienced-only"><label class="form-label">Designation</label><input type="text" class="form-control" name="previous_designation" value="<?= esc(old('previous_designation')) ?>"></div>
                        <div class="col-md-12 experienced-only"><label class="form-label">Roles & Responsibilities</label><textarea class="form-control" name="previous_roles" rows="2"><?= esc(old('previous_roles')) ?></textarea></div>
                        <div class="col-md-4 experienced-only"><label class="form-label">Starting Date</label><input type="date" class="form-control" name="previous_start_date" value="<?= esc(old('previous_start_date')) ?>"></div>
                        <div class="col-md-4 experienced-only"><label class="form-label">End Date</label><input type="date" class="form-control" name="previous_end_date" value="<?= esc(old('previous_end_date')) ?>"></div>
                        <div class="col-md-4 experienced-only"><label class="form-label">Salary Per Month</label><input type="number" min="0" class="form-control" name="previous_salary_month" value="<?= esc(old('previous_salary_month')) ?>"></div>
                    </div>

                    <h6 class="section-title experienced-only">Current Job Details</h6>
                    <div class="row g-3 mb-3 experienced-only">
                        <div class="col-md-4"><label class="form-label">Current Company Name & City</label><input type="text" class="form-control" name="current_company_city" value="<?= esc(old('current_company_city')) ?>"></div>
                        <div class="col-md-4"><label class="form-label">Current Designation</label><input type="text" class="form-control" name="current_designation" value="<?= esc(old('current_designation')) ?>"></div>
                        <div class="col-md-4"><label class="form-label">Current Salary (Per Month)</label><input type="number" min="0" class="form-control" name="current_salary_month" value="<?= esc(old('current_salary_month')) ?>"></div>
                        <div class="col-md-12"><label class="form-label">Current Roles & Responsibilities</label><textarea class="form-control" name="current_roles" rows="2"><?= esc(old('current_roles')) ?></textarea></div>
                        <div class="col-md-4"><label class="form-label">Current Job Starting Date</label><input type="date" class="form-control" name="current_start_date" value="<?= esc(old('current_start_date')) ?>"></div>
                        <div class="col-md-8"><label class="form-label">Reason for Change</label><input type="text" class="form-control" name="reason_for_change" value="<?= esc(old('reason_for_change')) ?>"></div>
                    </div>

                    <h6 class="section-title">Skills, Expectations & Documents</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6"><label class="form-label">Set of Skills</label><textarea class="form-control" name="skills_set" rows="2"><?= esc(old('skills_set')) ?></textarea></div>
                        <div class="col-md-6"><label class="form-label">Achievements</label><textarea class="form-control" name="achievements" rows="2"><?= esc(old('achievements')) ?></textarea></div>
                        <div class="col-md-3"><label class="form-label">Expected Salary (Per Month)</label><input type="number" min="0" class="form-control" name="expected_salary_month" value="<?= esc(old('expected_salary_month')) ?>"></div>
                        <div class="col-md-3"><label class="form-label">Preferred Location</label><input type="text" class="form-control" name="preferred_location" value="<?= esc(old('preferred_location')) ?>"></div>
                        <div class="col-md-3"><label class="form-label">Preferred Working Time</label>
                            <select class="form-select" name="preferred_working_time">
                                <?php foreach ($timingOptions as $item): ?>
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
                        <div class="col-md-4">
                            <label class="form-label">Resume Upload (PDF/DOC/DOCX)</label>
                            <input type="file" class="form-control" name="resume_file" accept=".pdf,.doc,.docx">
                            <div id="currentResumeDisplay" class="mt-1 small"></div>
                        </div>
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

                    <div id="timelineSection" style="display: none;">
                        <hr class="my-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="section-title mb-0"><i class="bi bi-clock-history"></i> Activity Timeline</h6>
                            <span class="badge bg-info-subtle text-info border border-info-subtle">History</span>
                        </div>
                        <div id="candidateTimeline" class="timeline-compact p-2 rounded" style="max-height: 250px; overflow-y: auto;">
                            <p class="text-secondary small mb-0 text-center py-3">Loading history...</p>
                        </div>
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

<div class="modal fade" id="candidateReminderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <form method="post" action="index.php?action=add_candidate_reminder&<?= $filterQuery ?>">
                <?= csrf_field() ?>
                <div class="modal-header modal-header-sticky">
                    <h5 class="modal-title">Set Reminder</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-2">
                    <input type="hidden" name="candidate_id" id="reminderCandidateId">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Title</label><input type="text" class="form-control" name="title" id="reminderCandidateTitle" value="Candidate Follow-up Reminder"></div>
                        <div class="col-md-6"><label class="form-label">Reminder Date & Time *</label><input type="datetime-local" class="form-control" name="remind_at" required></div>
                        <div class="col-md-6"><label class="form-label">Email To</label><input type="email" class="form-control" name="email_to" id="reminderCandidateEmail"></div>
                        <div class="col-md-6"><label class="form-label">Phone To</label><input type="text" class="form-control" name="phone_to" id="reminderCandidatePhone"></div>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const aiResumeUpload = document.getElementById('aiResumeUpload');
    if (aiResumeUpload) {
        aiResumeUpload.addEventListener('change', function() {
            if (this.files.length === 0) return;
            const file = this.files[0];
            
            if (file.type !== 'application/pdf') {
                alert('Please upload a PDF file for AI parsing.');
                this.value = '';
                return;
            }
            
            const formData = new FormData();
            formData.append('resume', file);
            
            const loadingIndicator = document.getElementById('aiLoadingIndicator');
            loadingIndicator.classList.remove('d-none');
            
            fetch('index.php?action=parse_resume_ajax', {
                method: 'POST',
                body: formData
            })
            .then(async res => {
                const text = await res.text();
                try {
                    return JSON.parse(text);
                } catch(e) {
                    throw new Error("Server error: " + text.substring(0, 100));
                }
            })
            .then(data => {
                loadingIndicator.classList.add('d-none');
                this.value = '';
                
                if (data.status === 'success') {
                    const candidateData = data.data;
                    
                    // Autofill the form
                    if (candidateData.full_name) document.querySelector('[name="full_name"]').value = candidateData.full_name;
                    if (candidateData.email_address) document.querySelector('[name="email_id"]').value = candidateData.email_address;
                    if (candidateData.mobile_number) document.querySelector('[name="mobile_number"]').value = candidateData.mobile_number.replace(/[^0-9]/g, '');
                    if (candidateData.preferred_work_role_field) document.querySelector('[name="preferred_work_role_field"]').value = candidateData.preferred_work_role_field;
                    if (candidateData.skills_set) document.querySelector('[name="skills_set"]').value = candidateData.skills_set;
                    if (candidateData.current_company_city) document.querySelector('[name="current_company_city"]').value = candidateData.current_company_city;
                    if (candidateData.current_designation) document.querySelector('[name="current_designation"]').value = candidateData.current_designation;
                    if (candidateData.expected_salary_month) document.querySelector('[name="expected_salary_month"]').value = candidateData.expected_salary_month.replace(/[^0-9]/g, '');
                    
                    if (candidateData.experience_type) {
                        const expSelect = document.querySelector('[name="experience_type"]');
                        for (let i = 0; i < expSelect.options.length; i++) {
                            if (expSelect.options[i].value.toLowerCase() === candidateData.experience_type.toLowerCase()) {
                                expSelect.selectedIndex = i;
                                break;
                            }
                        }
                    }
                    
                    if (data.message) {
                        alert(data.message);
                    } else {
                        alert('Resume parsed successfully! Please review the auto-filled details.');
                    }
                } else {
                    alert(data.message || 'An error occurred during AI parsing.');
                }
            })
            .catch(err => {
                loadingIndicator.classList.add('d-none');
                this.value = '';
                alert('Connection/Server Error: ' + err.message);
            });
        });
    }
});
</script>

<?php
clear_old();
require BASE_PATH . '/app/views/partials/app_layout_end.php';
