<?php
require_once 'db_config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.html?login_required=1");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$success_msg = "";
$error_msg = "";

// Handle Form Submission (Saving Updated Profile Details)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name'] ?? '');
    $age     = !empty($_POST['age']) ? (int)$_POST['age'] : null;
    $address = trim($_POST['address'] ?? '');
    $role    = trim($_POST['role'] ?? 'Standard User');

    if (empty($name)) {
        $error_msg = "Name cannot be empty.";
    } else {
        // Handle Profile Picture Upload
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath   = $_FILES['profile_pic']['tmp_name'];
            $origFileName  = $_FILES['profile_pic']['name'];
            $fileExtension = strtolower(pathinfo($origFileName, PATHINFO_EXTENSION));
            $allowedExts   = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (in_array($fileExtension, $allowedExts)) {
                $newFileName   = 'user_' . $user_id . '_' . time() . '.' . $fileExtension;
                $uploadFileDir = __DIR__ . '/uploads/';

                if (!is_dir($uploadFileDir)) {
                    mkdir($uploadFileDir, 0755, true);
                }

                $dest_path = $uploadFileDir . $newFileName;
                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    $picStmt = $conn->prepare("UPDATE users SET profile_pic = ? WHERE id = ?");
                    $picStmt->bind_param("si", $newFileName, $user_id);
                    $picStmt->execute();
                    $picStmt->close();
                    $_SESSION['profile_pic'] = $newFileName;
                } else {
                    $error_msg = "Failed to move uploaded photo.";
                }
            } else {
                $error_msg = "Invalid file type. Please upload a JPG, PNG, WEBP, or GIF image.";
            }
        }

        // Update Text Fields & Selected Role in Database
        if (empty($error_msg)) {
            $updateStmt = $conn->prepare("UPDATE users SET name = ?, age = ?, address = ?, role = ? WHERE id = ?");
            $updateStmt->bind_param("sissi", $name, $age, $address, $role, $user_id);

            if ($updateStmt->execute()) {
                $_SESSION['user_name'] = $name;
                $_SESSION['role'] = $role;
                $success_msg = "Profile updated successfully!";
            } else {
                $error_msg = "Error updating profile details: " . $conn->error;
            }
            $updateStmt->close();
        }
    }
}

// Fetch Current User Details from Database
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$user = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$user) {
    // If user no longer in DB, clear session
    session_destroy();
    header("Location: index.html");
    exit;
}

$username     = $user['name'] ?? 'User';
$email        = $user['email'] ?? 'user@fakenews.com';
$age          = $user['age'] ?? '';
$address      = $user['address'] ?? '';
$current_role = $user['role'] ?? 'Standard User';

$profilePicPath = 'uploads/' . ($user['profile_pic'] ?? '');
if (!empty($user['profile_pic']) && file_exists(__DIR__ . '/' . $profilePicPath)) {
    $pic = $profilePicPath;
} else {
    $pic = "https://ui-avatars.com/api/?name=" . urlencode($username) . "&background=0D8ABC&color=fff&size=150";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - VeriTruth Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .profile-card {
            background: #ffffff;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        .profile-img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid #0d6efd;
        }
    </style>
</head>
<body class="bg-light">

    <!-- Top Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-custom px-3 shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.html">🛡️ VeriTruth Portal</a>
            <div class="ms-auto d-flex gap-2">
                <a href="index.html" class="btn btn-outline-light btn-sm">Home Portal</a>
                <a href="admin.php" class="btn btn-light btn-sm text-primary fw-bold">Admin Console</a>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                
                <?php if (!empty($success_msg)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <strong>Success:</strong> <?php echo htmlspecialchars($success_msg); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error_msg)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Error:</strong> <?php echo htmlspecialchars($error_msg); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="profile-card p-4">
                    <h3 class="fw-bold text-dark mb-4 border-bottom pb-2">Account Settings & Profile</h3>
                    
                    <form action="profile.php" method="POST" enctype="multipart/form-data">
                        
                        <!-- Profile Header & Picture Upload -->
                        <div class="d-flex align-items-center gap-4 mb-4 flex-wrap">
                            <img src="<?php echo htmlspecialchars($pic); ?>" alt="Profile Picture" class="profile-img" id="imgPreview">
                            <div>
                                <h4 class="fw-bold mb-1"><?php echo htmlspecialchars($username); ?></h4>
                                <p class="text-muted mb-1"><?php echo htmlspecialchars($email); ?></p>
                                <span class="badge bg-primary mb-2"><?php echo htmlspecialchars($current_role); ?></span>
                                <div>
                                    <input type="file" name="profile_pic" class="form-control form-control-sm" accept="image/*" onchange="previewImage(event)">
                                    <small class="text-muted">Upload profile photo (JPG, PNG, WEBP)</small>
                                </div>
                            </div>
                        </div>

                        <!-- User Info Form Fields -->
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Full Name</label>
                                <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($username); ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Email Address (Account Identifier)</label>
                                <input type="email" class="form-control bg-light" value="<?php echo htmlspecialchars($email); ?>" readonly>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Age</label>
                                <input type="number" name="age" min="10" max="120" class="form-control" placeholder="Enter age" value="<?php echo htmlspecialchars($age); ?>">
                            </div>

                            <!-- Account Role Selector Dropdown -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Account Role</label>
                                <select name="role" class="form-select">
                                    <option value="Standard User" <?php echo ($current_role === 'Standard User') ? 'selected' : ''; ?>>Standard User</option>
                                    <option value="Reporter" <?php echo ($current_role === 'Reporter') ? 'selected' : ''; ?>>Reporter / Journalist</option>
                                    <option value="Moderator" <?php echo ($current_role === 'Moderator') ? 'selected' : ''; ?>>Community Moderator</option>
                                    <option value="Fact Checker" <?php echo ($current_role === 'Fact Checker') ? 'selected' : ''; ?>>Verified Fact Checker</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">Address / Location</label>
                                <textarea name="address" class="form-control" rows="3" placeholder="Enter city, state or address"><?php echo htmlspecialchars($address); ?></textarea>
                            </div>
                        </div>

                        <div class="mt-4 d-flex justify-content-end gap-2">
                            <a href="index.html" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4">Save Changes</button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>

    <script>
        function previewImage(event) {
            const reader = new FileReader();
            reader.onload = function() {
                const output = document.getElementById('imgPreview');
                output.src = reader.result;
            };
            if (event.target.files && event.target.files[0]) {
                reader.readAsDataURL(event.target.files[0]);
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>