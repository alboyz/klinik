<?php
require_once '../../config/config.php';
requireLogin();

$current_page = 'medicine';
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
                $medicine_code = generateCode('OB', 3);
                $medicine_name = sanitizeInput($_POST['medicine_name']);
                $description = sanitizeInput($_POST['description']);
                $dosage_form = sanitizeInput($_POST['dosage_form']);
                $strength = sanitizeInput($_POST['strength']);
                $manufacturer = sanitizeInput($_POST['manufacturer']);
                $stock_quantity = $_POST['stock_quantity'];
                $unit_price = $_POST['unit_price'];
                $expiry_date = $_POST['expiry_date'];
                
                $stmt = $db->prepare("
                    INSERT INTO medicines (medicine_code, medicine_name, description, dosage_form, strength, manufacturer, stock_quantity, unit_price, expiry_date) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$medicine_code, $medicine_name, $description, $dosage_form, $strength, $manufacturer, $stock_quantity, $unit_price, $expiry_date]);
                $message = "Medicine added successfully!";
                break;
                
            case 'edit':
                $medicine_id = $_POST['medicine_id'];
                $medicine_name = sanitizeInput($_POST['medicine_name']);
                $description = sanitizeInput($_POST['description']);
                $dosage_form = sanitizeInput($_POST['dosage_form']);
                $strength = sanitizeInput($_POST['strength']);
                $manufacturer = sanitizeInput($_POST['manufacturer']);
                $stock_quantity = $_POST['stock_quantity'];
                $unit_price = $_POST['unit_price'];
                $expiry_date = $_POST['expiry_date'];
                
                $stmt = $db->prepare("
                    UPDATE medicines SET medicine_name=?, description=?, dosage_form=?, strength=?, manufacturer=?, stock_quantity=?, unit_price=?, expiry_date=? 
                    WHERE medicine_id=?
                ");
                $stmt->execute([$medicine_name, $description, $dosage_form, $strength, $manufacturer, $stock_quantity, $unit_price, $expiry_date, $medicine_id]);
                $message = "Medicine updated successfully!";
                break;
                
            case 'delete':
                $medicine_id = $_POST['medicine_id'];
                $stmt = $db->prepare("DELETE FROM medicines WHERE medicine_id=?");
                $stmt->execute([$medicine_id]);
                $message = "Medicine deleted successfully!";
                break;
                
            case 'update_stock':
                $medicine_id = $_POST['medicine_id'];
                $new_quantity = $_POST['new_quantity'];
                $operation = $_POST['operation']; // 'add' or 'subtract'
                
                if ($operation == 'add') {
                    $stmt = $db->prepare("UPDATE medicines SET stock_quantity = stock_quantity + ? WHERE medicine_id = ?");
                } else {
                    $stmt = $db->prepare("UPDATE medicines SET stock_quantity = GREATEST(0, stock_quantity - ?) WHERE medicine_id = ?");
                }
                $stmt->execute([$new_quantity, $medicine_id]);
                $message = "Stock updated successfully!";
                break;
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
        logError('Medicine module error: ' . $e->getMessage());
    }
}

// Get data
$db = getDB();
$medicines = [];
$current_medicine = null;
$low_stock_medicines = [];

