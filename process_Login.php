<?php
session_start();
// Generate token if missing
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

//  validation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        // Token check failed - show error but don't die()
        $_SESSION['error'] = "Session expired. Please refresh the page.";
        header("Location: login.php");
        exit();
    }
}

// Include the database connection
require_once('Models/Database.php');

// Fetch user input from form
$username = isset($_POST['username']) ? $_POST['username'] : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

// Validate human check
if (!isset($_POST['human_check'])) {
    $_SESSION['error'] = "Please confirm that you are a human.";
    echo '<script type="text/javascript">window.location.href = "login.php";</script>';
    exit();
}

// Create a database connection
$db = new Database();
$conn = $db->connect();

// Check if the user exists in the database
$query = "SELECT * FROM ecoUser WHERE username = :username LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->bindValue(':username', $username, PDO::PARAM_STR);
$stmt->execute();

$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if the user exists and if the password is correct using password_verify
if ($user && password_verify($password, $user['password'])) {
    // Password is correct, set session variables
    $_SESSION['userId'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['userType'] = $user['userType']; // Save the userType from the database

    // Use JavaScript for redirection
    echo '<script type="text/javascript">window.location.href = "login_dashboard.php";</script>';
    exit();
} else {
    // Invalid login credentials, set error message and redirect back to login page
    $_SESSION['error'] = "Invalid username or password.";
    echo '<script type="text/javascript">window.location.href = "login.php";</script>';
    exit();
}
?>
