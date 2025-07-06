<?php
require_once '../../config/config.php';
requireLogin();

$current_page = 'animal';
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
                $animal_code = generateCode('AN', 3);
                $animal_name = sanitizeInput($_POST['animal_name']);
                $owner_id = $_POST['owner_id'];
                $identifying_signs = sanitizeInput($_POST['identifying_signs']);
                $species = sanitizeInput($_POST['species']);
                $race = sanitizeInput($_POST['race']);
                $age = $_POST['age'];
                $gender = sanitizeInput($_POST['gender']);
                $date_of_birth = $_POST['date_of_birth'];
                $weight = $_POST['weight'];
                
                $stmt = $db->prepare("
                    INSERT INTO animals (animal_code, animal_name, owner_id, identifying_signs, species, race, age, gender, date_of_birth, weight) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$animal_code, $animal_name, $owner_id, $identifying_signs, $species, $race, $age, $gender, $date_of_birth, $weight]);
                $message = "Animal added successfully!";
                break;
                
            case 'edit':
                $animal_id = $_POST['animal_id'];
                $animal_name = sanitizeInput($_POST['animal_name']);
                $owner_id = $_POST['owner_id'];
                $identifying_signs = sanitizeInput($_POST['identifying_signs']);
                $species = sanitizeInput($_POST['species']);
                $race = sanitizeInput($_POST['race']);
                $age = $_POST['age'];
                $gender = sanitizeInput($_POST['gender']);
                $date_of_birth = $_POST['date_of_birth'];
                $weight = $_POST['weight'];
                
                $stmt = $db->prepare("
                    UPDATE animals SET animal_name=?, owner_id=?, identifying_signs=?, species=?, race=?, age=?, gender=?, date_of_birth=?, weight=? 
                    WHERE animal_id=?
                ");
                $stmt->execute([$animal_name, $owner_id, $identifying_signs, $species, $race, $age, $gender, $date_of_birth, $weight, $animal_id]);
                $message = "Animal updated successfully!";
                break;
                
            case 'delete':
                $animal_id = $_POST['animal_id'];
                $stmt = $db->prepare("DELETE FROM animals WHERE animal_id=?");
                $stmt->execute([$animal_id]);
                $message = "Animal deleted successfully!";
                break;
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
        logError('Animal module error: ' . $e->getMessage());
    }
}

// Get data
$db = getDB();
$animals = [];
$owners = [];
$current_animal = null;

