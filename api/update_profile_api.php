<?php
session_start();
require_once __DIR__ . '/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if (isset($_SESSION['user_id'])) {
        header('Location: ../dashboard.php?page=profile');
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    }
    exit;
}

// Detect if this is an Android (multipart with 'id' field) or web (session-based) request
$isAndroid = isset($_POST['id']) && !empty($_POST['id']);

if ($isAndroid) {
    $userId = (int)$_POST['id'];
} elseif (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
} else {
    header('Location: ../index.php');
    exit;
}

// Accept 'name' (Android) or 'full_name' (web)
$fullName = trim($_POST['name'] ?? $_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$bio = $_POST['bio'] ?? '';
$newPassword = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

// Basic validation
if (empty($fullName) || empty($email)) {
    if ($isAndroid) {
        echo json_encode(['status' => 'error', 'message' => 'Name and email are required']);
        exit;
    }
    header('Location: ../dashboard.php?page=edit_profile&error=empty_fields');
    exit;
}

$conn = getDB();
$profileImage = $_SESSION['profile_image'] ?? null;

// File Upload handling
if (!empty($_FILES['profile_image']['name'])) {
    $targetDir = "../uploads/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    $fileExtension = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png'];
    
    if (in_array($fileExtension, $allowedExtensions)) {
        $newFileName = "profile_" . $userId . "_" . time() . "." . $fileExtension;
        $targetPath = $targetDir . $newFileName;
        
        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $targetPath)) {
            // Remove old profile image if it exists
            if ($profileImage && file_exists("../" . $profileImage) && strpos($profileImage, 'uploads/') === 0) {
                unlink("../" . $profileImage);
            }
            $profileImage = "uploads/" . $newFileName;
        }
    }
}

try {
    // Password Update Logic
    if (!empty($newPassword) && $newPassword === $confirmPassword) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $query = "UPDATE users SET name = ?, email = ?, bio = ?, password = ?, profile_image = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('sssssi', $fullName, $email, $bio, $hashedPassword, $profileImage, $userId);
        $stmt->execute();
        $stmt->close();
    } else {
        $query = "UPDATE users SET name = ?, email = ?, bio = ?, profile_image = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ssssi', $fullName, $email, $bio, $profileImage, $userId);
        $stmt->execute();
        $stmt->close();
    }

    // Update Session variables (only for web)
    if (!$isAndroid) {
        $_SESSION['user_name'] = $fullName;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_bio'] = $bio;
        $_SESSION['profile_image'] = $profileImage;
    }

    $conn->close();

    if ($isAndroid) {
        echo json_encode(['status' => 'success', 'message' => 'Profile updated successfully']);
    } else {
        header('Location: ../dashboard.php?page=profile&success=updated');
    }
    exit;

} catch (Exception $e) {
    if (isset($conn)) $conn->close();
    if ($isAndroid) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    } else {
        header('Location: ../dashboard.php?page=edit_profile&error=db_error');
    }
    exit;
}

