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

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $user = verifyCredentials($conn, $username, $password);
        
        if ($user) {
            loginUser($user['id'], $user['username']);
            
            // Redirect to original page or index
            $redirect = $_SESSION['redirect_after_login'] ?? 'index.php';
            unset($_SESSION['redirect_after_login']);
            header('Location: ' . $redirect);
            exit;
        } else {
            $error = 'Invalid username or password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Driving Tracker</title>
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
                <h2 style="font-size: 2rem; margin-bottom: 0.5rem;">🚗 Welcome Back</h2>
                <p style="color: var(--text-light); font-size: 1rem;">Sign in to track your driving experiences</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="login.php" class="form-card">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        required 
                        autofocus
                        value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                        placeholder="Enter your username"
                    >
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required
                        placeholder="Enter your password"
                    >
                </div>
                
                <button type="submit" class="btn btn-success btn-block" style="margin-top: 1.5rem;">
                    🔓 Sign In
                </button>
            </form>
            
            <div style="text-align: center; margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--border-color);">
                <p style="color: var(--text-light); margin-bottom: 0.5rem;">Don't have an account?</p>
                <a href="register.php" class="btn btn-primary" style="display: inline-block;">
                    Create Account
                </a>
            </div>
        </section>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Driving Tracker</p>
    </footer>
</body>
</html>
