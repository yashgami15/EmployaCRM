<?php
/** @var array $user */
/** @var array|null $flash */
/** @var array $suggestions */

$pageTitle = 'Employa HR - AI Matcher';
$headerTitle = 'AI Suggestions';
$currentModule = 'ai_matcher';

require BASE_PATH . '/app/views/partials/app_layout_start.php';
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div>
        <h1 class="greeting mb-0">AI Matcher</h1>
        <p class="text-secondary mb-0">Intelligent candidate suggestions for open positions.</p>
    </div>
    <button class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#aiInfoModal"><i class="bi bi-info-circle"></i> How it works?</button>
</div>

<!-- AI Info Modal -->
<div class="modal fade" id="aiInfoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="bi bi-robot"></i> AI Matching Criteria</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h6>The AI Matcher analyzes the following data points:</h6>
                <ul class="list-group list-group-flush mb-0">
                    <li class="list-group-item"><i class="bi bi-x-circle text-danger me-2"></i> <strong>Strict Salary:</strong> Candidates are rejected if their expected salary is above the client budget.</li>
                    <li class="list-group-item"><i class="bi bi-geo-alt text-primary me-2"></i> <strong>Real Distance:</strong> Calculates actual distance (km) between locations using AI.</li>
                    <li class="list-group-item"><i class="bi bi-check2-circle text-success me-2"></i> <strong>Skills Set:</strong> Scans candidate skills against client expectations.</li>
                    <li class="list-group-item"><i class="bi bi-check2-circle text-success me-2"></i> <strong>Job Role:</strong> Matches preferred work roles and designations.</li>
                </ul>
                <p class="mt-3 mb-0 small text-muted">The percentage score represents the overall keyword overlap and alignment between the candidate's profile and the job requirement.</p>
            </div>
        </div>
    </div>
</div>

<?php if (empty($suggestions)): ?>
    <div class="card card-soft p-5 text-center">
        <i class="bi bi-robot fs-1 text-secondary mb-3"></i>
        <h5>No matches found</h5>
        <p class="text-secondary">AI could not find any suitable candidates for the current active clients based on skills and roles.</p>
    </div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($suggestions as $clientMatch): ?>
            <div class="col-12 col-xl-6">
                <div class="card card-soft h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h5 class="mb-1 text-primary"><i class="bi bi-building"></i> <?= esc($clientMatch['company_name']) ?></h5>
                                <p class="text-secondary mb-0 small"><i class="bi bi-briefcase"></i> <?= esc($clientMatch['job_role']) ?> &bull; <i class="bi bi-geo-alt"></i> <?= esc($clientMatch['area']) ?></p>
                            </div>
                            <span class="badge bg-light text-dark border"><?= count($clientMatch['suggested_candidates']) ?> Matches</span>
                        </div>
                        
                        <div class="list-group list-group-flush border-top pt-2">
                            <?php foreach ($clientMatch['suggested_candidates'] as $candidate): ?>
                                <div class="list-group-item px-0 py-3 bg-transparent border-bottom">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1 fw-bold"><?= esc($candidate['candidate_name']) ?> <span class="badge bg-success ms-2"><?= $candidate['match_score'] ?>% Match</span></h6>
                                            <p class="mb-1 small text-secondary"><strong>Role:</strong> <?= esc($candidate['role'] ?: '-') ?> &bull; <strong>Exp. Salary:</strong> <?= esc($candidate['expected_salary'] ?: '-') ?></p>
                                            <p class="mb-1 small text-muted text-truncate" style="max-width: 350px;"><strong>Skills:</strong> <?= esc($candidate['skills'] ?: '-') ?></p>
                                            <?php if (isset($candidate['distance_km']) && $candidate['distance_km'] !== null): ?>
                                                <p class="mb-0 small text-primary"><i class="bi bi-geo-alt-fill"></i> <?= number_format($candidate['distance_km'], 1) ?> km away</p>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <a href="index.php?action=candidate&search=<?= urlencode($candidate['candidate_name']) ?>" class="btn btn-sm btn-outline-primary">View</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php
require BASE_PATH . '/app/views/partials/app_layout_end.php';
