<?php
// delete_item.php
require_once 'config/_init.php';
// Start session if you plan to handle user authentication in the future
session_start();

// Include the database configuration file
require_once 'config/itemdata.php';

// Initialize variables
$errors = [];
$success = "";

// Check if 'id' is present in GET parameters
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $deleteId = intval($_GET['id']);

    // Fetch the item's image path to delete the image file
    $fetchSql = "SELECT image FROM items WHERE id = :id";
    $fetchStmt = $pdo->prepare($fetchSql);
    $fetchStmt->execute([':id' => $deleteId]);
    $item = $fetchStmt->fetch();

    if ($item) {
        // Delete the image file if it exists
        if ($item['image'] && file_exists($item['image'])) {
            unlink($item['image']);
        }

        // Delete the item from the database
        $deleteSql = "DELETE FROM items WHERE id = :id";
        $deleteStmt = $pdo->prepare($deleteSql);
        try {
            $deleteStmt->execute([':id' => $deleteId]);
            $success = "Item deleted successfully.";
            // Redirect back to items.php with success message
            header("Location: /dashboard-views/items.php?success=" . urlencode($success));
            exit();
        } catch (PDOException $e) {
            $errors[] = "Error deleting item: " . htmlspecialchars($e->getMessage());
        }
    } else {
        $errors[] = "Item not found.";
    }
} else {
    $errors[] = "Invalid request.";
}

// If there are errors, redirect back with error messages
if (!empty($errors)) {
    // For simplicity, redirect without detailed error messages
    header("Location: /dashboard-views/items.php?error=Unable to delete item.");
    exit();
}

