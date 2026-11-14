<?php
header('Content-Type: application/json');
require_once 'db_config.php';

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT id, name, email, role, profile_pic FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['profile_pic'] = $user['profile_pic'];

        echo json_encode([
            'loggedIn'    => true,
            'user_id'     => $user['id'],
            'user_name'   => $user['name'],
            'email'       => $user['email'],
            'role'        => $user['role'],
            'profile_pic' => $user['profile_pic']
        ]);
        $stmt->close();
        exit;
    }
    $stmt->close();
}

echo json_encode([
    'loggedIn' => false
]);
?>