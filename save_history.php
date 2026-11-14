<?php
header('Content-Type: application/json');
require_once 'db_config.php';

$headline = trim($_POST['headline'] ?? '');
$rawScore = $_POST['score'] ?? 0;
$status   = trim($_POST['status'] ?? 'Checked');
$userId   = $_SESSION['user_id'] ?? NULL;

if (empty($headline)) {
    echo json_encode(['status' => 'error', 'message' => 'Headline required']);
    exit;
}

$score = (int)preg_replace('/[^0-9]/', '', (string)$rawScore);

$stmt = $conn->prepare("INSERT INTO search_history (user_id, headline, trust_score, status, checked_at) VALUES (?, ?, ?, ?, NOW())");
if ($stmt) {
    $stmt->bind_param("isis", $userId, $headline, $score, $status);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'id' => $stmt->insert_id]);
    } else {
        echo json_encode(['status' => 'error', 'message' => $stmt->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => $conn->error]);
}
?>