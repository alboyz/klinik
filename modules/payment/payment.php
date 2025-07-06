<?php
require_once '../../config/config.php';
requireLogin();

$current_page = 'payment';
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
                $examination_id = $_POST['examination_id'] ?: null;
                $owner_id = $_POST['owner_id'];
                $amount = $_POST['amount'];
                $payment_date = $_POST['payment_date'];
                $payment_method = sanitizeInput($_POST['payment_method']);
                $description = sanitizeInput($_POST['description']);
                $status = sanitizeInput($_POST['status']);
                
                $stmt = $db->prepare("
                    INSERT INTO payments (examination_id, owner_id, amount, payment_date, payment_method, description, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$examination_id, $owner_id, $amount, $payment_date, $payment_method, $description, $status]);
                $message = "Payment added successfully!";
                break;
                
            case 'edit':
                $payment_id = $_POST['payment_id'];
                $examination_id = $_POST['examination_id'] ?: null;
                $owner_id = $_POST['owner_id'];
                $amount = $_POST['amount'];
                $payment_date = $_POST['payment_date'];
                $payment_method = sanitizeInput($_POST['payment_method']);
                $description = sanitizeInput($_POST['description']);
                $status = sanitizeInput($_POST['status']);
                
                $stmt = $db->prepare("
                    UPDATE payments SET examination_id=?, owner_id=?, amount=?, payment_date=?, payment_method=?, description=?, status=? 
                    WHERE payment_id=?
                ");
                $stmt->execute([$examination_id, $owner_id, $amount, $payment_date, $payment_method, $description, $status, $payment_id]);
                $message = "Payment updated successfully!";
                break;
                
            case 'delete':
                $payment_id = $_POST['payment_id'];
                $stmt = $db->prepare("DELETE FROM payments WHERE payment_id=?");
                $stmt->execute([$payment_id]);
                $message = "Payment deleted successfully!";
                break;
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
        logError('Payment module error: ' . $e->getMessage());
    }
}

// Get data
$db = getDB();
$payments = [];
$owners = [];
$examinations = [];
$current_payment = null;

