<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="editUserModalLabel">Edit User</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= escapeHtml($csrfToken) ?>">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="edit-user-id">
                    <div class="mb-3">
                        <label for="edit-username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="edit-username" name="username" maxlength="50" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit-password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="edit-password" name="password" autocomplete="new-password">
                        <div class="form-text">Leave blank to keep the current password.</div>
                    </div>
                    <div>
                        <label for="edit-role" class="form-label">Role</label>
                        <select class="form-select" id="edit-role" name="role" required>
                            <option value="admin">Admin</option>
                            <option value="manager">Manager</option>
                            <option value="cashier">Cashier</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
