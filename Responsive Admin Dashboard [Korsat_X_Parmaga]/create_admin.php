<?php
// create_admin.php

$username = ''; // Replace with desired username
$password = ''; // Replace with desired password

// Hash the password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Insert into database
try {
    $stmt = $pdo->prepare("INSERT INTO admins (username, password) VALUES (:username, :password)");
    $stmt->execute([
        ':username' => $username,
        ':password' => $hashedPassword
    ]);
    echo "Admin account created successfully.";
} catch (PDOException $e) {
    echo "Error creating admin account: " . htmlspecialchars($e->getMessage());
}

