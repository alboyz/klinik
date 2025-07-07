<?php
require_once '../../config/config.php';
requireLogin();
requirePermission('admin');

$current_page = 'manage_admin';
$action = $_GET['action'] ?? 'dashboard';
$module = $_GET['module'] ?? 'users';

// Handle form submissions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $db = getDB();

    try {
        switch ($action) {
            case 'add_user':
                $username = sanitizeInput($_POST['username']);
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $full_name = sanitizeInput($_POST['full_name']);
                $email = sanitizeInput($_POST['email']);
                $role = sanitizeInput($_POST['role']);

                $stmt = $db->prepare("INSERT INTO users (username, password, full_name, email, role) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$username, $password, $full_name, $email, $role]);

                // Tambahkan kode berikut agar otomatis insert ke doctors jika role doctor
                if ($role === 'doctor') {
                    $user_id = $db->lastInsertId();
                    $stmt = $db->prepare("INSERT INTO doctors (user_id, doctor_name, email, created_at) VALUES (?, ?, ?, NOW())");
                    $stmt->execute([$user_id, $full_name, $email]);
                }

                $message = "User added successfully!";
                break;

            case 'edit_user':
                $user_id = $_POST['user_id'];
                $username = sanitizeInput($_POST['username']);
                $full_name = sanitizeInput($_POST['full_name']);
                $email = sanitizeInput($_POST['email']);
                $role = sanitizeInput($_POST['role']);

                if (!empty($_POST['password'])) {
                    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $stmt = $db->prepare("UPDATE users SET username=?, password=?, full_name=?, email=?, role=? WHERE user_id=?");
                    $stmt->execute([$username, $password, $full_name, $email, $role, $user_id]);
                } else {
                    $stmt = $db->prepare("UPDATE users SET username=?, full_name=?, email=?, role=? WHERE user_id=?");
                    $stmt->execute([$username, $full_name, $email, $role, $user_id]);
                }
                $message = "User updated successfully!";
                break;

            case 'delete_user':
                $user_id = $_POST['user_id'];
                $stmt = $db->prepare("DELETE FROM users WHERE user_id=? AND user_id != ?");

                // Prevent deleting the current user
                if ($user_id != $_SESSION['user_id']) {
                    $stmt->execute([$user_id, $_SESSION['user_id']]);
                } else {
                    $error = "Cannot delete your own account";
                }

                // Check if the user is a doctor and delete the doctor record if it exists
                $stmt = $db->prepare("SELECT * FROM doctors WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $doctorRecord = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($doctorRecord) {
                    $db->query("DELETE FROM doctors WHERE user_id = $user_id");
                }

                $message = "User deleted successfully!";
                break;
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
        logError('Admin management error: ' . $e->getMessage());
    }
}

// Get data based on current module
$db = getDB();
$data = [];

// Add doctor user mapping
$doctorMap = [];
if ($module == 'users') {
    try {
        $stmt = $db->query("SELECT user_id, doctor_id FROM doctors");
        $doctorMappings = $stmt->fetchAll();
        foreach ($doctorMappings as $dr) {
            $doctorMap[$dr['user_id']] = $dr['doctor_id'];
        }
    } catch (PDOException $e) {
        logError('Doctor mapping error: ' . $e->getMessage());
    }
}

try {
    switch ($module) {
        case 'users':
            $stmt = $db->query("SELECT * FROM users ORDER BY created_at DESC");
            $data = $stmt->fetchAll();
            break;
        case 'doctors':
            $stmt = $db->query("SELECT d.*, u.username FROM doctors d LEFT JOIN users u ON d.user_id = u.user_id ORDER BY d.created_at DESC");
            $data = $stmt->fetchAll();
            break;
        case 'animals':
            $stmt = $db->query("SELECT a.*, ao.owner_name FROM animals a JOIN animal_owners ao ON a.owner_id = ao.owner_id ORDER BY a.created_at DESC");
            $data = $stmt->fetchAll();
            break;
        case 'owners':
            $stmt = $db->query("SELECT * FROM animal_owners ORDER BY created_at DESC");
            $data = $stmt->fetchAll();
            break;
    }
} catch (PDOException $e) {
    $error = "Error loading data: " . $e->getMessage();
    logError('Admin data loading error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Admin - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body>
    <div class="app-container">
        <!-- Side Navigation -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <h3>Pet Clinic</h3>
                <p>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
            </div>

            <ul class="sidebar-menu">
                <li>
                    <a href="../../dashboard.php">
                        <span class="icon">📊</span>
                        Dashboard
                    </a>
                </li>

                <li class="active">
                    <a href="manage_admin.php">
                        <span class="icon">👥</span>
                        Manage Admin
                    </a>
                </li>

                <li>
                    <a href="../doctor/doctor.php">
                        <span class="icon">👨‍⚕️</span>
                        Doctor
                    </a>
                </li>

                <li>
                    <a href="../animal/animal.php">
                        <span class="icon">🐕</span>
                        Animal
                    </a>
                </li>

                <li>
                    <a href="../owner/animal_owner.php">
                        <span class="icon">👤</span>
                        Animal Owner
                    </a>
                </li>

                <li>
                    <a href="../examination/examination.php">
                        <span class="icon">🔍</span>
                        Examination
                    </a>
                </li>

                <li>
                    <a href="../medicine/medicine.php">
                        <span class="icon">💊</span>
                        Medicine
                    </a>
                </li>

                <li>
                    <a href="../payment/payment.php">
                        <span class="icon">💰</span>
                        Payment
                    </a>
                </li>

                <li class="logout">
                    <a href="../../logout.php">
                        <span class="icon">🚪</span>
                        Logout
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <div class="content-header">
                <h1>Manage Admin</h1>
                <p class="current-date">Today: <?php echo date('F j, Y'); ?></p>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <!-- Module Navigation -->
            <div class="module-nav">
                <a href="?module=users" class="btn <?php echo $module == 'users' ? 'btn-primary' : 'btn-secondary'; ?>">Users</a>
                <a href="?module=doctors" class="btn <?php echo $module == 'doctors' ? 'btn-primary' : 'btn-secondary'; ?>">Doctors</a>
                <a href="?module=animals" class="btn <?php echo $module == 'animals' ? 'btn-primary' : 'btn-secondary'; ?>">Animals</a>
                <a href="?module=owners" class="btn <?php echo $module == 'owners' ? 'btn-primary' : 'btn-secondary'; ?>">Owners</a>
            </div>

            <!-- Content based on module -->
            <div class="admin-content">
                <?php if ($module == 'users'): ?>
                    <div class="section-header">
                        <h2>User Management</h2>
                        <button onclick="showAddUserForm()" class="btn btn-success">Add New User</button>
                    </div>

                    <!-- Add User Form (Hidden by default) -->
                    <div id="addUserForm" class="form-container" style="display: none;">
                        <h3>Add New User</h3>
                        <form method="POST" action="?action=add_user&module=users">
                            <div class="form-row">
                                <div class="form-col">
                                    <label>Username:</label>
                                    <input type="text" name="username" required>
                                </div>
                                <div class="form-col">
                                    <label>Password:</label>
                                    <input type="password" name="password" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-col">
                                    <label>Full Name:</label>
                                    <input type="text" name="full_name" required>
                                </div>
                                <div class="form-col">
                                    <label>Email:</label>
                                    <input type="email" name="email">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-col">
                                    <label>Role:</label>
                                    <select name="role" required>
                                        <option value="staff">Staff</option>
                                        <option value="doctor">Doctor</option>
                                        <option value="admin">Admin</option>
                                    </select>
                                </div>
                            </div>
                            <div class="action-buttons">
                                <button type="submit" class="btn btn-success">Add User</button>
                                <button type="button" onclick="hideAddUserForm()" class="btn btn-secondary">Cancel</button>
                            </div>
                        </form>
                    </div>

                    <!-- Edit User Form -->
                    <div class="form-container" id="editUserForm" style="display: none;">
                        <h3>Edit User</h3>
                        <form method="POST" action="?action=edit_user&module=users">
                            <input type="hidden" name="user_id" id="edit_user_id">
                            <div class="form-row">
                                <div class="form-col">
                                    <label>Username:</label>
                                    <input type="text" name="username" id="edit_username" required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-col">
                                    <label>Password</label>
                                    <input type="password" name="password" id="edit_password">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-col">
                                    <label>Full Name</label>
                                    <input type="text" name="full_name" id="edit_full_name" required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-col">
                                    <label>Email</label>
                                    <input type="email" name="email" id="edit_email">
                                </div>
                            </div>


                            <div class="form-row">
                                <div class="form-col">
                                    <label>Role</label>
                                    <select name="role" id="edit_role" required>
                                        <option value="staff">Staff</option>
                                        <option value="doctor">Doctor</option>
                                        <option value="admin">Admin</option>
                                    </select>
                                </div>
                            </div>


                            <div class="action-buttons">
                                <button type="submit" class="btn btn-success">Update User</button>
                                <button type="button" onclick="cancelEditUser()" class="btn btn-secondary">Cancel</button>
                            </div>
                        </form>
                    </div>


                    <!-- Users Table -->
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Full Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data as $user): ?>
                                    <tr>
                                        <td><?php echo $user['user_id']; ?></td>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <span class="status status-<?php echo $user['role']; ?>">
                                                <?php echo ucfirst($user['role']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo formatDate($user['created_at']); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button onclick="showEditForm('<?php echo $user['user_id']; ?>', '<?php echo $user['username']; ?>', '<?php echo $user['full_name']; ?>', '<?php echo $user['email']; ?>', '<?php echo $user['role']; ?>')" class="btn btn-warning">Edit</button>
                                                <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                                    <button onclick="deleteUser(<?php echo $user['user_id']; ?>)" class="btn btn-danger">Delete</button>
                                                <?php endif; ?>
                                            </div>

                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>


                <?php elseif ($module == 'doctors'): ?>
                    <div class="section-header">
                        <h2>Doctor Management</h2>
                        <a href="../doctor/doctor.php?action=add" class="btn btn-success">Add New Doctor</a>
                    </div>

                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Doctor Name</th>
                                    <th>Specialization</th>
                                    <th>License</th>
                                    <th>Phone</th>
                                    <th>Email</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data as $doctor): ?>
                                    <tr>
                                        <td><?php echo $doctor['doctor_id']; ?></td>
                                        <td><?php echo htmlspecialchars($doctor['doctor_name']); ?></td>
                                        <td><?php echo htmlspecialchars($doctor['specialization']); ?></td>
                                        <td><?php echo htmlspecialchars($doctor['license_number']); ?></td>
                                        <td><?php echo htmlspecialchars($doctor['phone']); ?></td>
                                        <td><?php echo htmlspecialchars($doctor['email']); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="../doctor/doctor.php?action=edit&id=<?php echo $doctor['doctor_id']; ?>" class="btn btn-warning">Edit</a>
                                                <a href="../doctor/doctor.php?action=delete&id=<?php echo $doctor['doctor_id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>




                    <?php elseif ($module == 'animals'): ?>
                        <div class="section-header">
                            <h2>Animal Management</h2>
                            <a href="../animal/animal.php?action=add" class="btn btn-success">Add New Animal</a>
                        </div>

                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Code</th>
                                        <th>Name</th>
                                        <th>Species</th>
                                        <th>Race</th>
                                        <th>Owner</th>
                                        <th>Age</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($data as $animal): ?>
                                        <tr>
                                            <td><?php echo $animal['animal_id']; ?></td>
                                            <td><?php echo htmlspecialchars($animal['animal_code']); ?></td>
                                            <td><?php echo htmlspecialchars($animal['animal_name']); ?></td>
                                            <td><?php echo ucfirst($animal['species']); ?></td>
                                            <td><?php echo htmlspecialchars($animal['race']); ?></td>
                                            <td><?php echo htmlspecialchars($animal['owner_name']); ?></td>
                                            <td><?php echo $animal['age']; ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="../animal/animal.php?action=edit&id=<?php echo $animal['animal_id']; ?>" class="btn btn-warning">Edit</a>
                                                    <a href="../animal/animal.php?action=delete&id=<?php echo $animal['animal_id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                    <?php elseif ($module == 'owners'): ?>
                        <div class="section-header">
                            <h2>Animal Owner Management</h2>
                            <a href="../owner/animal_owner.php?action=add" class="btn btn-success">Add New Owner</a>
                        </div>

                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Code</th>
                                        <th>Name</th>
                                        <th>Phone</th>
                                        <th>Email</th>
                                        <th>Address</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($data as $owner): ?>
                                        <tr>
                                            <td><?php echo $owner['owner_id']; ?></td>
                                            <td><?php echo htmlspecialchars($owner['owner_code']); ?></td>
                                            <td><?php echo htmlspecialchars($owner['owner_name']); ?></td>
                                            <td><?php echo htmlspecialchars($owner['telephone_number']); ?></td>
                                            <td><?php echo htmlspecialchars($owner['email']); ?></td>
                                            <td><?php echo htmlspecialchars(substr($owner['address'], 0, 50)) . '...'; ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="../owner/animal_owner.php?action=edit&id=<?php echo $owner['owner_id']; ?>" class="btn btn-warning">Edit</a>
                                                    <a href="../owner/animal_owner.php?action=delete&id=<?php echo $owner['owner_id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                    </div>
        </main>
    </div>

    <script>
        function showAddUserForm() {
            document.getElementById('addUserForm').style.display = 'block';
        }

        function hideAddUserForm() {
            document.getElementById('addUserForm').style.display = 'none';
        }

        function editUser(userId) {
            // Implementation for edit user modal/form
            alert('Edit user functionality - ID: ' + userId);
        }

        function deleteUser(userId) {
            if (confirm('Are you sure you want to delete this user?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '?action=delete_user&module=users';

                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'user_id';
                input.value = userId;

                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        }

        function showEditForm(userId, username, fullName, email, role) {
            document.getElementById('edit_user_id').value = userId;
            document.getElementById('edit_username').value = username;
            document.getElementById('edit_full_name').value = fullName;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_role').value = role;

            document.getElementById('editUserForm').style.display = 'block';
            document.getElementById('editUserForm').scrollIntoView({
                behavior: 'smooth'
            });
        }

        function cancelEditUser() {
            document.getElementById('editUserForm').style.display = 'none';
        }
    </script>

    <style>
        .module-nav {
            margin-bottom: 30px;
            display: flex;
            gap: 10px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .status-admin {
            background: #e1f5fe;
            color: #01579b;
        }

        .status-doctor {
            background: #f3e5f5;
            color: #4a148c;
        }

        .status-staff {
            background: #e8f5e8;
            color: #2e7d32;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.4);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 20px;
            width: 50%;
            border-radius: 5px;
            box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2);
            overflow-y: auto;
            max-height: 80vh;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
</body>

</html>