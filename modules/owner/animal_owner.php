<?php
require_once '../../config/config.php';
requireLogin();

$current_page = 'animal_owner';
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

// Handle form submissions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $db = getDB();
    
    try {
        switch ($action) {
            case 'add':
                $owner_code = generateCode('PH', 3);
                $owner_name = sanitizeInput($_POST['owner_name']);
                $address = sanitizeInput($_POST['address']);
                $telephone_number = sanitizeInput($_POST['telephone_number']);
                $email = sanitizeInput($_POST['email']);
                
                $stmt = $db->prepare("
                    INSERT INTO animal_owners (owner_code, owner_name, address, telephone_number, email) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$owner_code, $owner_name, $address, $telephone_number, $email]);
                $message = "Animal owner added successfully!";
                break;
                
            case 'edit':
                $owner_id = $_POST['owner_id'];
                $owner_name = sanitizeInput($_POST['owner_name']);
                $address = sanitizeInput($_POST['address']);
                $telephone_number = sanitizeInput($_POST['telephone_number']);
                $email = sanitizeInput($_POST['email']);
                
                $stmt = $db->prepare("
                    UPDATE animal_owners SET owner_name=?, address=?, telephone_number=?, email=? 
                    WHERE owner_id=?
                ");
                $stmt->execute([$owner_name, $address, $telephone_number, $email, $owner_id]);
                $message = "Animal owner updated successfully!";
                break;
                
            case 'delete':
                $owner_id = $_POST['owner_id'];
                $stmt = $db->prepare("DELETE FROM animal_owners WHERE owner_id=?");
                $stmt->execute([$owner_id]);
                $message = "Animal owner deleted successfully!";
                break;
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
        logError('Animal owner module error: ' . $e->getMessage());
    }
}

// Get data
$db = getDB();
$owners = [];
$current_owner = null;

