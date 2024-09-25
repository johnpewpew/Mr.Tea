<?php
// delete_category.php
require_once 'config/_init.php';
// Start session if you plan to handle user authentication in the future
session_start();

// Include the database configuration file
require_once 'config/catagoriesdata.php';

// Initialize variables
$errors = [];
$success = "";

// Check if 'id' is present in GET parameters
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $deleteId = intval($_GET['id']);

    // Fetch the category's image path to delete the image file
    $fetchSql = "SELECT image FROM categories WHERE id = :id";
    $fetchStmt = $pdo->prepare($fetchSql);
    $fetchStmt->execute([':id' => $deleteId]);
    $category = $fetchStmt->fetch();

    if ($category) {
        // Delete the image file if it exists
        if ($category['image'] && file_exists($category['image'])) {
            unlink($category['image']);
        }

        // Delete the category from the database
        $deleteSql = "DELETE FROM categories WHERE id = :id";
        $deleteStmt = $pdo->prepare($deleteSql);
        try {
            $deleteStmt->execute([':id' => $deleteId]);
            $success = "Category deleted successfully.";
            // Redirect back to categories.php with success message
            header("Location: categories.php?success=" . urlencode($success));
            exit();
        } catch (PDOException $e) {
            $errors[] = "Error deleting category: " . htmlspecialchars($e->getMessage());
        }
    } else {
        $errors[] = "Category not found.";
    }
} else {
    $errors[] = "Invalid request.";
}

// If there are errors, redirect back with error messages
if (!empty($errors)) {
    // For simplicity, redirect without detailed error messages
    header("Location: /dashboard-views/categories.php?error=Unable to delete category.");
    exit();
}

