<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';

// If already logged in, redirect to index
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($username) || empty($password) || empty($confirm_password)) {
        $error = 'All fields are required';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $result = registerUser($conn, $username, $password);
        
        if ($result['success']) {
            $success = $result['message'];
            // Auto-login after registration
            loginUser($result['user_id'], $username);
            header('Location: index.php');
            exit;
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Driving Tracker</title>
    <link rel="stylesheet" href="css/style.css?v=9.0">
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo">
                <h1>Driving Tracker</h1>
            </div>
        </div>
    </header>

    <main>
        <section class="container" style="max-width: 500px; margin-top: 4rem;">
            <div style="text-align: center; margin-bottom: 2rem;">
                <h2 style="font-size: 2rem; margin-bottom: 0.5rem;">🚗 Create Account</h2>
                <p style="color: var(--text-light); font-size: 1rem;">Join us to start tracking your driving experiences</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="register.php" class="form-card">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        required 
                        autofocus
                        minlength="3"
                        maxlength="50"
                        value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                        placeholder="Choose a username"
                    >
                    <small style="color: var(--text-light); font-size: 0.85rem;">3-50 characters</small>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required
                        minlength="6"
                        placeholder="Create a password"
                    >
                    <small style="color: var(--text-light); font-size: 0.85rem;">Minimum 6 characters</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        required
                        minlength="6"
                        placeholder="Re-enter your password"
                    >
                </div>
                
                <button type="submit" class="btn btn-success btn-block" style="margin-top: 1.5rem;">
                    ✓ Create Account
                </button>
            </form>
            
            <div style="text-align: center; margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--border-color);">
                <p style="color: var(--text-light); margin-bottom: 0.5rem;">Already have an account?</p>
                <a href="login.php" class="btn btn-primary" style="display: inline-block;">
                    Sign In
                </a>
            </div>
        </section>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Driving Tracker</p>
    </footer>
</body>
</html>
</body>
</html>
