<?php
require_once 'db_config.php';

// Handle Admin Actions
$msg = "";
$msgType = "info";

if (isset($_GET['action']) && isset($_GET['id'])) {
    $action_id = (int)$_GET['id'];
    $action    = $_GET['action'];

    if ($action === 'verify_real') {
        $stmt = $conn->prepare("UPDATE reported_news SET status = 'Verified Real' WHERE id = ?");
        $stmt->bind_param("i", $action_id);
        $stmt->execute();
        $stmt->close();
        $msg = "Report #$action_id marked as VERIFIED REAL news.";
        $msgType = "success";
    } elseif ($action === 'mark_fake') {
        $stmt = $conn->prepare("UPDATE reported_news SET status = 'Fake / Scam' WHERE id = ?");
        $stmt->bind_param("i", $action_id);
        $stmt->execute();
        $stmt->close();
        $msg = "Report #$action_id marked as CONFIRMED FAKE / SCAM.";
        $msgType = "danger";
    } elseif ($action === 'mark_pending') {
        $stmt = $conn->prepare("UPDATE reported_news SET status = 'Pending' WHERE id = ?");
        $stmt->bind_param("i", $action_id);
        $stmt->execute();
        $stmt->close();
        $msg = "Report #$action_id status reset to Pending.";
        $msgType = "warning";
    } elseif ($action === 'delete_report') {
        $stmt = $conn->prepare("DELETE FROM reported_news WHERE id = ?");
        $stmt->bind_param("i", $action_id);
        $stmt->execute();
        $stmt->close();
        $msg = "Report #$action_id deleted successfully.";
        $msgType = "secondary";
    } elseif ($action === 'delete_user') {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $action_id);
        $stmt->execute();
        $stmt->close();
        $msg = "User #$action_id deleted successfully.";
        $msgType = "danger";
    } elseif ($action === 'delete_history') {
        $stmt = $conn->prepare("DELETE FROM search_history WHERE id = ?");
        $stmt->bind_param("i", $action_id);
        $stmt->execute();
        $stmt->close();
        $msg = "Search history record #$action_id deleted.";
        $msgType = "secondary";
    }

    $currentView = $_GET['view'] ?? 'reports';
    header("Location: admin.php?view=$currentView&status_msg=" . urlencode($msg) . "&msg_type=" . urlencode($msgType));
    exit;
}

// Active Tab Filter (default: 'reports')
$view = $_GET['view'] ?? 'reports';

// LIVE ACCURATE DATABASE COUNTS
$pendingRes = $conn->query("SELECT COUNT(*) AS total FROM reported_news WHERE status = 'Pending' OR status IS NULL OR status = ''");
$pendingCount = $pendingRes ? (int)$pendingRes->fetch_assoc()['total'] : 0;

$verifiedRes = $conn->query("SELECT COUNT(*) AS total FROM reported_news WHERE status = 'Verified Real'");
$verifiedCount = $verifiedRes ? (int)$verifiedRes->fetch_assoc()['total'] : 0;

$fakeRes = $conn->query("SELECT COUNT(*) AS total FROM reported_news WHERE status = 'Fake / Scam'");
$fakeCount = $fakeRes ? (int)$fakeRes->fetch_assoc()['total'] : 0;

$userRes = $conn->query("SELECT COUNT(*) AS total FROM users");
$userCount = $userRes ? (int)$userRes->fetch_assoc()['total'] : 0;

$historyRes = $conn->query("SELECT COUNT(*) AS total FROM search_history");
$historyCount = $historyRes ? (int)$historyRes->fetch_assoc()['total'] : 0;

