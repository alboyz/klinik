<?php
require_once 'config/config.php';
requireLogin();

// Get dashboard statistics
try {
    $db = getDB();

    // Count doctors
    $stmt = $db->query("SELECT COUNT(*) as count FROM doctors");
    $doctors_count = $stmt->fetch()['count'];

    // Count animals
    $stmt = $db->query("SELECT COUNT(*) as count FROM animals");
    $animals_count = $stmt->fetch()['count'];

    // Count animal owners
    $stmt = $db->query("SELECT COUNT(*) as count FROM animal_owners");
    $owners_count = $stmt->fetch()['count'];

    // Count medicines
    $stmt = $db->query("SELECT COUNT(*) as count FROM medicines");
    $medicines_count = $stmt->fetch()['count'];

    // Count examinations
    $stmt = $db->query("SELECT COUNT(*) as count FROM examinations");
    $examinations_count = $stmt->fetch()['count'];

    // Count payments
    $stmt = $db->query("SELECT COUNT(*) as count FROM payments");
    $payments_count = $stmt->fetch()['count'];

    // Get recent examinations
    $stmt = $db->query("
        SELECT e.examination_id, a.animal_name, ao.owner_name, d.doctor_name, e.examination_date, e.status
        FROM examinations e
        JOIN animals a ON e.animal_id = a.animal_id
        JOIN animal_owners ao ON a.owner_id = ao.owner_id
        JOIN doctors d ON e.doctor_id = d.doctor_id
        ORDER BY e.examination_date DESC
        LIMIT 5
    ");
    $recent_examinations = $stmt->fetchAll();
} catch (PDOException $e) {
    logError('Dashboard error: ' . $e->getMessage());
    $doctors_count = $animals_count = $owners_count = $medicines_count = $examinations_count = $payments_count = 0;
    $recent_examinations = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
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
                <li class="active">
                    <a href="dashboard.php">
                        <span class="icon">📊</span>
                        Dashboard
                    </a>
                </li>

                <?php if (hasPermission('admin')): ?>
                    <li>
                        <a href="modules/admin/manage_admin.php">
                            <span class="icon">👥</span>
                            Manage Admin
                        </a>
                    </li>
                <?php endif; ?>

                <li>
                    <a href="modules/doctor/doctor.php">
                        <span class="icon">👨‍⚕️</span>
                        Doctor
                    </a>
                </li>

                <li>
                    <a href="modules/animal/animal.php">
                        <span class="icon">🐈</span>
                        Animal
                    </a>
                </li>

                <li>
                    <a href="modules/owner/animal_owner.php">
                        <span class="icon">👤</span>
                        Animal Owner
                    </a>
                </li>

                <li>
                    <a href="modules/examination/examination.php">
                        <span class="icon">🔍</span>
                        Examination
                    </a>
                </li>

                <li>
                    <a href="modules/medicine/medicine.php">
                        <span class="icon">💊</span>
                        Medicine
                    </a>
                </li>

                <li>
                    <a href="modules/payment/payment.php">
                        <span class="icon">💰</span>
                        Payment
                    </a>
                </li>

                <li class="logout">
                    <a href="logout.php">
                        <span class="icon">🚪</span>
                        Logout
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <div class="content-header">
                <h1>Dashboard</h1>
                <p class="current-date">Today: <?php echo date('F j, Y'); ?></p>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">👨‍⚕️</div>
                    <div class="stat-info">
                        <h3><?php echo $doctors_count; ?></h3>
                        <p>Doctors</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">🐕</div>
                    <div class="stat-info">
                        <h3><?php echo $animals_count; ?></h3>
                        <p>Animals</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">👤</div>
                    <div class="stat-info">
                        <h3><?php echo $owners_count; ?></h3>
                        <p>Animal Owners</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">💊</div>
                    <div class="stat-info">
                        <h3><?php echo $medicines_count; ?></h3>
                        <p>Medicines</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">🔍</div>
                    <div class="stat-info">
                        <h3><?php echo $examinations_count; ?></h3>
                        <p>Examinations</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-info">
                        <h3><?php echo $payments_count; ?></h3>
                        <p>Payments</p>
                    </div>
                </div>
            </div>

            <!-- Recent Examinations -->
            <div class="recent-section">
                <h2>Recent Examinations</h2>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Exam ID</th>
                                <th>Animal</th>
                                <th>Owner</th>
                                <th>Doctor</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recent_examinations)): ?>
                                <tr>
                                    <td colspan="6" class="no-data">No examinations found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recent_examinations as $exam): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($exam['examination_id']); ?></td>
                                        <td><?php echo htmlspecialchars($exam['animal_name']); ?></td>
                                        <td><?php echo htmlspecialchars($exam['owner_name']); ?></td>
                                        <td><?php echo htmlspecialchars($exam['doctor_name']); ?></td>
                                        <td><?php echo formatDate($exam['examination_date']); ?></td>
                                        <td>
                                            <span class="status status-<?php echo $exam['status']; ?>">
                                                <?php echo ucfirst($exam['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>

</html>