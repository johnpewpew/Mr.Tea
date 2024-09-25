<?php
// delete_employee.php
require_once 'config/_init.php';
// Start session
session_start();

// Include the database configuration file
require_once 'config/employeesdata.php';

// Initialize variables
$errors = [];
$success = "";

// Check if 'id' is present in GET parameters
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $deleteId = intval($_GET['id']);

    // Fetch the employee's image path to delete the image file
    $fetchSql = "SELECT image FROM employees WHERE id = :id";
    $fetchStmt = $pdo->prepare($fetchSql);
    $fetchStmt->execute([':id' => $deleteId]);
    $employee = $fetchStmt->fetch();

    if ($employee) {
        // Delete the image file if it exists
        if ($employee['image'] && file_exists($employee['image'])) {
            unlink($employee['image']);
        }

        // Delete the employee from the database
        $deleteSql = "DELETE FROM employees WHERE id = :id";
        $deleteStmt = $pdo->prepare($deleteSql);
        try {
            $deleteStmt->execute([':id' => $deleteId]);
            $success = "Employee deleted successfully.";
            // Redirect back to employees.php with success message
            header("Location: /dashboard-views/employees.php?success=" . urlencode($success));
            exit();
        } catch (PDOException $e) {
            $errors[] = "Error deleting employee: " . htmlspecialchars($e->getMessage());
        }
    } else {
        $errors[] = "Employee not found.";
    }
} else {
    $errors[] = "Invalid request.";
}

// If there are errors, redirect back with error messages
if (!empty($errors)) {
    // For simplicity, redirect without detailed error messages
    header("Location: /dashboard-views/employees.php?error=Unable to delete employee.");
    exit();
}

