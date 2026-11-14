<?php
header('Content-Type: application/json');
require_once 'db_config.php';

$title    = trim($_POST['title'] ?? '');
$platform = trim($_POST['platform'] ?? 'Web / Social Media');
$reason   = trim($_POST['reason'] ?? '');
$userId   = $_SESSION['user_id'] ?? NULL;

if (empty($title)) {
    echo json_encode(['status' => 'error', 'message' => 'Headline is required to submit a report.']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO reported_news (user_id, headline, platform, reason, status, date_reported) VALUES (?, ?, ?, ?, 'Pending', NOW())");

if ($stmt) {
    $stmt->bind_param("isss", $userId, $title, $platform, $reason);
    if ($stmt->execute()) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Report submitted successfully! Our moderators and admin will review it.'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Database error: ' . $stmt->error
        ]);
    }
    $stmt->close();
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to prepare database statement: ' . $conn->error
    ]);
}
?>