try {
    // Get all owners
    $stmt = $db->query("SELECT * FROM animal_owners ORDER BY created_at DESC");
    $owners = $stmt->fetchAll();
    
    // Get specific owner if editing or viewing
    if (($action == 'edit' || $action == 'view') && $id) {
        $stmt = $db->prepare("SELECT * FROM animal_owners WHERE owner_id = ?");
        $stmt->execute([$id]);
        $current_owner = $stmt->fetch();
    }
    
} catch (PDOException $e) {
    $error = "Error loading data: " . $e->getMessage();
    logError('Animal owner data loading error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Animal Owner Management - <?php echo APP_NAME; ?></title>
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
                
                <li class="active">
                    <a href="animal_owner.php">
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
                <h1>Animal Owner Management</h1>
                <p class="current-date">Today: <?php echo date('F j, Y'); ?></p>
            </div>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <!-- Content based on action -->
            <?php if ($action == 'list'): ?>
                <div class="section-header">
                    <h2>Animal Owners</h2>
                    <a href="?action=add" class="btn btn-success">Add New Owner</a>
                </div>
                
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Owner ID</th>
                                <th>Owner Code</th>
                                <th>Owner Name</th>
                                <th>Telephone Number</th>
                                <th>Email</th>
                                <th>Address</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($owners as $owner): ?>
                                <tr>
                                    <td><?php echo $owner['owner_id']; ?></td>
                                    <td><?php echo htmlspecialchars($owner['owner_code']); ?></td>
                                    <td><?php echo htmlspecialchars($owner['owner_name']); ?></td>
                                    <td><?php echo htmlspecialchars($owner['telephone_number']); ?></td>
                                    <td><?php echo htmlspecialchars($owner['email']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($owner['address'], 0, 30)) . '...'; ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="?action=view&id=<?php echo $owner['owner_id']; ?>" class="btn btn-primary">View</a>
                                            <a href="?action=edit&id=<?php echo $owner['owner_id']; ?>" class="btn btn-warning">Edit</a>
                                            <button onclick="deleteOwner(<?php echo $owner['owner_id']; ?>)" class="btn btn-danger">Delete</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
            <?php elseif ($action == 'add'): ?>
                <div class="form-container">
                    <h2>Add New Animal Owner</h2>
                    <form method="POST" action="?action=add">
                        <div class="form-row">
                            <div class="form-col">
                                <label>Owner Name:</label>
                                <input type="text" name="owner_name" required>
                            </div>
                            <div class="form-col">
                                <label>Telephone Number:</label>
                                <input type="text" name="telephone_number" required>
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
                                <textarea name="address" rows="4" required></textarea>
                            </div>
                        </div>
                        
                        <div class="action-buttons">
                            <button type="submit" class="btn btn-success">Add Owner</button>
                            <a href="?action=list" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
                
            <?php elseif ($action == 'edit' && $current_owner): ?>
                <div class="form-container">
                    <h2>Edit Animal Owner</h2>
                    <form method="POST" action="?action=edit">
                        <input type="hidden" name="owner_id" value="<?php echo $current_owner['owner_id']; ?>">
                        
                        <div class="form-row">
                            <div class="form-col">
                                <label>Owner Name:</label>
                                <input type="text" name="owner_name" value="<?php echo htmlspecialchars($current_owner['owner_name']); ?>" required>
                            </div>
                            <div class="form-col">
                                <label>Telephone Number:</label>
                                <input type="text" name="telephone_number" value="<?php echo htmlspecialchars($current_owner['telephone_number']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-col">
                                <label>Email:</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($current_owner['email']); ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-col">
                                <label>Address:</label>
                                <textarea name="address" rows="4" required><?php echo htmlspecialchars($current_owner['address']); ?></textarea>
                            </div>
                        </div>
                        
                        <div class="action-buttons">
                            <button type="submit" class="btn btn-success">Update Owner</button>
                            <a href="?action=list" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
                
            <?php elseif ($action == 'view' && $current_owner): ?>
                <?php
                // Get owner's animals
                $stmt = $db->prepare("SELECT * FROM animals WHERE owner_id = ? ORDER BY animal_name");
                $stmt->execute([$id]);
                $owner_animals = $stmt->fetchAll();
                
                // Get examination dates for each animal
                $animal_examinations = [];
                foreach ($owner_animals as $animal) {
                    $stmt = $db->prepare("
                        SELECT e.examination_date, e.examination_id, d.doctor_name
                        FROM examinations e
                        JOIN doctors d ON e.doctor_id = d.doctor_id
                        WHERE e.animal_id = ?
                        ORDER BY e.examination_date DESC
                    ");
                    $stmt->execute([$animal['animal_id']]);
                    $animal_examinations[$animal['animal_id']] = $stmt->fetchAll();
                }
                
                // Get payments
                $stmt = $db->prepare("
                    SELECT p.*, e.examination_id
                    FROM payments p
                    LEFT JOIN examinations e ON p.examination_id = e.examination_id
                    WHERE p.owner_id = ?
                    ORDER BY p.payment_date DESC
                ");
                $stmt->execute([$id]);
                $payments = $stmt->fetchAll();
                ?>
                
                <div class="owner-detail">
                    <div class="detail-header">
                        <h2>Animal Owner Details</h2>
                        <div class="action-buttons">
                            <a href="?action=edit&id=<?php echo $id; ?>" class="btn btn-warning">Edit</a>
                            <a href="?action=list" class="btn btn-secondary">Back to List</a>
                        </div>
                    </div>
                    
                    <!-- Owner Information -->
                    <div class="detail-section">
                        <h3>Owner Information</h3>
                        <div class="detail-grid">
                            <div class="detail-row">
                                <label>Owner ID:</label>
                                <span><?php echo $current_owner['owner_id']; ?></span>
                            </div>
                            <div class="detail-row">
                                <label>Owner Code:</label>
                                <span><?php echo htmlspecialchars($current_owner['owner_code']); ?></span>
                            </div>
                            <div class="detail-row">
                                <label>Owner Name:</label>
                                <span><?php echo htmlspecialchars($current_owner['owner_name']); ?></span>
                            </div>
                            <div class="detail-row">
                                <label>Telephone Number:</label>
                                <span><?php echo htmlspecialchars($current_owner['telephone_number']); ?></span>
                            </div>
                            <div class="detail-row">
                                <label>Email:</label>
                                <span><?php echo htmlspecialchars($current_owner['email']); ?></span>
                            </div>
                            <div class="detail-row">
                                <label>Address:</label>
                                <span><?php echo htmlspecialchars($current_owner['address']); ?></span>
                            </div>
                            <div class="detail-row">
                                <label>Registered:</label>
                                <span><?php echo formatDate($current_owner['created_at']); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Animals -->
                    <div class="animals-section">
                        <h3>Animals (<?php echo count($owner_animals); ?>)</h3>
                        <?php if (empty($owner_animals)): ?>
                            <p class="no-data">No animals registered for this owner.</p>
                            <a href="../animal/animal.php?action=add" class="btn btn-success">Add Animal</a>
                        <?php else: ?>
                            <div class="animals-grid">
                                <?php foreach ($owner_animals as $animal): ?>
                                    <div class="animal-card">
                                        <div class="animal-header">
                                            <h4><?php echo htmlspecialchars($animal['animal_name']); ?></h4>
                                            <span class="animal-code"><?php echo htmlspecialchars($animal['animal_code']); ?></span>
                                        </div>
                                        <div class="animal-info">
                                            <p><strong>Species:</strong> <?php echo ucfirst($animal['species']); ?></p>
                                            <p><strong>Race:</strong> <?php echo htmlspecialchars($animal['race']); ?></p>
                                            <p><strong>Age:</strong> <?php echo $animal['age']; ?> years</p>
                                            <p><strong>Gender:</strong> <?php echo ucfirst($animal['gender']); ?></p>
                                            <p><strong>Weight:</strong> <?php echo $animal['weight']; ?> kg</p>
                                        </div>
                                        
                                        <!-- Examination History for this animal -->
                                        <div class="animal-examinations">
                                            <h5>Recent Examinations</h5>
                                            <?php if (empty($animal_examinations[$animal['animal_id']])): ?>
                                                <p class="no-data">No examinations yet</p>
                                            <?php else: ?>
                                                <ul class="examination-list">
                                                    <?php foreach (array_slice($animal_examinations[$animal['animal_id']], 0, 3) as $exam): ?>
                                                        <li>
                                                            <span class="exam-date"><?php echo formatDate($exam['examination_date']); ?></span>
                                                            <span class="exam-doctor">Dr. <?php echo htmlspecialchars($exam['doctor_name']); ?></span>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                                <?php if (count($animal_examinations[$animal['animal_id']]) > 3): ?>
                                                    <p class="more-exams">+ <?php echo count($animal_examinations[$animal['animal_id']]) - 3; ?> more examinations</p>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="animal-actions">
                                            <a href="../animal/animal.php?action=view&id=<?php echo $animal['animal_id']; ?>" class="btn btn-primary">View Details</a>
                                            <a href="../doctor/doctor.php?action=add_examination" class="btn btn-success">New Exam</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Payment History -->
                    <div class="payments-section">
                        <h3>Payment History</h3>
                        <?php if (empty($payments)): ?>
                            <p class="no-data">No payments recorded for this owner.</p>
                        <?php else: ?>
                            <div class="table-container">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Payment ID</th>
                                            <th>Amount</th>
                                            <th>Date</th>
                                            <th>Method</th>
                                            <th>Description</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($payments as $payment): ?>
                                            <tr>
                                                <td><?php echo $payment['payment_id']; ?></td>
                                                <td><?php echo formatCurrency($payment['amount']); ?></td>
                                                <td><?php echo formatDate($payment['payment_date']); ?></td>
                                                <td><?php echo ucfirst($payment['payment_method']); ?></td>
                                                <td><?php echo htmlspecialchars(substr($payment['description'], 0, 30)) . '...'; ?></td>
                                                <td>
                                                    <span class="status status-<?php echo $payment['status']; ?>">
                                                        <?php echo ucfirst($payment['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="../payment/payment.php?action=view&id=<?php echo $payment['payment_id']; ?>" class="btn btn-primary">View</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
    
    <script>
        function deleteOwner(ownerId) {
            if (confirm('Are you sure you want to delete this owner? This will also delete all related animals, examinations, and payments.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '?action=delete';
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'owner_id';
                input.value = ownerId;
                
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
    
    <style>
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
        
        .animals-section, .payments-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        
        .animals-section h3, .payments-section h3 {
            margin-bottom: 20px;
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        
        .animals-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .animal-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            background: #f8f9fa;
        }
        
        .animal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .animal-header h4 {
            margin: 0;
            color: #2c3e50;
        }
        
        .animal-code {
            background: #3498db;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
        }
        
        .animal-info p {
            margin: 5px 0;
            font-size: 14px;
        }
        
        .animal-examinations {
            margin: 15px 0;
        }
        
        .animal-examinations h5 {
            margin-bottom: 10px;
            color: #555;
            font-size: 14px;
        }
        
        .examination-list {
            list-style: none;
            padding: 0;
        }
        
        .examination-list li {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px solid #eee;
            font-size: 12px;
        }
        
        .exam-date {
            font-weight: 600;
        }
        
        .more-exams {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        .animal-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .animal-actions .btn {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        .status-paid {
            background: #d4edda;
            color: #155724;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-partial {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        
        @media (max-width: 768px) {
            .detail-grid {
                grid-template-columns: 1fr;
            }
            
            .animals-grid {
                grid-template-columns: 1fr;
            }
            
            .detail-row {
                flex-direction: column;
            }
            
            .detail-row label {
                width: auto;
                margin-bottom: 5px;
            }
            
            .animal-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .animal-actions {
                flex-direction: column;
            }
        }
    </style>
</body>
</html>

