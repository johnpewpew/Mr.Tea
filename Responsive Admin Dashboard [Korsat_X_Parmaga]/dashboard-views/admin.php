<?php
// admin.php

// Include the authentication check


// Include the database configuration file


// Initialize variables
$errors = [];
$success = "";

// Handle Form Submission for Updating Account
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_account'])) {
    // Retrieve and sanitize form inputs
    $username = trim($_POST['username']);
    $currentPassword = $_POST['current-password'];
    $newPassword = $_POST['new-password'];
    $confirmPassword = $_POST['confirm-password'];

    // Validate inputs
    if (empty($username)) {
        $errors[] = "Username is required.";
    }

    if (!empty($newPassword)) {
        if (strlen($newPassword) < 6) {
            $errors[] = "New Password must be at least 6 characters long.";
        }

        if ($newPassword !== $confirmPassword) {
            $errors[] = "New Password and Confirm Password do not match.";
        }
    }

    // Fetch the current admin data
    $sql = "SELECT * FROM admins WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $_SESSION['admin_id']]);
    $admin = $stmt->fetch();

    if (!$admin) {
        $errors[] = "Admin account not found.";
    } else {
        // Verify current password
        if (!password_verify($currentPassword, $admin['password'])) {
            $errors[] = "Current Password is incorrect.";
        }
    }

    // If no errors, proceed to update
    if (empty($errors)) {
        try {
            // Begin transaction
            $pdo->beginTransaction();

            // Update username if changed
            if ($username !== $admin['username']) {
                // Check if the new username already exists
                $checkSql = "SELECT id FROM admins WHERE username = :username AND id != :id";
                $checkStmt = $pdo->prepare($checkSql);
                $checkStmt->execute([
                    ':username' => $username,
                    ':id' => $_SESSION['admin_id']
                ]);
                if ($checkStmt->fetch()) {
                    $errors[] = "Username already taken. Please choose another one.";
                } else {
                    $updateSql = "UPDATE admins SET username = :username WHERE id = :id";
                    $updateStmt = $pdo->prepare($updateSql);
                    $updateStmt->execute([
                        ':username' => $username,
                        ':id' => $_SESSION['admin_id']
                    ]);
                    $_SESSION['admin_username'] = $username;
                }
            }

            // Update password if provided
            if (!empty($newPassword)) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $passwordSql = "UPDATE admins SET password = :password WHERE id = :id";
                $passwordStmt = $pdo->prepare($passwordSql);
                $passwordStmt->execute([
                    ':password' => $hashedPassword,
                    ':id' => $_SESSION['admin_id']
                ]);
            }

            // Commit transaction
            $pdo->commit();

            if (empty($errors)) {
                $success = "Account updated successfully.";
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = "Error updating account: " . htmlspecialchars($e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings</title>
    <!-- ======= Styles ====== -->
    <link rel="stylesheet" href="/CSS/admin.css">
</head>
<body>
    <div class="form-container">
        <div class="admin-settings">
            <h2>Admin Settings</h2>

            <!-- Display Success Message -->
            <?php if (!empty($success)): ?>
                <div class="success-message">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <!-- Display Errors -->
            <?php if (!empty($errors)): ?>
                <div class="error-messages">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Update Account Form -->
            <form action="/dashboard-views/admin.php" method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Username" required value="<?php echo isset($username) ? htmlspecialchars($username) : htmlspecialchars($_SESSION['admin_username']); ?>">
                </div>

                <div class="form-group">
                    <label for="current-password">Current Password</label>
                    <input type="password" id="current-password" name="current-password" placeholder="Current Password" required>
                </div>

                <div class="form-group">
                    <label for="new-password">New Password</label>
                    <input type="password" id="new-password" name="new-password" placeholder="New Password">
                </div>

                <div class="form-group">
                    <label for="confirm-password">Confirm New Password</label>
                    <input type="password" id="confirm-password" name="confirm-password" placeholder="Confirm New Password">
                </div>

                <div class="show-password">
                    <input type="checkbox" id="show-password">
                    <label for="show-password">Show Password</label>
                </div>

                <button type="submit" name="update_account">Update Account</button>
            </form>

            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <!-- ======= Scripts ====== -->
    <script src="/JAVA/admin.js"></script>
</body>
</html>
