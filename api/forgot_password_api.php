<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../forgot_password.php');
    exit;
}

$action = $_POST['action'] ?? '';
$email = $_POST['email'] ?? '';

if ($action === 'verify_email') {
    if (empty($email)) {
        header('Location: ../forgot_password.php');
        exit;
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Email exists, proceed to step 2
        header('Location: ../forgot_password.php?email=' . urlencode($email));
    } else {
        // Email not found
        header('Location: ../forgot_password.php?error=not_found');
    }
    $stmt->close();
    $db->close();
    exit;
}

if ($action === 'reset_password') {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($email) || empty($password) || empty($confirm_password)) {
        header('Location: ../forgot_password.php');
        exit;
    }

    if ($password !== $confirm_password) {
        header('Location: ../forgot_password.php?email=' . urlencode($email) . '&error=mismatch');
        exit;
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $db = getDB();
    $stmt = $db->prepare("UPDATE users SET password = ? WHERE email = ?");
    $stmt->bind_param("ss", $hashed_password, $email);
    
    if ($stmt->execute()) {
        header('Location: ../forgot_password.php?success=1');
    } else {
        header('Location: ../forgot_password.php?email=' . urlencode($email) . '&error=failed');
    }
    
    $stmt->close();
    $db->close();
    exit;
}

header('Location: ../forgot_password.php');
exit;
