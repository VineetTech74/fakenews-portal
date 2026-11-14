<?php
header('Content-Type: application/json');
require_once 'db_config.php';

$userId = $_SESSION['user_id'] ?? null;
$history = [];

if ($userId) {
    // Fetch logged-in user's personal search history
    $stmt = $conn->prepare("SELECT id, headline, trust_score, status, checked_at FROM search_history WHERE user_id = ? ORDER BY id DESC LIMIT 50");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $history[] = [
            "id"          => $row['id'],
            "headline"    => $row['headline'],
            "trust_score" => $row['trust_score'] . "%",
            "status"      => $row['status'],
            "date"        => date('Y-m-d H:i', strtotime($row['checked_at']))
        ];
    }
    $stmt->close();
} else {
    // For guest visitors, return recent public verification checks
    $result = $conn->query("SELECT id, headline, trust_score, status, checked_at FROM search_history ORDER BY id DESC LIMIT 20");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $history[] = [
                "id"          => $row['id'],
                "headline"    => $row['headline'],
                "trust_score" => $row['trust_score'] . "%",
                "status"      => $row['status'],
                "date"        => date('Y-m-d H:i', strtotime($row['checked_at']))
            ];
        }
    }
}

echo json_encode([
    "status"  => "success",
    "history" => $history
]);
?>
