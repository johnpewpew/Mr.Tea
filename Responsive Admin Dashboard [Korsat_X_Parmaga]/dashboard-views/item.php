<?php

// Start session if you plan to handle user authentication in the future



// Include the database configuration file

// Initialize variables
$errors = [];
$success = "";

// Handle Form Submission for Adding a New Item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_item'])) {
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

    // Handle Image Upload
    $imagePath = NULL; // Default image path
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
                $imagePath = $dest_path;
            } else {
                $errors[] = "There was an error moving the uploaded file.";
            }
        } else {
            $errors[] = "Upload failed. Allowed file types: " . implode(',', $allowedfileExtensions);
        }
    }

    // If no errors, insert the item into the database
    if (empty($errors)) {
        // Prepare SQL statement
        $sql = "INSERT INTO items (name, description, quantity, price, image) 
                VALUES (:name, :description, :quantity, :price, :image)";

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':name' => $itemName,
                ':description' => $itemDescription,
                ':quantity' => $itemQuantity,
                ':price' => $itemPrice,
                ':image' => $imagePath
            ]);
            $success = "Item added successfully.";
            // Clear form fields after successful addition
            $itemName = $itemDescription = "";
            $itemQuantity = $itemPrice = 0;
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
    <title>Items</title>
    <!-- ======= Styles ====== -->
    <link rel="stylesheet" href="/CSS/item.css">
</head>
<body>
    <?php require_once ('sidebar.php');?>
    <div class="container">
        <!-- Item Details Section -->
        <div class="item-details">
            <h2>Item Details</h2>
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

            <!-- Add Item Form -->
            <form action="item.php" method="POST" enctype="multipart/form-data">
                <div class="image-container">
                    <img src="https://via.placeholder.com/200" alt="Item Image" id="item-image">
                </div>
                <div class="form-group">
                    <label for="item_image">Upload Image</label>
                    <input type="file" id="item_image" name="item_image" accept="image/*">
                </div>
                <div class="form-group">
                    <label for="item-name">Item Name</label>
                    <input type="text" id="item-name" name="item_name" placeholder="Item Name" required value="<?php echo isset($itemName) ? htmlspecialchars($itemName) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="item-description">Description</label>
                    <input type="text" id="item-description" name="item_description" placeholder="Description" required value="<?php echo isset($itemDescription) ? htmlspecialchars($itemDescription) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="item-quantity">Quantity</label>
                    <input type="number" id="item-quantity" name="item_quantity" placeholder="Quantity" required min="0" value="<?php echo isset($itemQuantity) ? htmlspecialchars($itemQuantity) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="item-price">Price</label>
                    <input type="number" id="item-price" name="item_price" placeholder="Price" required min="0" step="0.01" value="<?php echo isset($itemPrice) ? htmlspecialchars($itemPrice) : ''; ?>">
                </div>
                <button type="submit" name="add_item" id="add-btn">Add Item</button>
                <button type="reset" id="save-btn">Save Item</button>
                <button type="button" id="update-btn">Update Item</button>
                <button type="button" id="delete-btn">Delete Item</button>
            </form>
        </div>

        <!-- Item List Section -->
        <div class="item-list">
            <h2>Item List</h2>
            <form method="GET" action="item.php">
                <input type="text" id="search-input" name="search" placeholder="Search items..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                <button type="submit">Search</button>
            </form>
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
                    <?php if ($items): ?>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td>
                                    <?php if ($item['image'] && file_exists($item['image'])): ?>
                                        <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="Item Image" class="item-icon">
                                    <?php else: ?>
                                        <img src="https://via.placeholder.com/50" alt="No Image" class="item-icon">
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td><?php echo htmlspecialchars($item['description']); ?></td>
                                <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                <td>$<?php echo number_format($item['price'], 2); ?></td>
                                <td>
                                    <a href="/controller/update/update_item.php?id=<?php echo $item['id']; ?>" class="update-btn">Update</a>
                                    <a href="/controller/delete_item.php?id=<?php echo $item['id']; ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this item?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">No items found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ======= Scripts ====== -->
    <script src="/JAVA/Item.js"></script>
</body>
</html>
