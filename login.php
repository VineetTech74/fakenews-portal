<?php
header('Content-Type: application/json');
require_once 'db_config.php';

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(["status" => "error", "message" => "Please enter both email and password."]);
    exit;
}

$stmt = $conn->prepare("SELECT id, name, email, password, role, profile_pic FROM users WHERE email = ? OR name = ?");
$stmt->bind_param("ss", $email, $email);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    if (password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['role'] = $user['role'] ?? 'Standard User';
        $_SESSION['profile_pic'] = $user['profile_pic'] ?? 'default-avatar.png';

        echo json_encode([
            "status" => "success",
            "message" => "Welcome back, " . $user['name'] . "!",
            "user" => [
                "id" => $user['id'],
                "name" => $user['name'],
                "email" => $user['email'],
                "role" => $user['role'] ?? 'Standard User',
                "profile_pic" => $user['profile_pic'] ?? 'default-avatar.png'
            ]
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Incorrect password. Please try again."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "No account found with this email."]);
}

$stmt->close();
?>