try {
    // Get all animals with owner information
    $stmt = $db->query("
        SELECT a.*, ao.owner_name, ao.owner_code
        FROM animals a
        JOIN animal_owners ao ON a.owner_id = ao.owner_id
        ORDER BY a.created_at DESC
    ");
    $animals = $stmt->fetchAll();
    
    // Get all owners for dropdown
    $stmt = $db->query("SELECT * FROM animal_owners ORDER BY owner_name");
    $owners = $stmt->fetchAll();
    
    // Get specific animal if editing
    if ($action == 'edit' && $id) {
        $stmt = $db->prepare("SELECT * FROM animals WHERE animal_id = ?");
        $stmt->execute([$id]);
        $current_animal = $stmt->fetch();
    }
    
} catch (PDOException $e) {
    $error = "Error loading data: " . $e->getMessage();
    logError('Animal data loading error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Animal Management - <?php echo APP_NAME; ?></title>
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
                
                <li class="active">
                    <a href="animal.php">
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
                <h1>Animal Management</h1>
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
                    <h2>Animals</h2>
                    <a href="?action=add" class="btn btn-success">Add New Animal</a>
                </div>
                
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Animal ID</th>
                                <th>Code</th>
                                <th>Animal Name</th>
                                <th>Owner</th>
                                <th>Species</th>
                                <th>Race</th>
                                <th>Age</th>
                                <th>Gender</th>
                                <th>Weight</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($animals as $animal): ?>
                                <tr>
                                    <td><?php echo $animal['animal_id']; ?></td>
                                    <td><?php echo htmlspecialchars($animal['animal_code']); ?></td>
                                    <td><?php echo htmlspecialchars($animal['animal_name']); ?></td>
                                    <td><?php echo htmlspecialchars($animal['owner_name']); ?></td>
                                    <td><?php echo ucfirst($animal['species']); ?></td>
                                    <td><?php echo htmlspecialchars($animal['race']); ?></td>
                                    <td><?php echo $animal['age']; ?></td>
                                    <td><?php echo ucfirst($animal['gender']); ?></td>
                                    <td><?php echo $animal['weight']; ?> kg</td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="?action=view&id=<?php echo $animal['animal_id']; ?>" class="btn btn-primary">View</a>
                                            <a href="?action=edit&id=<?php echo $animal['animal_id']; ?>" class="btn btn-warning">Edit</a>
                                            <button onclick="deleteAnimal(<?php echo $animal['animal_id']; ?>)" class="btn btn-danger">Delete</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
            <?php elseif ($action == 'add'): ?>
                <div class="form-container">
                    <h2>Add New Animal</h2>
                    <form method="POST" action="?action=add">
                        <div class="form-row">
                            <div class="form-col">
                                <label>Animal Name:</label>
                                <input type="text" name="animal_name" required>
                            </div>
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
                        </div>
                        
                        <div class="form-row">
                            <div class="form-col">
                                <label>Species:</label>
                                <select name="species" required>
                                    <option value="">Select Species</option>
                                    <option value="cat">Cat</option>
                                    <option value="dog">Dog</option>
                                    <option value="bird">Bird</option>
                                    <option value="rabbit">Rabbit</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="form-col">
                                <label>Race:</label>
                                <input type="text" name="race">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-col">
                                <label>Age (years):</label>
                                <input type="number" name="age" min="0" max="50">
                            </div>
                            <div class="form-col">
                                <label>Gender:</label>
                                <select name="gender" required>
                                    <option value="">Select Gender</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-col">
                                <label>Date of Birth:</label>
                                <input type="date" name="date_of_birth">
                            </div>
                            <div class="form-col">
                                <label>Weight (kg):</label>
                                <input type="number" name="weight" step="0.1" min="0">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-col">
                                <label>Identifying Signs:</label>
                                <textarea name="identifying_signs" rows="3" placeholder="Distinctive markings, scars, etc."></textarea>
                            </div>
                        </div>
                        
                        <div class="action-buttons">
                            <button type="submit" class="btn btn-success">Add Animal</button>
                            <a href="?action=list" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
                
            <?php elseif ($action == 'edit' && $current_animal): ?>
                <div class="form-container">
                    <h2>Edit Animal</h2>
                    <form method="POST" action="?action=edit">
                        <input type="hidden" name="animal_id" value="<?php echo $current_animal['animal_id']; ?>">
                        
                        <div class="form-row">
                            <div class="form-col">
                                <label>Animal Name:</label>
                                <input type="text" name="animal_name" value="<?php echo htmlspecialchars($current_animal['animal_name']); ?>" required>
                            </div>
                            <div class="form-col">
                                <label>Owner:</label>
                                <select name="owner_id" required>
                                    <option value="">Select Owner</option>
                                    <?php foreach ($owners as $owner): ?>
                                        <option value="<?php echo $owner['owner_id']; ?>" <?php echo $owner['owner_id'] == $current_animal['owner_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($owner['owner_name'] . ' (' . $owner['owner_code'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-col">
                                <label>Species:</label>
                                <select name="species" required>
                                    <option value="">Select Species</option>
                                    <option value="cat" <?php echo $current_animal['species'] == 'cat' ? 'selected' : ''; ?>>Cat</option>
                                    <option value="dog" <?php echo $current_animal['species'] == 'dog' ? 'selected' : ''; ?>>Dog</option>
                                    <option value="bird" <?php echo $current_animal['species'] == 'bird' ? 'selected' : ''; ?>>Bird</option>
                                    <option value="rabbit" <?php echo $current_animal['species'] == 'rabbit' ? 'selected' : ''; ?>>Rabbit</option>
                                    <option value="other" <?php echo $current_animal['species'] == 'other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            <div class="form-col">
                                <label>Race:</label>
                                <input type="text" name="race" value="<?php echo htmlspecialchars($current_animal['race']); ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-col">
                                <label>Age (years):</label>
                                <input type="number" name="age" value="<?php echo $current_animal['age']; ?>" min="0" max="50">
                            </div>
                            <div class="form-col">
                                <label>Gender:</label>
                                <select name="gender" required>
                                    <option value="">Select Gender</option>
                                    <option value="male" <?php echo $current_animal['gender'] == 'male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="female" <?php echo $current_animal['gender'] == 'female' ? 'selected' : ''; ?>>Female</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-col">
                                <label>Date of Birth:</label>
                                <input type="date" name="date_of_birth" value="<?php echo $current_animal['date_of_birth']; ?>">
                            </div>
                            <div class="form-col">
                                <label>Weight (kg):</label>
                                <input type="number" name="weight" value="<?php echo $current_animal['weight']; ?>" step="0.1" min="0">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-col">
                                <label>Identifying Signs:</label>
                                <textarea name="identifying_signs" rows="3"><?php echo htmlspecialchars($current_animal['identifying_signs']); ?></textarea>
                            </div>
                        </div>
                        
                        <div class="action-buttons">
                            <button type="submit" class="btn btn-success">Update Animal</button>
                            <a href="?action=list" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
                
            <?php elseif ($action == 'view' && $id): ?>
                <?php
                // Get detailed animal information
                $stmt = $db->prepare("
                    SELECT a.*, ao.owner_name, ao.owner_code, ao.telephone_number, ao.email, ao.address
                    FROM animals a
                    JOIN animal_owners ao ON a.owner_id = ao.owner_id
                    WHERE a.animal_id = ?
                ");
                $stmt->execute([$id]);
                $animal_detail = $stmt->fetch();
                
                // Get examination history
                $stmt = $db->prepare("
                    SELECT e.*, d.doctor_name
                    FROM examinations e
                    JOIN doctors d ON e.doctor_id = d.doctor_id
                    WHERE e.animal_id = ?
                    ORDER BY e.examination_date DESC
                ");
                $stmt->execute([$id]);
                $examinations = $stmt->fetchAll();
                ?>
                
                <div class="animal-detail">
                    <div class="detail-header">
                        <h2>Animal Details</h2>
                        <div class="action-buttons">
                            <a href="?action=edit&id=<?php echo $id; ?>" class="btn btn-warning">Edit</a>
                            <a href="?action=list" class="btn btn-secondary">Back to List</a>
                        </div>
                    </div>
                    
                    <?php if ($animal_detail): ?>
                        <div class="detail-grid">
                            <div class="detail-section">
                                <h3>Animal Information</h3>
                                <div class="detail-row">
                                    <label>Animal ID:</label>
                                    <span><?php echo $animal_detail['animal_id']; ?></span>
                                </div>
                                <div class="detail-row">
                                    <label>Animal Code:</label>
                                    <span><?php echo htmlspecialchars($animal_detail['animal_code']); ?></span>
                                </div>
                                <div class="detail-row">
                                    <label>Animal Name:</label>
                                    <span><?php echo htmlspecialchars($animal_detail['animal_name']); ?></span>
                                </div>
                                <div class="detail-row">
                                    <label>Species:</label>
                                    <span><?php echo ucfirst($animal_detail['species']); ?></span>
                                </div>
                                <div class="detail-row">
                                    <label>Race:</label>
                                    <span><?php echo htmlspecialchars($animal_detail['race']); ?></span>
                                </div>
                                <div class="detail-row">
                                    <label>Age:</label>
                                    <span><?php echo $animal_detail['age']; ?> years</span>
                                </div>
                                <div class="detail-row">
                                    <label>Gender:</label>
                                    <span><?php echo ucfirst($animal_detail['gender']); ?></span>
                                </div>
                                <div class="detail-row">
                                    <label>Date of Birth:</label>
                                    <span><?php echo formatDate($animal_detail['date_of_birth']); ?></span>
                                </div>
                                <div class="detail-row">
                                    <label>Weight:</label>
                                    <span><?php echo $animal_detail['weight']; ?> kg</span>
                                </div>
                                <div class="detail-row">
                                    <label>Identifying Signs:</label>
                                    <span><?php echo htmlspecialchars($animal_detail['identifying_signs']); ?></span>
                                </div>
                            </div>
                            
                            <div class="detail-section">
                                <h3>Owner Information</h3>
                                <div class="detail-row">
                                    <label>Owner Name:</label>
                                    <span><?php echo htmlspecialchars($animal_detail['owner_name']); ?></span>
                                </div>
                                <div class="detail-row">
                                    <label>Owner Code:</label>
                                    <span><?php echo htmlspecialchars($animal_detail['owner_code']); ?></span>
                                </div>
                                <div class="detail-row">
                                    <label>Phone:</label>
                                    <span><?php echo htmlspecialchars($animal_detail['telephone_number']); ?></span>
                                </div>
                                <div class="detail-row">
                                    <label>Email:</label>
                                    <span><?php echo htmlspecialchars($animal_detail['email']); ?></span>
                                </div>
                                <div class="detail-row">
                                    <label>Address:</label>
                                    <span><?php echo htmlspecialchars($animal_detail['address']); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Examination History -->
                        <div class="examination-history">
                            <h3>Examination History</h3>
                            <?php if (empty($examinations)): ?>
                                <p class="no-data">No examinations found for this animal.</p>
                            <?php else: ?>
                                <div class="table-container">
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Doctor</th>
                                                <th>Diagnosis</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($examinations as $exam): ?>
                                                <tr>
                                                    <td><?php echo formatDate($exam['examination_date']); ?></td>
                                                    <td><?php echo htmlspecialchars($exam['doctor_name']); ?></td>
                                                    <td><?php echo htmlspecialchars(substr($exam['diagnosis'], 0, 50)) . '...'; ?></td>
                                                    <td>
                                                        <span class="status status-<?php echo $exam['status']; ?>">
                                                            <?php echo ucfirst($exam['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <a href="../examination/examination.php?action=view&id=<?php echo $exam['examination_id']; ?>" class="btn btn-primary">View</a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <p class="error">Animal not found.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
    
    <script>
        function deleteAnimal(animalId) {
            if (confirm('Are you sure you want to delete this animal? This will also delete all related examinations and medical records.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '?action=delete';
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'animal_id';
                input.value = animalId;
                
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
        
        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .detail-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .detail-section h3 {
            margin-bottom: 20px;
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        
        .detail-row {
            display: flex;
            margin-bottom: 15px;
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
        
        .examination-history {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .examination-history h3 {
            margin-bottom: 20px;
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
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

