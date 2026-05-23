<?php
/** @var array $user */
/** @var array|null $flash */
/** @var array $groups */
/** @var array $settings */

$pageTitle = 'Employa HR - Settings';
$headerTitle = 'System Settings';
$currentModule = 'settings';
$headerActions = '';

require BASE_PATH . '/app/views/partials/app_layout_start.php';
?>

<div class="module-heading mb-3">
    <div>
        <h1 class="greeting mb-1">Dropdown Settings</h1>
        <p class="text-secondary mb-0">Configure the dropdown options available across different forms in the system.</p>
    </div>
</div>

<div class="card card-soft">
    <div class="card-body">
        <form method="post" action="index.php?action=save_settings">
            <?= csrf_field() ?>
            
            <div class="row g-4">
                <?php foreach ($groups as $groupKey => $groupLabel): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="form-group">
                            <label class="form-label fw-semibold text-dark mb-1"><?= esc($groupLabel) ?></label>
                            <p class="text-secondary small mb-2">Separate options with commas.</p>
                            <textarea class="form-control" name="settings[<?= esc($groupKey) ?>]" rows="3"><?= esc(implode(', ', $settings[$groupKey])) ?></textarea>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="mt-4 pt-3 border-top">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save Settings</button>
            </div>
        </form>
    </div>
</div>

<?php
require BASE_PATH . '/app/views/partials/app_layout_end.php';
