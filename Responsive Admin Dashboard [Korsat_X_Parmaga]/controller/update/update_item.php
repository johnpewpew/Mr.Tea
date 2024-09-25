<?php
// update_item.php
require_once 'config/_init.php';
// Start session if you plan to handle user authentication in the future
session_start();

// Include the database configuration file
require_once 'config/itemdata.php';

// Initialize variables
$errors = [];
$success = "";

// Check if 'id' is present in GET parameters
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "Invalid item ID.";
    exit();
}

$itemId = intval($_GET['id']);

// Fetch the item's current data
$sql = "SELECT * FROM items WHERE id = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute([':id' => $itemId]);
$item = $stmt->fetch();

if (!$item) {
    echo "Item not found.";
    exit();
}

// Handle Form Submission for Updating the Item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_item'])) {
    // Sanitize and validate input data
    $itemName = trim($_POST['item_name']);
    $itemDescription = trim($_POST['item_description']);
    $itemQuantity = intval($_POST['item_quantity']);
    $itemPrice = floatval($_POST['item_price']);

    // Validate required fields
    if (empty($itemName)) {
        $errors[] = "Item name is required.";
    }
    if (empty($itemDescription)) {
        $errors[] = "Description is required.";
    }
    if ($itemQuantity < 0) {
        $errors[] = "Quantity cannot be negative.";
    }
    if ($itemPrice < 0) {
        $errors[] = "Price cannot be negative.";
    }

    // Handle Image Upload (Optional)
    $imagePath = $item['image']; // Keep existing image by default
    if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['item_image']['tmp_name'];
        $fileName = $_FILES['item_image']['name'];
        $fileSize = $_FILES['item_image']['size'];
        $fileType = $_FILES['item_image']['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        // Allowed file extensions
        $allowedfileExtensions = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($fileExtension, $allowedfileExtensions)) {
            // Directory where the uploaded file will be moved
            $uploadFileDir = 'uploads/';
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }
            // Sanitize file name
            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            $dest_path = $uploadFileDir . $newFileName;

            if(move_uploaded_file($fileTmpPath, $dest_path)) {
                // Delete the old image if it exists
                if ($item['image'] && file_exists($item['image'])) {
                    unlink($item['image']);
                }
                $imagePath = $dest_path;
            } else {
                $errors[] = "There was an error moving the uploaded file.";
            }
        } else {
            $errors[] = "Upload failed. Allowed file types: " . implode(',', $allowedfileExtensions);
        }
    }

    // If no errors, update the item in the database
    if (empty($errors)) {
        // Prepare SQL statement
        $sql = "UPDATE items SET name = :name, description = :description, quantity = :quantity, price = :price, image = :image 
                WHERE id = :id";

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':name' => $itemName,
                ':description' => $itemDescription,
                ':quantity' => $itemQuantity,
                ':price' => $itemPrice,
                ':image' => $imagePath,
                ':id' => $itemId
            ]);
            $success = "Item updated successfully.";
            // Fetch the updated item data
            $stmt = $pdo->prepare("SELECT * FROM items WHERE id = :id");
            $stmt->execute([':id' => $itemId]);
            $item = $stmt->fetch();
        } catch (PDOException $e) {
            $errors[] = "Error: " . htmlspecialchars($e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Item</title>
    <!-- ======= Styles ====== -->
    <link rel="stylesheet" href="/CSS/item.css">
</head>
<body>
    <div class="container">
        <!-- Update Item Section -->
        <div class="item-details">
            <h2>Update Item</h2>

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

            <!-- Update Item Form -->
            <form action="/controller/update/update_item.php?php echo $itemId; ?>" method="POST" enctype="multipart/form-data">
                <div class="image-container">
                    <?php if ($item['image'] && file_exists($item['image'])): ?>
                        <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="Item Image" id="item-image">
                    <?php else: ?>
                        <img src="https://via.placeholder.com/200" alt="Item Image" id="item-image">
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="item_image">Upload New Image (Optional)</label>
                    <input type="file" id="item_image" name="item_image" accept="image/*">
                </div>
                <div class="form-group">
                    <label for="item-name">Item Name</label>
                    <input type="text" id="item-name" name="item_name" placeholder="Item Name" required value="<?php echo htmlspecialchars($item['name']); ?>">
                </div>
                <div class="form-group">
                    <label for="item-description">Description</label>
                    <input type="text" id="item-description" name="item_description" placeholder="Description" required value="<?php echo htmlspecialchars($item['description']); ?>">
                </div>
                <div class="form-group">
                    <label for="item-quantity">Quantity</label>
                    <input type="number" id="item-quantity" name="item_quantity" placeholder="Quantity" required min="0" value="<?php echo htmlspecialchars($item['quantity']); ?>">
                </div>
                <div class="form-group">
                    <label for="item-price">Price</label>
                    <input type="number" id="item-price" name="item_price" placeholder="Price" required min="0" step="0.01" value="<?php echo htmlspecialchars($item['price']); ?>">
                </div>
                <button type="submit" name="update_item" id="update-btn">Update Item</button>
                <a href="/dashboard-views/item.php" class="cancel-btn">Cancel</a>
            </form>
        </div>

        <!-- Item List Section (Optional) -->
        <div class="item-list">
            <h2>Item List</h2>
            <input type="text" id="search-input" placeholder="Search items...">
            <table>
                <thead>
                    <tr>
                        <th>Icon</th>
                        <th>Item Name</th>
                        <th>Description</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="item-list-body">
                    <!-- Item rows will go here -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- ======= Scripts ====== -->
    <script src="/JAVA/Item.js"></script>
</body>
</html>
