<?php
require_once '../../config/config.php';
requireLogin();

$current_page = 'examination';
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

// Handle form submissions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $db = getDB();
    
    try {
        switch ($action) {
            case 'update_status':
                $examination_id = $_POST['examination_id'];
                $status = sanitizeInput($_POST['status']);
                
                $stmt = $db->prepare("UPDATE examinations SET status = ? WHERE examination_id = ?");
                $stmt->execute([$status, $examination_id]);
                $message = "Examination status updated successfully!";
                break;
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
        logError('Examination module error: ' . $e->getMessage());
    }
}

// Get data
$db = getDB();
$examinations = [];
$current_examination = null;

try {
    // Get all examinations with related data
    $stmt = $db->query("
        SELECT e.*, a.animal_name, a.animal_code, a.species, ao.owner_name, ao.owner_code, d.doctor_name
        FROM examinations e
        JOIN animals a ON e.animal_id = a.animal_id
        JOIN animal_owners ao ON a.owner_id = ao.owner_id
        JOIN doctors d ON e.doctor_id = d.doctor_id
        ORDER BY e.examination_date DESC
    ");
    $examinations = $stmt->fetchAll();
    
    // Get specific examination if viewing
    if ($action == 'view' && $id) {
        $stmt = $db->prepare("
            SELECT e.*, a.animal_name, a.animal_code, a.species, a.race, a.age, a.gender, a.weight,
                   ao.owner_name, ao.owner_code, ao.telephone_number, ao.email, ao.address,
                   d.doctor_name, d.specialization, d.license_number
            FROM examinations e
            JOIN animals a ON e.animal_id = a.animal_id
            JOIN animal_owners ao ON a.owner_id = ao.owner_id
            JOIN doctors d ON e.doctor_id = d.doctor_id
            WHERE e.examination_id = ?
        ");
        $stmt->execute([$id]);
        $current_examination = $stmt->fetch();
        
        // Get prescribed medicines for this examination
        if ($current_examination) {
            $stmt = $db->prepare("
                SELECT pm.*, m.medicine_name, m.medicine_code, m.dosage_form, m.strength
                FROM prescription_medicines pm
                JOIN medicines m ON pm.medicine_id = m.medicine_id
                WHERE pm.examination_id = ?
            ");
            $stmt->execute([$id]);
            $prescribed_medicines = $stmt->fetchAll();
        }
    }
    
} catch (PDOException $e) {
    $error = "Error loading data: " . $e->getMessage();
    logError('Examination data loading error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Examination Management - <?php echo APP_NAME; ?></title>
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
                
                <li class="active">
                    <a href="examination.php">
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
                <h1>Examination Management</h1>
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
                <a href="?action=list" class="btn <?php echo $action == 'list' ? 'btn-primary' : 'btn-secondary'; ?>">All Examinations</a>
                <a href="?action=pending" class="btn <?php echo $action == 'pending' ? 'btn-primary' : 'btn-secondary'; ?>">Pending</a>
                <a href="?action=completed" class="btn <?php echo $action == 'completed' ? 'btn-primary' : 'btn-secondary'; ?>">Completed</a>
                <a href="../doctor/doctor.php?action=add_examination" class="btn btn-success">New Examination</a>
            </div>
            
            <!-- Content based on action -->
            <?php if ($action == 'list' || $action == 'pending' || $action == 'completed'): ?>
                <?php
                $filtered_examinations = $examinations;
                if ($action == 'pending') {
                    $filtered_examinations = array_filter($examinations, function($e) { return $e['status'] == 'pending'; });
                } elseif ($action == 'completed') {
                    $filtered_examinations = array_filter($examinations, function($e) { return $e['status'] == 'completed'; });
                }
                ?>
                
                <div class="section-header">
                    <h2><?php echo ucfirst($action); ?> Examinations</h2>
                    <a href="../doctor/doctor.php?action=add_examination" class="btn btn-success">New Examination</a>
                </div>
                
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Exam ID</th>
                                <th>Date</th>
                                <th>Animal</th>
                                <th>Owner</th>
                                <th>Doctor</th>
                                <th>Diagnosis</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($filtered_examinations as $exam): ?>
                                <tr>
                                    <td><?php echo $exam['examination_id']; ?></td>
                                    <td><?php echo formatDate($exam['examination_date']); ?></td>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($exam['animal_name']); ?></strong><br>
                                            <small><?php echo htmlspecialchars($exam['animal_code']); ?> - <?php echo ucfirst($exam['species']); ?></small>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($exam['owner_name']); ?></td>
                                    <td><?php echo htmlspecialchars($exam['doctor_name']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($exam['diagnosis'], 0, 50)) . '...'; ?></td>
                                    <td>
                                        <span class="status status-<?php echo $exam['status']; ?>">
                                            <?php echo ucfirst($exam['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="?action=view&id=<?php echo $exam['examination_id']; ?>" class="btn btn-primary">View</a>
                                            <?php if ($exam['status'] != 'completed'): ?>
                                                <button onclick="updateStatus(<?php echo $exam['examination_id']; ?>, 'completed')" class="btn btn-success">Complete</button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
            <?php elseif ($action == 'view' && $current_examination): ?>
                <div class="examination-detail">
                    <div class="detail-header">
                        <h2>Examination Details</h2>
                        <div class="action-buttons">
                            <a href="?action=list" class="btn btn-secondary">Back to List</a>
                            <a href="../payment/payment.php?action=add" class="btn btn-success">Add Payment</a>
                        </div>
                    </div>
                    
                    <!-- Examination Information -->
                    <div class="detail-section">
                        <h3>Examination Information</h3>
                        <div class="detail-grid">
                            <div class="detail-row">
                                <label>Examination ID:</label>
                                <span><?php echo $current_examination['examination_id']; ?></span>
                            </div>
                            <div class="detail-row">
                                <label>Examination Date:</label>
                                <span><?php echo formatDate($current_examination['examination_date']); ?></span>
                            </div>
                            <div class="detail-row">
                                <label>Doctor:</label>
                                <span><?php echo htmlspecialchars($current_examination['doctor_name']); ?></span>
                            </div>
                            <div class="detail-row">
                                <label>Specialization:</label>
                                <span><?php echo htmlspecialchars($current_examination['specialization']); ?></span>
                            </div>
                            <div class="detail-row">
                                <label>Status:</label>
                                <span class="status status-<?php echo $current_examination['status']; ?>">
                                    <?php echo ucfirst($current_examination['status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Animal Information -->
                    <div class="detail-section">
                        <h3>Animal Information</h3>
                        <div class="detail-grid">
                            <div class="detail-row">
                                <label>Animal Name:</label>
                                <span><?php echo htmlspecialchars($current_examination['animal_name']); ?></span>
                            </div>
                            <div class="detail-row">
                                <label>Animal Code:</label>
                                <span><?php echo htmlspecialchars($current_examination['animal_code']); ?></span>
                            </div>
                            <div class="detail-row">
                                <label>Species:</label>
                                <span><?php echo ucfirst($current_examination['species']); ?></span>
                            </div>
                            <div class="detail-row">
                                <label>Race:</label>
                                <span><?php echo htmlspecialchars($current_examination['race']); ?></span>
                            </div>
                            <div class="detail-row">
                                <label>Age:</label>
                                <span><?php echo $current_examination['age']; ?> years</span>
                            </div>
                            <div class="detail-row">
                                <label>Gender:</label>
                                <span><?php echo ucfirst($current_examination['gender']); ?></span>
                            </div>
                            <div class="detail-row">
                                <label>Weight:</label>
                                <span><?php echo $current_examination['weight']; ?> kg</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Owner Information -->
                    <div class="detail-section">
                        <h3>Owner Information</h3>
                        <div class="detail-grid">
                            <div class="detail-row">
                                <label>Owner Name:</label>
                                <span><?php echo htmlspecialchars($current_examination['owner_name']); ?></span>
                            </div>
                            <div class="detail-row">
                                <label>Owner Code:</label>
                                <span><?php echo htmlspecialchars($current_examination['owner_code']); ?></span>
                            </div>
                            <div class="detail-row">
                                <label>Phone:</label>
                                <span><?php echo htmlspecialchars($current_examination['telephone_number']); ?></span>
                            </div>
                            <div class="detail-row">
                                <label>Email:</label>
                                <span><?php echo htmlspecialchars($current_examination['email']); ?></span>
                            </div>
                            <div class="detail-row">
                                <label>Address:</label>
                                <span><?php echo htmlspecialchars($current_examination['address']); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Medical Details -->
                    <div class="detail-section">
                        <h3>Medical Details</h3>
                        <div class="medical-details">
                            <div class="medical-row">
                                <label>Important Disease History:</label>
                                <div class="medical-content">
                                    <?php echo htmlspecialchars($current_examination['important_disease_history'] ?: 'None recorded'); ?>
                                </div>
                            </div>
                            <div class="medical-row">
                                <label>Allergies:</label>
                                <div class="medical-content">
                                    <?php echo htmlspecialchars($current_examination['allergies'] ?: 'None recorded'); ?>
                                </div>
                            </div>
                            <div class="medical-row">
                                <label>Diagnosis:</label>
                                <div class="medical-content">
                                    <?php echo htmlspecialchars($current_examination['diagnosis']); ?>
                                </div>
                            </div>
                            <div class="medical-row">
                                <label>Routine Drugs:</label>
                                <div class="medical-content">
                                    <?php echo htmlspecialchars($current_examination['routine_drugs'] ?: 'None prescribed'); ?>
                                </div>
                            </div>
                            <div class="medical-row">
                                <label>Action Taken:</label>
                                <div class="medical-content">
                                    <?php echo htmlspecialchars($current_examination['action_taken']); ?>
                                </div>
                            </div>
                            <div class="medical-row">
                                <label>Additional Notes:</label>
                                <div class="medical-content">
                                    <?php echo htmlspecialchars($current_examination['notes'] ?: 'No additional notes'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Prescribed Medicines -->
                    <?php if (isset($prescribed_medicines) && !empty($prescribed_medicines)): ?>
                        <div class="detail-section">
                            <h3>Prescribed Medicines</h3>
                            <div class="table-container">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Medicine</th>
                                            <th>Code</th>
                                            <th>Form</th>
                                            <th>Strength</th>
                                            <th>Quantity</th>
                                            <th>Dosage Instructions</th>
                                            <th>Duration</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($prescribed_medicines as $med): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($med['medicine_name']); ?></td>
                                                <td><?php echo htmlspecialchars($med['medicine_code']); ?></td>
                                                <td><?php echo ucfirst($med['dosage_form']); ?></td>
                                                <td><?php echo htmlspecialchars($med['strength']); ?></td>
                                                <td><?php echo $med['quantity']; ?></td>
                                                <td><?php echo htmlspecialchars($med['dosage_instructions']); ?></td>
                                                <td><?php echo $med['duration_days']; ?> days</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
    
    <script>
        function updateStatus(examinationId, status) {
            if (confirm('Are you sure you want to mark this examination as ' + status + '?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '?action=update_status';
                
                const examInput = document.createElement('input');
                examInput.type = 'hidden';
                examInput.name = 'examination_id';
                examInput.value = examinationId;
                
                const statusInput = document.createElement('input');
                statusInput.type = 'hidden';
                statusInput.name = 'status';
                statusInput.value = status;
                
                form.appendChild(examInput);
                form.appendChild(statusInput);
                document.body.appendChild(form);
                form.submit();
            }
        }
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
        
        .detail-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .detail-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        
        .detail-section h3 {
            margin-bottom: 20px;
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
        }
        
        .detail-row {
            display: flex;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .detail-row label {
            font-weight: 600;
            width: 150px;
            color: #555;
        }
        
        .detail-row span {
            flex: 1;
            color: #333;
        }
        
        .medical-details {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .medical-row {
            display: flex;
            flex-direction: column;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #3498db;
        }
        
        .medical-row label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
        }
        
        .medical-content {
            color: #333;
            line-height: 1.6;
            white-space: pre-wrap;
        }
        
        @media (max-width: 768px) {
            .detail-grid {
                grid-template-columns: 1fr;
            }
            
            .detail-row {
                flex-direction: column;
            }
            
            .detail-row label {
                width: auto;
                margin-bottom: 5px;
            }
        }
    </style>
</body>
</html>