try {
    // Get all payments with related data
    $stmt = $db->query("
        SELECT p.*, ao.owner_name, ao.owner_code, e.examination_date, a.animal_name
        FROM payments p
        JOIN animal_owners ao ON p.owner_id = ao.owner_id
        LEFT JOIN examinations e ON p.examination_id = e.examination_id
        LEFT JOIN animals a ON e.animal_id = a.animal_id
        ORDER BY p.payment_date DESC
    ");
    $payments = $stmt->fetchAll();
    
    // Get all owners for dropdown
    $stmt = $db->query("SELECT * FROM animal_owners ORDER BY owner_name");
    $owners = $stmt->fetchAll();
    
    // Get all examinations for dropdown
    $stmt = $db->query("
        SELECT e.examination_id, e.examination_date, a.animal_name, ao.owner_name
        FROM examinations e
        JOIN animals a ON e.animal_id = a.animal_id
        JOIN animal_owners ao ON a.owner_id = ao.owner_id
        ORDER BY e.examination_date DESC
    ");
    $examinations = $stmt->fetchAll();
    
    // Get specific payment if editing or viewing
    if (($action == 'edit' || $action == 'view') && $id) {
        $stmt = $db->prepare("
            SELECT p.*, ao.owner_name, ao.owner_code, e.examination_date, a.animal_name
            FROM payments p
            JOIN animal_owners ao ON p.owner_id = ao.owner_id
            LEFT JOIN examinations e ON p.examination_id = e.examination_id
            LEFT JOIN animals a ON e.animal_id = a.animal_id
            WHERE p.payment_id = ?
        ");
        $stmt->execute([$id]);
        $current_payment = $stmt->fetch();
    }
    
} catch (PDOException $e) {
    $error = "Error loading data: " . $e->getMessage();
    logError('Payment data loading error: ' . $e->getMessage());
}

// Calculate statistics
$total_revenue = 0;
$pending_amount = 0;
$paid_amount = 0;

foreach ($payments as $payment) {
    $total_revenue += $payment['amount'];
    if ($payment['status'] == 'paid') {
        $paid_amount += $payment['amount'];
    } elseif ($payment['status'] == 'pending') {
        $pending_amount += $payment['amount'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Management - <?php echo APP_NAME; ?></title>
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
                
                <li class="active">
                    <a href="payment.php">
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
                <h1>Payment Management</h1>
                <p class="current-date">Today: <?php echo date('F j, Y'); ?></p>
            </div>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <!-- Payment Statistics -->
            <?php if ($action == 'list'): ?>
                <div class="payment-stats">
                    <div class="stat-card">
                        <div class="stat-icon">💰</div>
                        <div class="stat-info">
                            <h3><?php echo formatCurrency($total_revenue); ?></h3>
                            <p>Total Revenue</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">✅</div>
                        <div class="stat-info">
                            <h3><?php echo formatCurrency($paid_amount); ?></h3>
                            <p>Paid Amount</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">⏳</div>
                        <div class="stat-info">
                            <h3><?php echo formatCurrency($pending_amount); ?></h3>
                            <p>Pending Amount</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">📊</div>
                        <div class="stat-info">
                            <h3><?php echo count($payments); ?></h3>
                            <p>Total Payments</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Action Navigation -->
            <div class="module-nav">
                <a href="?action=list" class="btn <?php echo $action == 'list' ? 'btn-primary' : 'btn-secondary'; ?>">All Payments</a>
                <a href="?action=pending" class="btn <?php echo $action == 'pending' ? 'btn-primary' : 'btn-secondary'; ?>">Pending</a>
                <a href="?action=paid" class="btn <?php echo $action == 'paid' ? 'btn-primary' : 'btn-secondary'; ?>">Paid</a>
                <a href="?action=add" class="btn btn-success">Add Payment</a>
            </div>
            
            <!-- Content based on action -->
            <?php if ($action == 'list' || $action == 'pending' || $action == 'paid'): ?>
                <?php
                $filtered_payments = $payments;
                if ($action == 'pending') {
                    $filtered_payments = array_filter($payments, function($p) { return $p['status'] == 'pending'; });
                } elseif ($action == 'paid') {
                    $filtered_payments = array_filter($payments, function($p) { return $p['status'] == 'paid'; });
                }
                ?>
                
                <div class="section-header">
                    <h2><?php echo ucfirst($action); ?> Payments</h2>
                    <a href="?action=add" class="btn btn-success">Add New Payment</a>
                </div>
                
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Payment ID</th>
                                <th>Owner</th>
                                <th>Animal</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Method</th>
                                <th>Status</th>
                                <th>Description</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($filtered_payments as $payment): ?>
                                <tr>
                                    <td><?php echo $payment['payment_id']; ?></td>
                                    <td><?php echo htmlspecialchars($payment['owner_name']); ?></td>
                                    <td><?php echo htmlspecialchars($payment['animal_name'] ?: 'N/A'); ?></td>
                                    <td><?php echo formatCurrency($payment['amount']); ?></td>
                                    <td><?php echo formatDate($payment['payment_date']); ?></td>
                                    <td><?php echo ucfirst($payment['payment_method']); ?></td>
                                    <td>
                                        <span class="status status-<?php echo $payment['status']; ?>">
                                            <?php echo ucfirst($payment['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars(substr($payment['description'], 0, 30)) . '...'; ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="?action=view&id=<?php echo $payment['payment_id']; ?>" class="btn btn-primary">View</a>
                                            <a href="?action=edit&id=<?php echo $payment['payment_id']; ?>" class="btn btn-warning">Edit</a>
                                            <button onclick="deletePayment(<?php echo $payment['payment_id']; ?>)" class="btn btn-danger">Delete</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
            <?php elseif ($action == 'add'): ?>
                <div class="form-container">
                    <h2>Add New Payment</h2>
                    <form method="POST" action="?action=add">
                        <div class="form-row">
                            <div class="form-col">
                                <label>Owner:</label>
                                <select name="owner_id" required>
                                    <option value="">Select Owner</option>
                                    <?php foreach ($owners as $owner): ?>
                                        <option value="<?php echo $owner['owner_id']; ?>">
                                            <?php echo htmlspecialchars($owner['owner_name'] . ' (' . $owner['owner_code'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-col">
                                <label>Related Examination (Optional):</label>
                                <select name="examination_id">
                                    <option value="">Select Examination</option>
                                    <?php foreach ($examinations as $exam): ?>
                                        <option value="<?php echo $exam['examination_id']; ?>">
                                            <?php echo htmlspecialchars($exam['animal_name'] . ' - ' . $exam['owner_name'] . ' (' . formatDate($exam['examination_date']) . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-col">
                                <label>Amount:</label>
                                <input type="number" name="amount" step="0.01" min="0" required>
                            </div>
                            <div class="form-col">
                                <label>Payment Date:</label>
                                <input type="date" name="payment_date" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-col">
                                <label>Payment Method:</label>
                                <select name="payment_method" required>
                                    <option value="">Select Method</option>
                                    <option value="cash">Cash</option>
                                    <option value="card">Card</option>
                                    <option value="transfer">Bank Transfer</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="form-col">
                                <label>Status:</label>
                                <select name="status" required>
                                    <option value="pending">Pending</option>
                                    <option value="paid">Paid</option>
                                    <option value="partial">Partial</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-col">
                                <label>Description:</label>
                                <textarea name="description" rows="3" placeholder="Payment description, services provided, etc."></textarea>
                            </div>
                        </div>
                        
                        <div class="action-buttons">
                            <button type="submit" class="btn btn-success">Add Payment</button>
                            <a href="?action=list" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
                
            <?php elseif ($action == 'edit' && $current_payment): ?>
                <div class="form-container">
                    <h2>Edit Payment</h2>
                    <form method="POST" action="?action=edit">
                        <input type="hidden" name="payment_id" value="<?php echo $current_payment['payment_id']; ?>">
                        
                        <div class="form-row">
                            <div class="form-col">
                                <label>Owner:</label>
                                <select name="owner_id" required>
                                    <option value="">Select Owner</option>
                                    <?php foreach ($owners as $owner): ?>
                                        <option value="<?php echo $owner['owner_id']; ?>" <?php echo $owner['owner_id'] == $current_payment['owner_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($owner['owner_name'] . ' (' . $owner['owner_code'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-col">
                                <label>Related Examination (Optional):</label>
                                <select name="examination_id">
                                    <option value="">Select Examination</option>
                                    <?php foreach ($examinations as $exam): ?>
                                        <option value="<?php echo $exam['examination_id']; ?>" <?php echo $exam['examination_id'] == $current_payment['examination_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($exam['animal_name'] . ' - ' . $exam['owner_name'] . ' (' . formatDate($exam['examination_date']) . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-col">
                                <label>Amount:</label>
                                <input type="number" name="amount" value="<?php echo $current_payment['amount']; ?>" step="0.01" min="0" required>
                            </div>
                            <div class="form-col">
                                <label>Payment Date:</label>
                                <input type="date" name="payment_date" value="<?php echo $current_payment['payment_date']; ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-col">
                                <label>Payment Method:</label>
                                <select name="payment_method" required>
                                    <option value="">Select Method</option>
                                    <option value="cash" <?php echo $current_payment['payment_method'] == 'cash' ? 'selected' : ''; ?>>Cash</option>
                                    <option value="card" <?php echo $current_payment['payment_method'] == 'card' ? 'selected' : ''; ?>>Card</option>
                                    <option value="transfer" <?php echo $current_payment['payment_method'] == 'transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                                    <option value="other" <?php echo $current_payment['payment_method'] == 'other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            <div class="form-col">
                                <label>Status:</label>
                                <select name="status" required>
                                    <option value="pending" <?php echo $current_payment['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="paid" <?php echo $current_payment['status'] == 'paid' ? 'selected' : ''; ?>>Paid</option>
                                    <option value="partial" <?php echo $current_payment['status'] == 'partial' ? 'selected' : ''; ?>>Partial</option>
                                    <option value="cancelled" <?php echo $current_payment['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-col">
                                <label>Description:</label>
                                <textarea name="description" rows="3"><?php echo htmlspecialchars($current_payment['description']); ?></textarea>
                            </div>
                        </div>
                        
                        <div class="action-buttons">
                            <button type="submit" class="btn btn-success">Update Payment</button>
                            <a href="?action=list" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
                
            <?php elseif ($action == 'view' && $current_payment): ?>
                <div class="payment-detail">
                    <div class="detail-header">
                        <h2>Payment Details</h2>
                        <div class="action-buttons">
                            <a href="?action=edit&id=<?php echo $id; ?>" class="btn btn-warning">Edit</a>
                            <a href="?action=list" class="btn btn-secondary">Back to List</a>
                        </div>
                    </div>
                    
                    <div class="detail-section">
                        <div class="detail-grid">
                            <div class="detail-row">
                                <label>Payment ID:</label>
                                <span><?php echo $current_payment['payment_id']; ?></span>
                            </div>
                            <div class="detail-row">
                                <label>Owner:</label>
                                <span><?php echo htmlspecialchars($current_payment['owner_name']); ?></span>
                            </div>
                            <div class="detail-row">
                                <label>Owner Code:</label>
                                <span><?php echo htmlspecialchars($current_payment['owner_code']); ?></span>
                            </div>
                            <div class="detail-row">
                                <label>Animal:</label>
                                <span><?php echo htmlspecialchars($current_payment['animal_name'] ?: 'N/A'); ?></span>
                            </div>
                            <div class="detail-row">
                                <label>Examination Date:</label>
                                <span><?php echo formatDate($current_payment['examination_date']); ?></span>
                            </div>
                            <div class="detail-row">
                                <label>Amount:</label>
                                <span class="amount"><?php echo formatCurrency($current_payment['amount']); ?></span>
                            </div>
                            <div class="detail-row">
                                <label>Payment Date:</label>
                                <span><?php echo formatDate($current_payment['payment_date']); ?></span>
                            </div>
                            <div class="detail-row">
                                <label>Payment Method:</label>
                                <span><?php echo ucfirst($current_payment['payment_method']); ?></span>
                            </div>
                            <div class="detail-row">
                                <label>Status:</label>
                                <span class="status status-<?php echo $current_payment['status']; ?>">
                                    <?php echo ucfirst($current_payment['status']); ?>
                                </span>
                            </div>
                            <div class="detail-row">
                                <label>Description:</label>
                                <span><?php echo htmlspecialchars($current_payment['description']); ?></span>
                            </div>
                            <div class="detail-row">
                                <label>Created:</label>
                                <span><?php echo formatDate($current_payment['created_at']); ?></span>
                            </div>
                            <div class="detail-row">
                                <label>Last Updated:</label>
                                <span><?php echo formatDate($current_payment['updated_at']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
    
    <script>
        function deletePayment(paymentId) {
            if (confirm('Are you sure you want to delete this payment?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '?action=delete';
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'payment_id';
                input.value = paymentId;
                
                form.appendChild(input);
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
        
        .payment-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
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
        
        .amount {
            font-size: 1.2em;
            font-weight: bold;
            color: #27ae60;
        }
        
        .status-paid {
            background: #d4edda;
            color: #155724;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-partial {
            background: #d1ecf1;
            color: #0c5460;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        @media (max-width: 768px) {
            .payment-stats {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
            
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

