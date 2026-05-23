<?php
$pageTitle = 'Admin Module - Employa HR';
$headerTitle = 'Admin Module';
$currentModule = 'admin';

ob_start();
?>
<div class="d-flex gap-2">
    <!-- Header Actions if any -->
</div>
<?php
$headerActions = ob_get_clean();

require BASE_PATH . '/app/views/partials/app_layout_start.php';
?>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-semibold text-dark">System Users</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 custom-table">
                <thead class="table-light text-secondary">
                    <tr>
                        <th class="px-4 py-3 fw-medium">ID</th>
                        <th class="py-3 fw-medium">Name</th>
                        <th class="py-3 fw-medium">Email</th>
                        <th class="py-3 fw-medium">Company (Tenant)</th>
                        <th class="py-3 fw-medium">Role</th>
                        <th class="px-4 py-3 fw-medium text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <div class="mb-2"><i class="bi bi-people fs-1"></i></div>
                                <div>No users found in the system.</div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td class="px-4 text-muted fw-medium">#<?= esc((string) $u['id']) ?></td>
                                <td>
                                    <div class="fw-medium text-dark"><?= esc((string) $u['name']) ?></div>
                                </td>
                                <td><?= esc((string) $u['email']) ?></td>
                                <td><span class="badge bg-light text-dark border"><?= esc((string) $u['tenant_name']) ?></span></td>
                                <td>
                                    <?php if ($u['role'] === 'admin'): ?>
                                        <span class="badge bg-primary text-white border">Admin</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary text-white border">User</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 text-end">
                                    <button class="btn btn-light btn-sm text-primary border" 
                                            onclick='openEditModal(<?= json_encode($u, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)' 
                                            title="Edit User">
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
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="index.php?action=admin_user_update" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="user_id" id="edit_user_id">
                
                <div class="modal-header bg-light border-bottom-0">
                    <h5 class="modal-title fw-semibold text-dark" id="editUserModalLabel">Edit User & Permissions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-medium text-secondary small">Full Name</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-medium text-secondary small">Email Address</label>
                        <input type="email" name="email" id="edit_email" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-medium text-secondary small">Company Name (Tenant ID)</label>
                        <input type="text" name="tenant_name" id="edit_tenant_name" class="form-control" required>
                        <div class="form-text text-muted small">This is the multi-tenant isolation key. All data is scoped to this company name.</div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-medium text-secondary small">Account Role</label>
                            <select name="role" id="edit_role" class="form-select" onchange="togglePermissions(this.value)">
                                <option value="user">Standard User</option>
                                <option value="admin">Administrator (Full Access)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium text-secondary small">Current Password</label>
                            <input type="text" id="current_password_display" class="form-control bg-light" readonly value="Unknown (Encrypted)">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium text-secondary small">New Password</label>
                            <input type="text" name="password" class="form-control" placeholder="Leave blank to keep">
                        </div>
                    </div>
                    
                    <div class="mb-3" id="permissions_block">
                        <label class="form-label fw-medium text-secondary small">Module Permissions</label>
                        <div class="d-flex flex-wrap gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="permissions[]" value="candidate" id="perm_candidate">
                                <label class="form-check-label" for="perm_candidate">Candidates</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="permissions[]" value="clients" id="perm_client">
                                <label class="form-check-label" for="perm_client">Clients</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="permissions[]" value="interviews" id="perm_interview">
                                <label class="form-check-label" for="perm_interview">Interviews</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="permissions[]" value="ai_matcher" id="perm_ai">
                                <label class="form-check-label" for="perm_ai">AI Matcher</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="permissions[]" value="settings" id="perm_settings">
                                <label class="form-check-label" for="perm_settings">Settings</label>
                            </div>
                        </div>
                        <div class="form-text text-muted small mt-2">Select which modules this user can access. Admin role ignores these and gets full access.</div>
                    </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top-0 d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-danger" onclick="confirmDelete()">Delete User</button>
                    <div>
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4 shadow-sm">Save Changes</button>
                    </div>
                </div>
            </form>
            
            <form action="index.php?action=admin_user_delete" method="POST" id="deleteUserForm" class="d-none">
                <?= csrf_field() ?>
                <input type="hidden" name="user_id" id="delete_user_id">
            </form>
        </div>
    </div>
</div>

<?php
$extraScripts = [<<<'HTML'
<script>
    function openEditModal(user) {
        document.getElementById('edit_user_id').value = user.id;
        document.getElementById('delete_user_id').value = user.id;
        document.getElementById('edit_name').value = user.name;
        document.getElementById('edit_email').value = user.email;
        document.getElementById('edit_tenant_name').value = user.tenant_name;
        document.getElementById('edit_role').value = user.role;
        document.getElementById('current_password_display').value = user.visible_password || 'Unknown (Encrypted)';
        document.querySelector('input[name="password"]').value = '';
        
        // Reset checkboxes
        document.querySelectorAll('input[name="permissions[]"]').forEach(cb => cb.checked = false);
        
        try {
            const perms = JSON.parse(user.permissions || '[]');
            perms.forEach(p => {
                const cb = document.querySelector(`input[name="permissions[]"][value="${p}"]`);
                if (cb) cb.checked = true;
            });
        } catch (e) {}

        togglePermissions(user.role);
        
        const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
        modal.show();
    }
    
    function togglePermissions(role) {
        const block = document.getElementById('permissions_block');
        if (role === 'admin') {
            block.style.display = 'none';
        } else {
            block.style.display = 'block';
        }
    }
    
    function confirmDelete() {
        if(confirm('Are you sure you want to permanently delete this user? This action cannot be undone.')) {
            document.getElementById('deleteUserForm').submit();
        }
    }
</script>
HTML
];

require BASE_PATH . '/app/views/partials/app_layout_end.php';
?>
