<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/db.php';

$userId = $_SESSION['user_id'];
$fullName = $_POST['full_name'] ?? '';
$email = $_POST['email'] ?? '';
$role = $_POST['role'] ?? '';
$bio = $_POST['bio'] ?? '';
$newPassword = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

// Basic validation
if (empty($fullName) || empty($email)) {
    // Ideally we would return an error message to the user, for now simple redirect
    header('Location: ../dashboard.php?page=edit_profile&error=empty_fields');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../dashboard.php?page=profile');
    exit;
}

$conn = getDB();
$userId = $_SESSION['user_id'];
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
        $query = "UPDATE users SET name = ?, email = ?, role = ?, bio = ?, password = ?, profile_image = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ssssssi', $fullName, $email, $role, $bio, $hashedPassword, $profileImage, $userId);
        $stmt->execute();
        $stmt->close();
    } else {
        $query = "UPDATE users SET name = ?, email = ?, role = ?, bio = ?, profile_image = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('sssssi', $fullName, $email, $role, $bio, $profileImage, $userId);
        $stmt->execute();
        $stmt->close();
    }

    // Update Session variables
    $_SESSION['user_name'] = $fullName;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_role'] = $role;
    $_SESSION['user_bio'] = $bio;
    $_SESSION['profile_image'] = $profileImage;

    $conn->close();
    header('Location: ../dashboard.php?page=profile&success=updated');
    exit;

} catch (Exception $e) {
    if (isset($conn)) $conn->close();
    header('Location: ../dashboard.php?page=edit_profile&error=db_error');
    exit;
}

