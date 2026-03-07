<?php
session_start();
require_once __DIR__ . '/db.php';

// Only handle POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../register.php');
    exit;
}

$name     = trim($_POST['name'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm  = $_POST['confirm_password'] ?? '';
$role     = $_POST['role'] ?? 'Student';

// Basic validation
if (empty($name) || empty($email) || empty($password) || empty($confirm)) {
    header('Location: ../register.php?error=empty');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ../register.php?error=email');
    exit;
}

if (strlen($password) < 6) {
    header('Location: ../register.php?error=short');
    exit;
}

if ($password !== $confirm) {
    header('Location: ../register.php?error=match');
    exit;
}

$conn = getDB();

// Check if email exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->close();
    header('Location: ../register.php?error=exists');
    exit;
}
$stmt->close();

// Insert user
$allowed = ['Student','Researcher','Educator'];
if (!in_array($role, $allowed)) $role = 'Student';

$hashed = password_hash($password, PASSWORD_BCRYPT);
$status = 'Active';

$stmt = $conn->prepare("INSERT INTO users (name, email, password, role, status, created_at) VALUES (?,?,?,?,?,NOW())");
$stmt->bind_param('sssss', $name, $email, $hashed, $role, $status);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    header('Location: ../index.php?registered=1');
} else {
    $stmt->close();
    $conn->close();
    header('Location: ../register.php?error=fail');
}
exit;
