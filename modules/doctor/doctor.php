<?php
require_once '../../config/config.php';
requireLogin();

$current_page = 'doctor';
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

// Handle form submissions
$message = '';
$error = '';

$db = getDB();

// Always build medicines options for the form (GET and POST)
$medicines_options = '';
$stmt = $db->query("SELECT medicine_id, medicine_name, medicine_code FROM medicines");
while ($row = $stmt->fetch()) {
    $medicines_options .= "<option value='{$row['medicine_id']}'>" . htmlspecialchars($row['medicine_name']) . " (" . htmlspecialchars($row['medicine_code']) . ")</option>";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $db = getDB();

    function decreaseMedicineStock($db, $medicine_id, $quantity)
    {
        $stmt = $db->prepare("UPDATE medicines SET stock_quantity = stock_quantity - ? WHERE medicine_id = ?");
        $stmt->execute([$quantity, $medicine_id]);

        // Check if stock goes below 0 (if needed)
        $stmt = $db->prepare("SELECT stock_quantity FROM medicines WHERE medicine_id = ?");
        $stmt->execute([$medicine_id]);
        $stock = $stmt->fetchColumn();

        if ($stock < 0) {
            // Handle negative stock (prevent it or send notification)
            throw new PDOException("Not enough stock for medicine");
        }
    }

    try {
        switch ($action) {
            case 'add_examination':
                $animal_id = $_POST['animal_id'];
                $doctor_id = $_POST['doctor_id'];
                $examination_date = $_POST['examination_date'];
                $important_disease_history = sanitizeInput($_POST['important_disease_history']);
                $allergies = sanitizeInput($_POST['allergies']);
                $diagnosis = sanitizeInput($_POST['diagnosis']);
                $routine_drugs = sanitizeInput($_POST['routine_drugs']);
                $action_taken = sanitizeInput($_POST['action_taken']);
                $notes = sanitizeInput($_POST['notes']);




                $stmt = $db->prepare("
                    INSERT INTO examinations (animal_id, doctor_id, examination_date, important_disease_history, 
                    allergies, diagnosis, routine_drugs, action_taken, notes, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'completed')
                ");
                $stmt->execute([
                    $animal_id,
                    $doctor_id,
                    $examination_date,
                    $important_disease_history,
                    $allergies,
                    $diagnosis,
                    $routine_drugs,
                    $action_taken,
                    $notes
                ]);

                $examination_id = $db->lastInsertId();

                // Create medical record
                $stmt = $db->prepare("
                    INSERT INTO medical_records (examination_id, animal_id, record_date, important_disease_history, 
                    allergies, diagnosis, routine_drugs, action_taken, doctor_notes) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $examination_id,
                    $animal_id,
                    $examination_date,
                    $important_disease_history,
                    $allergies,
                    $diagnosis,
                    $routine_drugs,
                    $action_taken,
                    $notes
                ]);

                // Create prescription medicines
                $stmt = $db->prepare("
                INSERT INTO prescription_medicines (examination_id, medicine_id, quantity, dosage_instructions, duration_days)
                VALUES (?, ?, ?, ?, ?)
            ");

                foreach ($_POST['medicine_id'] as $index => $medicine_id) {
                    $quantity = $_POST['quantity'][$index];
                    $dosage_instructions = $_POST['dosage_instructions'][$index] ?? '';
                    $duration_days = $_POST['duration_days'][$index];

                    // Decrease stock when medicine is prescribed/used in examination
                    decreaseMedicineStock($db, $medicine_id, $quantity);


                    $stmt->execute([
                        $examination_id,
                        $medicine_id,
                        $quantity,
                        $dosage_instructions,
                        $duration_days
                    ]);
                }


                $message = "Examination and medical record added successfully!";
                break;

            case 'add_doctor':
                $doctor_name = sanitizeInput($_POST['doctor_name']);
                $specialization = sanitizeInput($_POST['specialization']);
                $license_number = sanitizeInput($_POST['license_number']);
                $phone = sanitizeInput($_POST['phone']);
                $email = sanitizeInput($_POST['email']);
                $address = sanitizeInput($_POST['address']);

                $stmt = $db->prepare("
                    INSERT INTO doctors (doctor_name, specialization, license_number, phone, email, address) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$doctor_name, $specialization, $license_number, $phone, $email, $address]);
                $message = "Doctor added successfully!";
                break;

            case 'edit_doctor':
                $doctor_id = $_POST['doctor_id'];
                $doctor_name = sanitizeInput($_POST['doctor_name']);
                $specialization = sanitizeInput($_POST['specialization']);
                $license_number = sanitizeInput($_POST['license_number']);
                $phone = sanitizeInput($_POST['phone']);
                $email = sanitizeInput($_POST['email']);
                $address = sanitizeInput($_POST['address']);

                $stmt = $db->prepare("
                    UPDATE doctors SET doctor_name=?, specialization=?, license_number=?, phone=?, email=?, address=? 
                    WHERE doctor_id=?
                ");
                $stmt->execute([$doctor_name, $specialization, $license_number, $phone, $email, $address, $doctor_id]);
                $message = "Doctor updated successfully!";
                break;

            case 'delete':
                $doctor_id = $_POST['doctor_id'];
                $stmt = $db->prepare("DELETE FROM doctors WHERE doctor_id = ?");
                $stmt->execute([$doctor_id]);
                $message = "Doctor deleted successfully!";
                // Redirect to refresh the page after deletion
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit();
                break;
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
        logError('Doctor module error: ' . $e->getMessage());
    }
}

// Get data based on action
$db = getDB();
$examinations = [];
$doctors = [];
$animals = [];
$medical_records = [];
$current_doctor = null;

try {
    // Get all doctors
    $stmt = $db->query("SELECT * FROM doctors ORDER BY doctor_name");
    $doctors = $stmt->fetchAll();

    // Get all animals with owners
    $stmt = $db->query("
        SELECT a.*, ao.owner_name 
        FROM animals a 
        JOIN animal_owners ao ON a.owner_id = ao.owner_id 
        ORDER BY a.animal_name
    ");
    $animals = $stmt->fetchAll();

    // Get examinations with related data
    $stmt = $db->query("
        SELECT e.*, a.animal_name, a.animal_code, ao.owner_name, d.doctor_name
        FROM examinations e
        JOIN animals a ON e.animal_id = a.animal_id
        JOIN animal_owners ao ON a.owner_id = ao.owner_id
        JOIN doctors d ON e.doctor_id = d.doctor_id
        ORDER BY e.examination_date DESC
    ");
    $examinations = $stmt->fetchAll();

    // Get medical records
    $stmt = $db->query("
        SELECT mr.*, a.animal_name, a.animal_code, ao.owner_name
        FROM medical_records mr
        JOIN animals a ON mr.animal_id = a.animal_id
        JOIN animal_owners ao ON a.owner_id = ao.owner_id
        ORDER BY mr.record_date DESC
    ");
    $medical_records = $stmt->fetchAll();

    // Get specific doctor if editing
    if ($action == 'edit' && $id) {
        $stmt = $db->prepare("SELECT * FROM doctors WHERE doctor_id = ?");
        $stmt->execute([$id]);
        $current_doctor = $stmt->fetch();
    }
} catch (PDOException $e) {
    $error = "Error loading data: " . $e->getMessage();
    logError('Doctor data loading error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor - <?php echo APP_NAME; ?></title>
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

                <?php if (hasPermission('admin')): ?>
                    <li>
                        <a href="../admin/manage_admin.php">
                            <span class="icon">👥</span>
                            Manage Admin
                        </a>
                    </li>
                <?php endif; ?>

                <li class="active">
                    <a href="doctor.php">
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
                <h1>Doctor Management</h1>
                <p class="current-date">Today: <?php echo date('F j, Y'); ?></p>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <!-- Action Navigation -->
            <div class="module-nav">
                <a href="?action=list" class="btn <?php echo $action == 'list' ? 'btn-primary' : 'btn-secondary'; ?>">Examinations</a>
                <a href="?action=add_examination" class="btn <?php echo $action == 'add_examination' ? 'btn-primary' : 'btn-secondary'; ?>">New Examination</a>
                <a href="?action=medical_records" class="btn <?php echo $action == 'medical_records' ? 'btn-primary' : 'btn-secondary'; ?>">Medical Records</a>
                <a href="?action=manage_doctors" class="btn <?php echo $action == 'manage_doctors' ? 'btn-primary' : 'btn-secondary'; ?>">Manage Doctors</a>
            </div>

            <!-- Content based on action -->
            <div class="doctor-content">
                <?php if ($action == 'list'): ?>
                    <div class="section-header">
                        <h2>Examinations</h2>
                        <a href="?action=add_examination" class="btn btn-success">New Examination</a>
                    </div>

                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Exam ID</th>
                                    <th>Animal Name</th>
                                    <th>Owner</th>
                                    <th>Doctor</th>
                                    <th>Date</th>
                                    <th>Diagnosis</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($examinations as $exam): ?>
                                    <tr>
                                        <td><?php echo $exam['examination_id']; ?></td>
                                        <td><?php echo htmlspecialchars($exam['animal_name']); ?></td>
                                        <td><?php echo htmlspecialchars($exam['owner_name']); ?></td>
                                        <td><?php echo htmlspecialchars($exam['doctor_name']); ?></td>
                                        <td><?php echo formatDate($exam['examination_date']); ?></td>
                                        <td><?php echo htmlspecialchars(substr($exam['diagnosis'], 0, 50)) . '...'; ?></td>
                                        <td>
                                            <span class="status status-<?php echo $exam['status']; ?>">
                                                <?php echo ucfirst($exam['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button onclick="viewExamination(<?php echo $exam['examination_id']; ?>)" class="btn btn-primary">View</button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                <?php elseif ($action == 'add_examination'): ?>
                    <div class="form-container">
                        <h2>New Examination</h2>
                        <form method="POST" action="?action=add_examination">
                            <div class="form-row">
                                <div class="form-col">
                                    <label>Animal:</label>
                                    <select name="animal_id" required>
                                        <option value="">Select Animal</option>
                                        <?php foreach ($animals as $animal): ?>
                                            <option value="<?php echo $animal['animal_id']; ?>">
                                                <?php echo htmlspecialchars($animal['animal_name'] . ' (' . $animal['animal_code'] . ') - ' . $animal['owner_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-col">
                                    <label>Doctor:</label>
                                    <select name="doctor_id" required>
                                        <option value="">Select Doctor</option>
                                        <?php foreach ($doctors as $doctor): ?>
                                            <option value="<?php echo $doctor['doctor_id']; ?>">
                                                <?php echo htmlspecialchars($doctor['doctor_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-col">
                                    <label>Examination Date:</label>
                                    <input type="date" name="examination_date" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-col">
                                    <label>Important Disease History:</label>
                                    <textarea name="important_disease_history" rows="3"></textarea>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-col">
                                    <label>Allergies:</label>
                                    <textarea name="allergies" rows="3"></textarea>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-col">
                                    <label>Diagnosis:</label>
                                    <textarea name="diagnosis" rows="4" required></textarea>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-col">
                                    <label>Routine Drugs:</label>
                                    <textarea name="routine_drugs" rows="3"></textarea>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-col">
                                    <label>Action Taken:</label>
                                    <textarea name="action_taken" rows="4" required></textarea>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-col">
                                    <label>Prescribed Medicines:</label>
                                    <div class="medicine-prescriptions">
                                        <!-- First medicine -->
                                        <div class="medicine-row">
                                            <div class="form-group">
                                                <label>Medicine:</label>
                                                <select name="medicine_id[]" required>
                                                    <option value="">-- Select Medicine --</option>
                                                    <?php echo $medicines_options; ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label>Quantity:</label>
                                                <input type="number" name="quantity[]" min="1" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Dosage Instructions:</label>
                                                <input type="text" name="dosage_instructions[]" placeholder="e.g., 1 tab daily">
                                            </div>
                                            <div class="form-group">
                                                <label>Duration (days):</label>
                                                <input type="number" name="duration_days[]" min="1">
                                            </div>
                                        </div>

                                        <!-- Button to add another medicine -->
                                        <div style="margin-top: 10px;">
                                            <button type="button" id="add-medicine" class="btn btn-secondary" style="margin-bottom: 15px;">+ Add Another Medicine</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-col">
                                    <label>Additional Notes:</label>
                                    <textarea name="notes" rows="3"></textarea>
                                </div>
                            </div>

                            <div class="action-buttons">
                                <button type="submit" class="btn btn-success">Save Examination</button>
                                <a href="?action=list" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>

                <?php elseif ($action == 'medical_records'): ?>
                    <div class="section-header">
                        <h2>Medical Records</h2>
                    </div>

                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Record ID</th>
                                    <th>Animal Name</th>
                                    <th>Owner</th>
                                    <th>Date</th>
                                    <th>Disease History</th>
                                    <th>Allergies</th>
                                    <th>Diagnosis</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($medical_records as $record): ?>
                                    <tr>
                                        <td><?php echo $record['record_id']; ?></td>
                                        <td><?php echo htmlspecialchars($record['animal_name']); ?></td>
                                        <td><?php echo htmlspecialchars($record['owner_name']); ?></td>
                                        <td><?php echo formatDate($record['record_date']); ?></td>
                                        <td><?php echo htmlspecialchars(substr($record['important_disease_history'], 0, 30)) . '...'; ?></td>
                                        <td><?php echo htmlspecialchars(substr($record['allergies'], 0, 30)) . '...'; ?></td>
                                        <td><?php echo htmlspecialchars(substr($record['diagnosis'], 0, 40)) . '...'; ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button onclick="viewRecord(<?php echo $record['record_id']; ?>)" class="btn btn-primary">View</button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                <?php elseif ($action == 'manage_doctors'): ?>
                    <div class="section-header">
                        <h2>Manage Doctors</h2>
                        <a href="?action=add" class="btn btn-success">Add New Doctor</a>
                    </div>

                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Specialization</th>
                                    <th>License</th>
                                    <th>Phone</th>
                                    <th>Email</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($doctors as $doctor): ?>
                                    <tr>
                                        <td><?php echo $doctor['doctor_id']; ?></td>
                                        <td><?php echo htmlspecialchars($doctor['doctor_name']); ?></td>
                                        <td><?php echo htmlspecialchars($doctor['specialization']); ?></td>
                                        <td><?php echo htmlspecialchars($doctor['license_number']); ?></td>
                                        <td><?php echo htmlspecialchars($doctor['phone']); ?></td>
                                        <td><?php echo htmlspecialchars($doctor['email']); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="?action=edit&id=<?php echo $doctor['doctor_id']; ?>" class="btn btn-warning">Edit</a>
                                                <form method="POST" action="?action=delete" style="display:inline;">
                                                    <input type="hidden" name="doctor_id" value="<?php echo $doctor['doctor_id']; ?>">
                                                    <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                <?php elseif ($action == 'add'): ?>
                    <div class="form-container">
                        <h2>Add New Doctor</h2>
                        <form method="POST" action="?action=add_doctor">
                            <div class="form-row">
                                <div class="form-col">
                                    <label>Doctor Name:</label>
                                    <input type="text" name="doctor_name" required>
                                </div>
                                <div class="form-col">
                                    <label>Specialization:</label>
                                    <input type="text" name="specialization">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-col">
                                    <label>License Number:</label>
                                    <input type="text" name="license_number">
                                </div>
                                <div class="form-col">
                                    <label>Phone:</label>
                                    <input type="text" name="phone">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-col">
                                    <label>Email:</label>
                                    <input type="email" name="email">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-col">
                                    <label>Address:</label>
                                    <textarea name="address" rows="3"></textarea>
                                </div>
                            </div>

                            <div class="action-buttons">
                                <button type="submit" class="btn btn-success">Add Doctor</button>
                                <a href="?action=manage_doctors" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>

                <?php elseif ($action == 'edit' && $current_doctor): ?>
                    <div class="form-container">
                        <h2>Edit Doctor</h2>
                        <form method="POST" action="?action=edit_doctor">
                            <input type="hidden" name="doctor_id" value="<?php echo $current_doctor['doctor_id']; ?>">

                            <div class="form-row">
                                <div class="form-col">
                                    <label>Doctor Name:</label>
                                    <input type="text" name="doctor_name" value="<?php echo htmlspecialchars($current_doctor['doctor_name']); ?>" required>
                                </div>
                                <div class="form-col">
                                    <label>Specialization:</label>
                                    <input type="text" name="specialization" value="<?php echo htmlspecialchars($current_doctor['specialization']); ?>">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-col">
                                    <label>License Number:</label>
                                    <input type="text" name="license_number" value="<?php echo htmlspecialchars($current_doctor['license_number']); ?>">
                                </div>
                                <div class="form-col">
                                    <label>Phone:</label>
                                    <input type="text" name="phone" value="<?php echo htmlspecialchars($current_doctor['phone']); ?>">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-col">
                                    <label>Email:</label>
                                    <input type="email" name="email" value="<?php echo htmlspecialchars($current_doctor['email']); ?>">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-col">
                                    <label>Address:</label>
                                    <textarea name="address" rows="3"><?php echo htmlspecialchars($current_doctor['address']); ?></textarea>
                                </div>
                            </div>

                            <div class="action-buttons">
                                <button type="submit" class="btn btn-success">Update Doctor</button>
                                <a href="?action=manage_doctors" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        function viewExamination(examId) {
            alert('View examination details - ID: ' + examId);
        }

        function viewRecord(recordId) {
            alert('View medical record details - ID: ' + recordId);
        }

        // Add this JavaScript to handle adding multiple medicines

        const medicinesOptions = `<?php echo $medicines_options; ?>`;

        document.getElementById('add-medicine').addEventListener('click', function() {
            const newMedicine = document.createElement('div');
            newMedicine.className = 'medicine-row';
            newMedicine.innerHTML = `
        <div class="form-group">
            <label>Medicine:</label>
            <select name="medicine_id[]" required>
                <option value="">-- Select Medicine --</option>
                ${medicinesOptions}
            </select>
        </div>
        <div class="form-group">
            <label>Quantity:</label>
            <input type="number" name="quantity[]" min="1" required>
        </div>
        <div class="form-group">
            <label>Dosage Instructions:</label>
            <input type="text" name="dosage_instructions[]" placeholder="e.g., 1 tab daily">
        </div>
        <div class="form-group">
            <label>Duration (days):</label>
            <input type="number" name="duration_days[]" min="1">
        </div>
        <button type="button" class="btn btn-danger remove-medicine" style="height:40px;margin-top:24px;">Remove</button>
    `;
            document.querySelector('.medicine-prescriptions').appendChild(newMedicine);

            // Add remove event
            newMedicine.querySelector('.remove-medicine').addEventListener('click', function() {
                newMedicine.remove();
            });
        });
    </script>


    <style>
        .module-nav {
            margin-bottom: 30px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
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
    </style>
</body>

</html>