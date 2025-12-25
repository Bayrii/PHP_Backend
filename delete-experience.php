<?php
session_start();
require_once 'config/database.php';
require_once 'includes/anonymize.php';
require_once 'includes/auth.php';

// Require login
requireLogin();
$userId = getCurrentUserId();

// Check if ID is provided (support both direct ID and anonymized code)
if (isset($_GET['id']) && !empty($_GET['id'])) {
    // Direct ID approach
    $id = intval($_GET['id']);
} elseif (isset($_GET['code']) && !empty($_GET['code'])) {
    // Anonymized code approach (fallback)
    $id = deanonymizeCode($_GET['code']);
    if ($id === null) {
        $_SESSION['message'] = "Invalid or expired experience code";
        $_SESSION['message_type'] = 'error';
        header('Location: view-experiences.php');
        exit;
    }
} else {
    $_SESSION['message'] = "No experience ID provided";
    $_SESSION['message_type'] = 'error';
    header('Location: view-experiences.php');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Delete the experience with user_id verification for security
try {
    $stmt = $conn->prepare("DELETE FROM driving_experiences WHERE id = :id AND user_id = :user_id");
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        if ($stmt->rowCount() > 0) {
            $_SESSION['message'] = "Experience deleted successfully";
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = "Experience not found or you don't have permission to delete it";
            $_SESSION['message_type'] = 'error';
        }
    }
} catch (PDOException $e) {
    $_SESSION['message'] = "Error deleting experience: " . $e->getMessage();
    $_SESSION['message_type'] = 'error';
}

header('Location: view-experiences.php');
exit;
