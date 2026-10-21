<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tansioco | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@6.0.0-alpha.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-B/GM4XqrwHnWXNOWMbloTmrYXZg10cakYGmpfsR/bbzQ6JAJI4ihuyADKLnBgrCe" crossorigin="anonymous">
</head>
<body>
    <div class="row">
        <div class="col-md-4"></div>
        <div class="col-md-4">
            <h1>Manage Users</h1>
        </div>
        <div class="col-md-4"></div>
    </div>
    <div class="row container">
        <!-- Add User Form Button, Plus thetbale witht he FF. columns:
         ID | Username | Password | Action (Edit/Delete) -->
         <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Password</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody id="userTableBody">
                    <tr data-id="1">
                        <td>1</td>
                        <td class="col-username">admin</td>
                        <td>********</td>
                        <td>
                            <button type="button" class="btn btn-success btn-sm btn-edit" data-bs-toggle="modal" data-bs-target="#userModal">Edit</button>
                            <button type="button" class="btn btn-danger btn-sm btn-delete">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
 
    <!-- Add / Edit User Modal -->
    <div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="user_id">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" placeholder="Enter Username">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" placeholder="Enter Password">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="btnSave">Save</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@6.0.0-alpha.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-ygbV9kiqUc6oa4msXn9868pTtWMgiQaeYH7/t7LECLbyPA2x65Kgf80OJFdroafW" crossorigin="anonymous"></script>
    <script>
        // Edit and Delete hmmm di pa sure kung tama to, pero try natin
        document.getElementById('userTableBody').addEventListener('click', function (e) {
            const row = e.target.closest('tr');

            if (e.target.classList.contains('btn-edit')) {
                document.getElementById('user_id').value = row.getAttribute('data-id');
                document.getElementById('username').value = row.querySelector('.col-username').textContent;
                document.getElementById('password').value = '';
                document.getElementById('modalTitle').textContent = 'Edit User';
            }

            if (e.target.classList.contains('btn-delete')) {
                if (confirm('Are you sure you want to delete this user?')) {
                    row.remove();
                    showAlert('danger', 'User deleted successfully!');
                }
            }
        });
</script>
</body>
</html>
