<?php
header('Content-Type: application/json');
require_once 'db_config.php';

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($name) || empty($email) || empty($password)) {
    echo json_encode(["status" => "error", "message" => "All registration fields are required."]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["status" => "error", "message" => "Please provide a valid email address."]);
    exit;
}

if (strlen($password) < 4) {
    echo json_encode(["status" => "error", "message" => "Password must be at least 4 characters long."]);
    exit;
}

// Check if email already exists
$checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$checkStmt->bind_param("s", $email);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows > 0) {
    echo json_encode(["status" => "error", "message" => "This email address is already registered. Please sign in."]);
    $checkStmt->close();
    exit;
}
$checkStmt->close();

$hashedPassword = password_hash($password, PASSWORD_BCRYPT);
$defaultPic = 'default-avatar.png';
$defaultRole = 'Standard User';

$stmt = $conn->prepare("INSERT INTO users (name, email, password, role, profile_pic) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssss", $name, $email, $hashedPassword, $defaultRole, $defaultPic);

if ($stmt->execute()) {
    $userId = $stmt->insert_id;
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_name'] = $name;
    $_SESSION['user_email'] = $email;
    $_SESSION['role'] = $defaultRole;
    $_SESSION['profile_pic'] = $defaultPic;

    echo json_encode([
        "status" => "success",
        "message" => "Account created successfully! Welcome, " . $name . ".",
        "user" => [
            "id" => $userId,
            "name" => $name,
            "email" => $email,
            "role" => $defaultRole,
            "profile_pic" => $defaultPic
        ]
    ]);
} else {
    echo json_encode(["status" => "error", "message" => "Database registration failed: " . $conn->error]);
}

$stmt->close();
?>
