<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="addUserModalLabel">Add New User</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= escapeHtml($csrfToken) ?>">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3">
                        <label for="new-username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="new-username" name="username" maxlength="50" required>
                    </div>
                    <div class="mb-3">
                        <label for="new-password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="new-password" name="password" required>
                    </div>
                    <div>
                        <label for="new-role" class="form-label">Role</label>
                        <select class="form-select" id="new-role" name="role" required>
                            <option value="admin">Admin</option>
                            <option value="manager">Manager</option>
                            <option value="cashier">Cashier</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Create User</button>
                </div>
            </form>
        </div>
    </div>
</div>
