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
    <div class="row">
        <!-- Add User Form Button, Plus the table with the FF. columns:
         ID | Username | Password | Action (Edit/Delete) -->
    </div> 
</body>
</html> 

<script src="https://cdn.jsdelivr.net/npm/bootstrap@6.0.0-alpha.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-ygbV9kiqUc6oa4msXn9868pTtWMgiQaeYH7/t7LECLbyPA2x65Kgf80OJFdroafW" crossorigin="anonymous"></script>
<script>
        // Edit and Delete hmmm di pa sure kung tama to, pero try natin
        tbody.addEventListener('click', function (e) {
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
