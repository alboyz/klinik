<?php
require_once 'config/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error_message = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];

    if (!empty($username) && !empty($password)) {
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT user_id, username, password, full_name, role FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['login_time'] = time();

                header('Location: dashboard.php');
                exit();
            } else {
                $error_message = 'Invalid username or password.';
            }
        } catch (PDOException $e) {
            $error_message = 'Database error. Please try again.';
            logError('Login error: ' . $e->getMessage());
        }
    } else {
        $error_message = 'Please enter both username and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="login-page">
    <div class="login-container">
        <div class="login-header">
            <h1><?php echo APP_NAME; ?></h1>
            <p class="current-date">Today: <?php echo date('F j, Y'); ?></p>
        </div>

        <div class="main-content">
            <!-- Pet Images Section -->
            <div class="pet-gallery">
                <div class="pet-image">
                    <img src="assets/images/main_pets1.jpg" alt="Cats and Dogs at Clinic" class="pet-img">
                </div>
                <div class="pet-image">
                    <img src="assets/images/main_pets2.jpg" alt="Veterinary Care" class="pet-img">
                </div>
                <div class="pet-image">
                    <img src="assets/images/main_pets3.jpg" alt="Pet Health Check" class="pet-img">
                </div>
            </div>

            <!-- Login Form -->
            <div class="login-form-container">
                <div class="login-form">
                    <h2>Login to System</h2>

                    <?php if (!empty($error_message)): ?>
                        <div class="error-message">
                            <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="username">Username:</label>
                            <input type="text" id="username" name="username" required
                                value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="password">Password:</label>
                            <input type="password" id="password" name="password" required>
                        </div>

                        <button type="submit" class="login-btn">Login</button>
                    </form>

                    <div class="login-info">
                        <p><strong>Default Login:</strong></p>
                        <p>Username: admin</p>
                        <p>Password: password</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer">
            <p>&copy; <?php echo date('Y') . ' ' . APP_NAME . ' ' . APP_VERSION; ?></p>

        </div>
    </div>
</body>

</html>