try {
    // Get all medicines
    $stmt = $db->query("SELECT * FROM medicines ORDER BY medicine_name");
    $medicines = $stmt->fetchAll();
    
    // Get low stock medicines (less than 10)
    $stmt = $db->query("SELECT * FROM medicines WHERE stock_quantity < 10 ORDER BY stock_quantity ASC");
    $low_stock_medicines = $stmt->fetchAll();
    
    // Get specific medicine if editing or viewing
    if (($action == 'edit' || $action == 'view') && $id) {
        $stmt = $db->prepare("SELECT * FROM medicines WHERE medicine_id = ?");
        $stmt->execute([$id]);
        $current_medicine = $stmt->fetch();
    }
    
} catch (PDOException $e) {
    $error = "Error loading data: " . $e->getMessage();
    logError('Medicine data loading error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medicine Management - <?php echo APP_NAME; ?></title>
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
                
                <li class="active">
                    <a href="medicine.php">
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
                <h1>Medicine Management</h1>
                <p class="current-date">Today: <?php echo date('F j, Y'); ?></p>
            </div>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <!-- Low Stock Alert -->
            <?php if (!empty($low_stock_medicines) && $action == 'list'): ?>
                <div class="alert alert-warning">
                    <h4>⚠️ Low Stock Alert</h4>
                    <p>The following medicines are running low on stock:</p>
                    <ul>
                        <?php foreach ($low_stock_medicines as $med): ?>
                            <li><?php echo htmlspecialchars($med['medicine_name']); ?> - Only <?php echo $med['stock_quantity']; ?> left</li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <!-- Action Navigation -->
            <div class="module-nav">
                <a href="?action=list" class="btn <?php echo $action == 'list' ? 'btn-primary' : 'btn-secondary'; ?>">All Medicines</a>
                <a href="?action=low_stock" class="btn <?php echo $action == 'low_stock' ? 'btn-primary' : 'btn-secondary'; ?>">Low Stock</a>
                <a href="?action=expired" class="btn <?php echo $action == 'expired' ? 'btn-primary' : 'btn-secondary'; ?>">Expired</a>
                <a href="?action=add" class="btn btn-success">Add Medicine</a>
            </div>
            
            <!-- Content based on action -->
            <?php if ($action == 'list'): ?>
                <div class="section-header">
                    <h2>All Medicines</h2>
                    <a href="?action=add" class="btn btn-success">Add New Medicine</a>
                </div>
                
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Medicine ID</th>
                                <th>Code</th>
                                <th>Medicine Name</th>
                                <th>Dosage Form</th>
                                <th>Strength</th>
                                <th>Stock</th>
                                <th>Unit Price</th>
                                <th>Expiry Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($medicines as $medicine): ?>
                                <tr class="<?php echo $medicine['stock_quantity'] < 10 ? 'low-stock' : ''; ?>">
                                    <td><?php echo $medicine['medicine_id']; ?></td>
                                    <td><?php echo htmlspecialchars($medicine['medicine_code']); ?></td>
                                    <td><?php echo htmlspecialchars($medicine['medicine_name']); ?></td>
                                    <td><?php echo htmlspecialchars($medicine['dosage_form']); ?></td>
                                    <td><?php echo htmlspecialchars($medicine['strength']); ?></td>
                                    <td>
                                        <span class="stock-quantity <?php echo $medicine['stock_quantity'] < 10 ? 'low' : ''; ?>">
                                            <?php echo $medicine['stock_quantity']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatCurrency($medicine['unit_price']); ?></td>
                                    <td>
                                        <?php 
                                        $expiry = $medicine['expiry_date'];
                                        $is_expired = $expiry && strtotime($expiry) < time();
                                        $is_expiring_soon = $expiry && strtotime($expiry) < strtotime('+30 days');
                                        ?>
                                        <span class="expiry-date <?php echo $is_expired ? 'expired' : ($is_expiring_soon ? 'expiring-soon' : ''); ?>">
                                            <?php echo formatDate($expiry); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="?action=view&id=<?php echo $medicine['medicine_id']; ?>" class="btn btn-primary">View</a>
                                            <a href="?action=edit&id=<?php echo $medicine['medicine_id']; ?>" class="btn btn-warning">Edit</a>
                                            <button onclick="updateStock(<?php echo $medicine['medicine_id']; ?>)" class="btn btn-success">Stock</button>
                                            <button onclick="deleteMedicine(<?php echo $medicine['medicine_id']; ?>)" class="btn btn-danger">Delete</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
            <?php elseif ($action == 'add'): ?>
                <div class="form-container">
                    <h2>Add New Medicine</h2>
                    <form method="POST" action="?action=add">
                        <div class="form-row">
                            <div class="form-col">
                                <label>Medicine Name:</label>
                                <input type="text" name="medicine_name" required>
                            </div>
                            <div class="form-col">
                                <label>Dosage Form:</label>
                                <select name="dosage_form">
                                    <option value="">Select Form</option>
                                    <option value="tablet">Tablet</option>
                                    <option value="capsule">Capsule</option>
                                    <option value="liquid">Liquid</option>
                                    <option value="injection">Injection</option>
                                    <option value="cream">Cream</option>
                                    <option value="ointment">Ointment</option>
                                    <option value="drops">Drops</option>
                                    <option value="spray">Spray</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-col">
                                <label>Strength:</label>
                                <input type="text" name="strength" placeholder="e.g., 250mg, 5ml">
                            </div>
                            <div class="form-col">
                                <label>Manufacturer:</label>
                                <input type="text" name="manufacturer">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-col">
                                <label>Stock Quantity:</label>
                                <input type="number" name="stock_quantity" min="0" value="0" required>
                            </div>
                            <div class="form-col">
                                <label>Unit Price:</label>
                                <input type="number" name="unit_price" step="0.01" min="0" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-col">
                                <label>Expiry Date:</label>
                                <input type="date" name="expiry_date">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-col">
                                <label>Description:</label>
                                <textarea name="description" rows="3" placeholder="Medicine description, usage, etc."></textarea>
                            </div>
                        </div>
                        
                        <div class="action-buttons">
                            <button type="submit" class="btn btn-success">Add Medicine</button>
                            <a href="?action=list" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
                
            <?php elseif ($action == 'edit' && $current_medicine): ?>
                <div class="form-container">
                    <h2>Edit Medicine</h2>
                    <form method="POST" action="?action=edit">
                        <input type="hidden" name="medicine_id" value="<?php echo $current_medicine['medicine_id']; ?>">
                        
                        <div class="form-row">
                            <div class="form-col">
                                <label>Medicine Name:</label>
                                <input type="text" name="medicine_name" value="<?php echo htmlspecialchars($current_medicine['medicine_name']); ?>" required>
                            </div>
                            <div class="form-col">
                                <label>Dosage Form:</label>
                                <select name="dosage_form">
                                    <option value="">Select Form</option>
                                    <option value="tablet" <?php echo $current_medicine['dosage_form'] == 'tablet' ? 'selected' : ''; ?>>Tablet</option>
                                    <option value="capsule" <?php echo $current_medicine['dosage_form'] == 'capsule' ? 'selected' : ''; ?>>Capsule</option>
                                    <option value="liquid" <?php echo $current_medicine['dosage_form'] == 'liquid' ? 'selected' : ''; ?>>Liquid</option>
                                    <option value="injection" <?php echo $current_medicine['dosage_form'] == 'injection' ? 'selected' : ''; ?>>Injection</option>
                                    <option value="cream" <?php echo $current_medicine['dosage_form'] == 'cream' ? 'selected' : ''; ?>>Cream</option>
                                    <option value="ointment" <?php echo $current_medicine['dosage_form'] == 'ointment' ? 'selected' : ''; ?>>Ointment</option>
                                    <option value="drops" <?php echo $current_medicine['dosage_form'] == 'drops' ? 'selected' : ''; ?>>Drops</option>
                                    <option value="spray" <?php echo $current_medicine['dosage_form'] == 'spray' ? 'selected' : ''; ?>>Spray</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-col">
                                <label>Strength:</label>
                                <input type="text" name="strength" value="<?php echo htmlspecialchars($current_medicine['strength']); ?>">
                            </div>
                            <div class="form-col">
                                <label>Manufacturer:</label>
                                <input type="text" name="manufacturer" value="<?php echo htmlspecialchars($current_medicine['manufacturer']); ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-col">
                                <label>Stock Quantity:</label>
                                <input type="number" name="stock_quantity" value="<?php echo $current_medicine['stock_quantity']; ?>" min="0" required>
                            </div>
                            <div class="form-col">
                                <label>Unit Price:</label>
                                <input type="number" name="unit_price" value="<?php echo $current_medicine['unit_price']; ?>" step="0.01" min="0" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-col">
                                <label>Expiry Date:</label>
                                <input type="date" name="expiry_date" value="<?php echo $current_medicine['expiry_date']; ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-col">
                                <label>Description:</label>
                                <textarea name="description" rows="3"><?php echo htmlspecialchars($current_medicine['description']); ?></textarea>
                            </div>
                        </div>
                        
                        <div class="action-buttons">
                            <button type="submit" class="btn btn-success">Update Medicine</button>
                            <a href="?action=list" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
                
            <?php elseif ($action == 'view' && $current_medicine): ?>
                <div class="medicine-detail">
                    <div class="detail-header">
                        <h2>Medicine Details</h2>
                        <div class="action-buttons">
                            <a href="?action=edit&id=<?php echo $id; ?>" class="btn btn-warning">Edit</a>
                            <button onclick="updateStock(<?php echo $id; ?>)" class="btn btn-success">Update Stock</button>
                            <a href="?action=list" class="btn btn-secondary">Back to List</a>
                        </div>
                    </div>
                    
                    <div class="detail-section">
                        <div class="detail-grid">
                            <div class="detail-row">
                                <label>Medicine ID:</label>
                                <span><?php echo $current_medicine['medicine_id']; ?></span>
                            </div>
                            <div class="detail-row">
                                <label>Medicine Code:</label>
                                <span><?php echo htmlspecialchars($current_medicine['medicine_code']); ?></span>
                            </div>
                            <div class="detail-row">
                                <label>Medicine Name:</label>
                                <span><?php echo htmlspecialchars($current_medicine['medicine_name']); ?></span>
                            </div>
                            <div class="detail-row">
                                <label>Dosage Form:</label>
                                <span><?php echo ucfirst($current_medicine['dosage_form']); ?></span>
                            </div>
                            <div class="detail-row">
                                <label>Strength:</label>
                                <span><?php echo htmlspecialchars($current_medicine['strength']); ?></span>
                            </div>
                            <div class="detail-row">
                                <label>Manufacturer:</label>
                                <span><?php echo htmlspecialchars($current_medicine['manufacturer']); ?></span>
                            </div>
                            <div class="detail-row">
                                <label>Stock Quantity:</label>
                                <span class="stock-quantity <?php echo $current_medicine['stock_quantity'] < 10 ? 'low' : ''; ?>">
                                    <?php echo $current_medicine['stock_quantity']; ?>
                                </span>
                            </div>
                            <div class="detail-row">
                                <label>Unit Price:</label>
                                <span><?php echo formatCurrency($current_medicine['unit_price']); ?></span>
                            </div>
                            <div class="detail-row">
                                <label>Expiry Date:</label>
                                <span class="expiry-date <?php echo strtotime($current_medicine['expiry_date']) < time() ? 'expired' : ''; ?>">
                                    <?php echo formatDate($current_medicine['expiry_date']); ?>
                                </span>
                            </div>
                            <div class="detail-row">
                                <label>Description:</label>
                                <span><?php echo htmlspecialchars($current_medicine['description']); ?></span>
                            </div>
                            <div class="detail-row">
                                <label>Added:</label>
                                <span><?php echo formatDate($current_medicine['created_at']); ?></span>
                            </div>
                            <div class="detail-row">
                                <label>Last Updated:</label>
                                <span><?php echo formatDate($current_medicine['updated_at']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
            <?php elseif ($action == 'low_stock'): ?>
                <div class="section-header">
                    <h2>Low Stock Medicines</h2>
                </div>
                
                <?php if (empty($low_stock_medicines)): ?>
                    <div class="alert alert-success">
                        <p>✅ All medicines have adequate stock levels!</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Medicine Name</th>
                                    <th>Code</th>
                                    <th>Current Stock</th>
                                    <th>Unit Price</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($low_stock_medicines as $medicine): ?>
                                    <tr class="low-stock">
                                        <td><?php echo htmlspecialchars($medicine['medicine_name']); ?></td>
                                        <td><?php echo htmlspecialchars($medicine['medicine_code']); ?></td>
                                        <td>
                                            <span class="stock-quantity low">
                                                <?php echo $medicine['stock_quantity']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo formatCurrency($medicine['unit_price']); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button onclick="updateStock(<?php echo $medicine['medicine_id']; ?>)" class="btn btn-success">Restock</button>
                                                <a href="?action=view&id=<?php echo $medicine['medicine_id']; ?>" class="btn btn-primary">View</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>
    
    <!-- Stock Update Modal -->
    <div id="stockModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close" onclick="closeStockModal()">&times;</span>
            <h3>Update Stock</h3>
            <form id="stockForm" method="POST" action="?action=update_stock">
                <input type="hidden" id="stockMedicineId" name="medicine_id">
                <div class="form-row">
                    <div class="form-col">
                        <label>Operation:</label>
                        <select name="operation" required>
                            <option value="add">Add to Stock</option>
                            <option value="subtract">Remove from Stock</option>
                        </select>
                    </div>
                    <div class="form-col">
                        <label>Quantity:</label>
                        <input type="number" name="new_quantity" min="1" required>
                    </div>
                </div>
                <div class="action-buttons">
                    <button type="submit" class="btn btn-success">Update Stock</button>
                    <button type="button" onclick="closeStockModal()" class="btn btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function updateStock(medicineId) {
            document.getElementById('stockMedicineId').value = medicineId;
            document.getElementById('stockModal').style.display = 'block';
        }
        
        function closeStockModal() {
            document.getElementById('stockModal').style.display = 'none';
        }
        
        function deleteMedicine(medicineId) {
            if (confirm('Are you sure you want to delete this medicine?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '?action=delete';
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'medicine_id';
                input.value = medicineId;
                
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('stockModal');
            if (event.target == modal) {
                modal.style.display = 'none';
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
        
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .alert-warning h4 {
            margin-bottom: 10px;
        }
        
        .alert-warning ul {
            margin: 10px 0 0 20px;
        }
        
        .low-stock {
            background-color: #fff3cd !important;
        }
        
        .stock-quantity.low {
            background: #dc3545;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
        }
        
        .expiry-date.expired {
            background: #dc3545;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
        }
        
        .expiry-date.expiring-soon {
            background: #ffc107;
            color: #212529;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
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
        
        /* Modal Styles */
        .modal {
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 20px;
            border-radius: 10px;
            width: 80%;
            max-width: 500px;
            position: relative;
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            position: absolute;
            right: 15px;
            top: 10px;
            cursor: pointer;
        }
        
        .close:hover {
            color: #000;
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
            
            .modal-content {
                width: 95%;
                margin: 10% auto;
            }
        }
    </style>
</body>
</html>