// Fetch Data for Active View
if ($view === 'users') {
    $dataRes = $conn->query("SELECT * FROM users ORDER BY id DESC");
} elseif ($view === 'verified') {
    $dataRes = $conn->query("SELECT r.*, u.name as user_name FROM reported_news r LEFT JOIN users u ON r.user_id = u.id WHERE r.status = 'Verified Real' ORDER BY r.id DESC");
} elseif ($view === 'fake_cases') {
    $dataRes = $conn->query("SELECT r.*, u.name as user_name FROM reported_news r LEFT JOIN users u ON r.user_id = u.id WHERE r.status = 'Fake / Scam' ORDER BY r.id DESC");
} elseif ($view === 'history') {
    $dataRes = $conn->query("SELECT s.*, u.name as user_name FROM search_history s LEFT JOIN users u ON s.user_id = u.id ORDER BY s.id DESC LIMIT 100");
} else {
    // view === 'reports' (Pending reports)
    $dataRes = $conn->query("SELECT r.*, u.name as user_name FROM reported_news r LEFT JOIN users u ON r.user_id = u.id WHERE r.status = 'Pending' OR r.status IS NULL OR r.status = '' ORDER BY r.id DESC");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Fake News Monitoring Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .admin-nav {
            background-color: #0f172a;
        }
        .stat-card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            border: 2px solid transparent;
            transition: all 0.2s ease-in-out;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.12);
        }
        .stat-card.active-card {
            border-color: #2563eb;
            background-color: #f1f5f9;
        }
        .table th {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body class="bg-light">

    <!-- Top Admin Navigation -->
    <nav class="navbar navbar-dark admin-nav px-4 py-3 shadow">
        <div class="container-fluid d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <span class="navbar-brand fw-bold fs-5 mb-0">🛡️ VeriTruth Admin Console</span>
                <span class="badge bg-success">Live Database Connected</span>
            </div>
            <div class="d-flex gap-2">
                <a href="index.html" class="btn btn-outline-light btn-sm">🌐 Main Portal Home</a>
                <a href="profile.php" class="btn btn-outline-info btn-sm">👤 My Profile</a>
                <a href="admin.php?view=<?php echo htmlspecialchars($view); ?>" class="btn btn-secondary btn-sm">🔄 Refresh</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4 my-4">
        
        <?php if (isset($_GET['status_msg'])): ?>
            <?php $alertType = $_GET['msg_type'] ?? 'info'; ?>
            <div class="alert alert-<?php echo htmlspecialchars($alertType); ?> alert-dismissible fade show" role="alert">
                <strong>System Notice:</strong> <?php echo htmlspecialchars($_GET['status_msg']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Stat Overview Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3 col-sm-6">
                <a href="admin.php?view=reports" class="stat-card p-3 text-center <?php echo $view === 'reports' ? 'active-card' : ''; ?>">
                    <h6 class="text-muted text-uppercase small mb-1">Pending Reports</h6>
                    <h2 class="fw-bold text-warning mb-0"><?php echo $pendingCount; ?></h2>
                    <small class="text-muted">Awaiting Admin Review</small>
                </a>
            </div>
            <div class="col-md-3 col-sm-6">
                <a href="admin.php?view=verified" class="stat-card p-3 text-center <?php echo $view === 'verified' ? 'active-card' : ''; ?>">
                    <h6 class="text-muted text-uppercase small mb-1">Verified Real News</h6>
                    <h2 class="fw-bold text-success mb-0"><?php echo $verifiedCount; ?></h2>
                    <small class="text-muted">Confirmed Authentic</small>
                </a>
            </div>
            <div class="col-md-3 col-sm-6">
                <a href="admin.php?view=fake_cases" class="stat-card p-3 text-center <?php echo $view === 'fake_cases' ? 'active-card' : ''; ?>">
                    <h6 class="text-muted text-uppercase small mb-1">Identified Fake News</h6>
                    <h2 class="fw-bold text-danger mb-0"><?php echo $fakeCount; ?></h2>
                    <small class="text-muted">Confirmed Misinformation</small>
                </a>
            </div>
            <div class="col-md-3 col-sm-6">
                <a href="admin.php?view=users" class="stat-card p-3 text-center <?php echo $view === 'users' ? 'active-card' : ''; ?>">
                    <h6 class="text-muted text-uppercase small mb-1">Active Accounts</h6>
                    <h2 class="fw-bold text-primary mb-0"><?php echo $userCount; ?></h2>
                    <small class="text-muted">Registered System Users</small>
                </a>
            </div>
        </div>

        <!-- Navigation Tabs Bar -->
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link <?php echo $view === 'reports' ? 'active fw-bold' : 'text-dark'; ?>" href="admin.php?view=reports">
                    🚨 Pending Reports (<?php echo $pendingCount; ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $view === 'verified' ? 'active fw-bold' : 'text-dark'; ?>" href="admin.php?view=verified">
                    ✓ Verified Real News (<?php echo $verifiedCount; ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $view === 'fake_cases' ? 'active fw-bold' : 'text-dark'; ?>" href="admin.php?view=fake_cases">
                    ⚠️ Fake / Scam Cases (<?php echo $fakeCount; ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $view === 'history' ? 'active fw-bold' : 'text-dark'; ?>" href="admin.php?view=history">
                    🔍 Verification Logs (<?php echo $historyCount; ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $view === 'users' ? 'active fw-bold' : 'text-dark'; ?>" href="admin.php?view=users">
                    👥 User Accounts (<?php echo $userCount; ?>)
                </a>
            </li>
        </ul>

        <!-- Main Content Table Card -->
        <div class="card border-0 shadow-sm rounded-3 p-4">
            
            <?php if ($view === 'users'): ?>
                <!-- USERS MANAGEMENT VIEW -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-dark mb-0">Registered System Users (<?php echo $userCount; ?>)</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>User ID</th>
                                <th>Full Name</th>
                                <th>Email Address</th>
                                <th>Role</th>
                                <th>Age</th>
                                <th>Address</th>
                                <th>Joined Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($dataRes && $dataRes->num_rows > 0): ?>
                                <?php while ($u = $dataRes->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong>#<?php echo $u['id']; ?></strong></td>
                                        <td class="fw-semibold"><?php echo htmlspecialchars($u['name']); ?></td>
                                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                                        <td><span class="badge bg-primary"><?php echo htmlspecialchars($u['role'] ?? 'Standard User'); ?></span></td>
                                        <td><?php echo $u['age'] ? htmlspecialchars($u['age']) : '<span class="text-muted">-</span>'; ?></td>
                                        <td class="small text-muted" style="max-width: 200px;"><?php echo htmlspecialchars($u['address'] ?? '-'); ?></td>
                                        <td class="small text-muted"><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                                        <td>
                                            <a href="admin.php?view=users&action=delete_user&id=<?php echo $u['id']; ?>" 
                                               class="btn btn-sm btn-outline-danger" 
                                               onclick="return confirm('Are you sure you want to delete user #<?php echo $u['id']; ?> (<?php echo addslashes($u['name']); ?>)?')">
                                                Delete User
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">No registered users found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($view === 'history'): ?>
                <!-- VERIFICATION LOGS / SEARCH HISTORY VIEW -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-dark mb-0">Recent User Search & Verification Logs</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Log ID</th>
                                <th>User</th>
                                <th>Searched Headline</th>
                                <th>Trust Score</th>
                                <th>Verdict</th>
                                <th>Date/Time</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($dataRes && $dataRes->num_rows > 0): ?>
                                <?php while ($h = $dataRes->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo $h['id']; ?></td>
                                        <td>
                                            <?php echo !empty($h['user_name']) ? '<span class="fw-semibold text-primary">' . htmlspecialchars($h['user_name']) . '</span>' : '<span class="badge bg-secondary">Guest</span>'; ?>
                                        </td>
                                        <td class="text-wrap" style="max-width: 380px;">
                                            <?php echo htmlspecialchars($h['headline']); ?>
                                        </td>
                                        <td>
                                            <span class="fw-bold <?php echo $h['trust_score'] < 50 ? 'text-danger' : 'text-success'; ?>">
                                                <?php echo $h['trust_score']; ?>%
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo strpos($h['status'], 'High Risk') !== false || strpos($h['status'], 'Fake') !== false ? 'bg-danger' : (strpos($h['status'], 'High Credibility') !== false || strpos($h['status'], 'Verified') !== false ? 'bg-success' : 'bg-warning text-dark'); ?>">
                                                <?php echo htmlspecialchars($h['status']); ?>
                                            </span>
                                        </td>
                                        <td class="small text-muted"><?php echo date('M d, Y h:i A', strtotime($h['checked_at'])); ?></td>
                                        <td>
                                            <a href="admin.php?view=history&action=delete_history&id=<?php echo $h['id']; ?>" 
                                               class="btn btn-sm btn-outline-danger"
                                               onclick="return confirm('Delete this history record?')">
                                                Delete
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No search history logs recorded yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($view === 'verified' || $view === 'fake_cases'): ?>
                <!-- VERIFIED / FAKE CASES ARCHIVE -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0 <?php echo $view === 'verified' ? 'text-success' : 'text-danger'; ?>">
                        <?php echo $view === 'verified' ? '✓ Verified Authentic News Records' : '⚠️ Identified Fake News & Scam Archive'; ?>
                    </h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Report ID</th>
                                <th>Reported By</th>
                                <th>Headline Context</th>
                                <th>Platform</th>
                                <th>Reason / Remarks</th>
                                <th>Date Reported</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($dataRes && $dataRes->num_rows > 0): ?>
                                <?php while ($r = $dataRes->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo $r['id']; ?></td>
                                        <td><?php echo !empty($r['user_name']) ? htmlspecialchars($r['user_name']) : 'Anonymous'; ?></td>
                                        <td class="text-wrap fw-semibold" style="max-width: 320px;">
                                            "<?php echo htmlspecialchars($r['headline']); ?>"
                                        </td>
                                        <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars($r['platform'] ?: 'General'); ?></span></td>
                                        <td class="text-muted small" style="max-width: 250px;"><?php echo htmlspecialchars($r['reason'] ?: 'No remarks'); ?></td>
                                        <td class="small text-muted"><?php echo date('M d, Y h:i A', strtotime($r['date_reported'])); ?></td>
                                        <td>
                                            <span class="badge <?php echo $r['status'] === 'Verified Real' ? 'bg-success' : 'bg-danger'; ?>">
                                                <?php echo htmlspecialchars($r['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="admin.php?view=<?php echo $view; ?>&action=mark_pending&id=<?php echo $r['id']; ?>" class="btn btn-outline-warning" title="Move back to Pending">Reset to Pending</a>
                                                <a href="admin.php?view=<?php echo $view; ?>&action=delete_report&id=<?php echo $r['id']; ?>" class="btn btn-outline-danger" onclick="return confirm('Delete this record permanently?')">Delete</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">No records found in this category.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            <?php else: ?>
                <!-- PENDING USER REPORTS VIEW (Default) -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-dark mb-0">🚨 Incoming User Reports (Pending Review)</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Report ID</th>
                                <th>Reporter</th>
                                <th>Reported Headline</th>
                                <th>Platform</th>
                                <th>Remarks / Reason</th>
                                <th>Date Submitted</th>
                                <th style="min-width: 260px;">Verification Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($dataRes && $dataRes->num_rows > 0): ?>
                                <?php while ($r = $dataRes->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong>#<?php echo $r['id']; ?></strong></td>
                                        <td>
                                            <?php echo !empty($r['user_name']) ? '<span class="fw-semibold text-primary">' . htmlspecialchars($r['user_name']) . '</span>' : '<span class="badge bg-secondary">Anonymous</span>'; ?>
                                        </td>
                                        <td class="text-wrap fw-bold" style="max-width: 320px;">
                                            "<?php echo htmlspecialchars($r['headline']); ?>"
                                        </td>
                                        <td>
                                            <span class="badge bg-info text-dark"><?php echo htmlspecialchars($r['platform'] ?: 'General'); ?></span>
                                        </td>
                                        <td class="text-muted small" style="max-width: 260px;">
                                            <?php echo htmlspecialchars($r['reason'] ?: 'No remarks provided'); ?>
                                        </td>
                                        <td class="small text-muted"><?php echo date('M d, Y h:i A', strtotime($r['date_reported'])); ?></td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <a href="admin.php?view=reports&action=verify_real&id=<?php echo $r['id']; ?>" 
                                                   class="btn btn-sm btn-success" 
                                                   onclick="return confirm('Verify this item as REAL Authentic News?')">
                                                   ✓ Verify Real
                                                </a>
                                                <a href="admin.php?view=reports&action=mark_fake&id=<?php echo $r['id']; ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('Flag this item as FAKE / Scam?')">
                                                   ✗ Mark Fake
                                                </a>
                                                <a href="admin.php?view=reports&action=delete_report&id=<?php echo $r['id']; ?>" 
                                                   class="btn btn-sm btn-outline-secondary" 
                                                   onclick="return confirm('Delete this report?')">
                                                   🗑
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <em>No pending user reports requiring review at this time.</em>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>