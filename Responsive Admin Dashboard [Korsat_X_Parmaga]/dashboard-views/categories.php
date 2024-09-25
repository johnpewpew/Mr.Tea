<?php
// Start session if you plan to handle user authentication in the future
session_start();

// Include the database configuration file


// Initialize variables
$errors = [];
$success = "";

// Handle Form Submission for Adding a New Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    // Sanitize and validate input data
    $categoryName = trim($_POST['category_name']);

    // Validate required fields
    if (empty($categoryName)) {
        $errors[] = "Category name is required.";
    }

    // Handle Image Upload
    $imagePath = NULL; // Default image path
    if (isset($_FILES['category_image']) && $_FILES['category_image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['category_image']['tmp_name'];
        $fileName = $_FILES['category_image']['name'];
        $fileSize = $_FILES['category_image']['size'];
        $fileType = $_FILES['category_image']['type'];
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

    // If no errors, insert the category into the database
    if (empty($errors)) {
        // Prepare SQL statement
        $sql = "INSERT INTO categories (name, image) VALUES (:name, :image)";

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':name' => $categoryName,
                ':image' => $imagePath
            ]);
            $success = "Category added successfully.";
            // Clear form fields after successful addition
            $categoryName = "";
        } catch (PDOException $e) {
            $errors[] = "Error: " . htmlspecialchars($e->getMessage());
        }
    }
}

// Handle Deletion of a Category via GET request
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['delete_id'])) {
    $deleteId = intval($_GET['delete_id']);

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
        } catch (PDOException $e) {
            $errors[] = "Error deleting category: " . htmlspecialchars($e->getMessage());
        }
    } else {
        $errors[] = "Category not found.";
    }
}

// Handle Updating a Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_category'])) {
    $categoryId = intval($_POST['category_id']);
    $categoryName = trim($_POST['category_name']);

    // Validate required fields
    if (empty($categoryName)) {
        $errors[] = "Category name is required.";
    }

    // Handle Image Upload (Optional)
    $imagePath = NULL; // Default image path
    if (isset($_FILES['category_image']) && $_FILES['category_image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['category_image']['tmp_name'];
        $fileName = $_FILES['category_image']['name'];
        $fileSize = $_FILES['category_image']['size'];
        $fileType = $_FILES['category_image']['type'];
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

                // Fetch the old image path to delete the old image
                $fetchOldImageSql = "SELECT image FROM categories WHERE id = :id";
                $fetchOldImageStmt = $pdo->prepare($fetchOldImageSql);
                $fetchOldImageStmt->execute([':id' => $categoryId]);
                $oldCategory = $fetchOldImageStmt->fetch();

                if ($oldCategory && $oldCategory['image'] && file_exists($oldCategory['image'])) {
                    unlink($oldCategory['image']);
                }
            } else {
                $errors[] = "There was an error moving the uploaded file.";
            }
        } else {
            $errors[] = "Upload failed. Allowed file types: " . implode(',', $allowedfileExtensions);
        }
    }

    // If no errors, update the category in the database
    if (empty($errors)) {
        // Prepare SQL statement
        if ($imagePath) {
            $sql = "UPDATE categories SET name = :name, image = :image WHERE id = :id";
        } else {
            $sql = "UPDATE categories SET name = :name WHERE id = :id";
        }

        try {
            $stmt = $pdo->prepare($sql);
            $params = [
                ':name' => $categoryName,
                ':id' => $categoryId
            ];
            if ($imagePath) {
                $params[':image'] = $imagePath;
            }
            $stmt->execute($params);
            $success = "Category updated successfully.";
        } catch (PDOException $e) {
            $errors[] = "Error updating category: " . htmlspecialchars($e->getMessage());
        }
    }
}

// Fetch All Categories from the Database
$searchQuery = "";
if (isset($_GET['search'])) {
    $searchQuery = trim($_GET['search']);
}

if (!empty($searchQuery)) {
    $sql = "SELECT * FROM categories WHERE name LIKE :search ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':search' => '%' . $searchQuery . '%']);
} else {
    $sql = "SELECT * FROM categories ORDER BY created_at DESC";
    $stmt = $pdo->query($sql);
}
$categories = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories</title>
    <!-- ======= Styles ====== -->
    <link rel="stylesheet" href="/CSS/category.css">
</head>
<body>
    <div class="container">
        <!-- Category Details Section -->
        <div class="category-details">
            <h2>Category Details</h2>

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

            <!-- Add/Update Category Form -->
            <form action="/dashboard-views/categories.php<?php echo isset($_GET['edit_id']) ? '?edit_id=' . intval($_GET['edit_id']) : ''; ?>" method="POST" enctype="multipart/form-data">
                <?php
                // If editing, fetch the category data to pre-fill the form
                if (isset($_GET['edit_id'])) {
                    $editId = intval($_GET['edit_id']);
                    $editSql = "SELECT * FROM categories WHERE id = :id";
                    $editStmt = $pdo->prepare($editSql);
                    $editStmt->execute([':id' => $editId]);
                    $editCategory = $editStmt->fetch();

                    if ($editCategory) {
                        echo '<input type="hidden" name="category_id" value="' . htmlspecialchars($editCategory['id']) . '">';
                        echo '<div class="image-container">';
                        if ($editCategory['image'] && file_exists($editCategory['image'])) {
                            echo '<img src="' . htmlspecialchars($editCategory['image']) . '" alt="Category Image" id="category-image">';
                        } else {
                            echo '<img src="https://via.placeholder.com/200" alt="Category Image" id="category-image">';
                        }
                        echo '</div>';
                        echo '<div class="form-group">';
                        echo '<label for="category_image">Upload New Image (Optional)</label>';
                        echo '<input type="file" id="category_image" name="category_image" accept="image/*">';
                        echo '</div>';
                        echo '<div class="form-group">';
                        echo '<label for="category-name">Category Name</label>';
                        echo '<input type="text" id="category-name" name="category_name" placeholder="Category Name" required value="' . htmlspecialchars($editCategory['name']) . '">';
                        echo '</div>';
                        echo '<button type="submit" name="update_category" id="update-btn">Update Category</button>';
                        echo '<a href="categories.php" class="cancel-btn">Cancel</a>';
                        exit(); // Prevent displaying the add form below
                    } else {
                        echo '<div class="error-messages"><ul><li>Category not found.</li></ul></div>';
                    }
                }
                ?>
                <div class="image-container">
                    <img src="https://via.placeholder.com/200" alt="Category Image" id="category-image">
                </div>
                <div class="form-group">
                    <label for="category_image">Upload Image</label>
                    <input type="file" id="category_image" name="category_image" accept="image/*">
                </div>
                <div class="form-group">
                    <label for="category-name">Category Name</label>
                    <input type="text" id="category-name" name="category_name" placeholder="Category Name" required value="<?php echo isset($categoryName) ? htmlspecialchars($categoryName) : ''; ?>">
                </div>
                <button type="submit" name="add_category" id="add-btn">Add Category</button>
                <button type="reset" id="save-btn">Save Category</button>
                <!-- Update and Delete buttons are handled via links in the table -->
            </form>
        </div>

        <!-- Category List Section -->
        <div class="category-list">
            <h2>Category List</h2>
            <form method="GET" action="/dashboard-views/categories.php">
                <input type="text" id="search-input" name="search" placeholder="Search categories..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                <button type="submit">Search</button>
            </form>
            <table>
                <thead>
                    <tr>
                        <th>Icon</th>
                        <th>Category Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="category-list-body">
                    <?php if ($categories): ?>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td>
                                    <?php if ($category['image'] && file_exists($category['image'])): ?>
                                        <img src="<?php echo htmlspecialchars($category['image']); ?>" alt="Category Image" class="category-icon">
                                    <?php else: ?>
                                        <img src="https://via.placeholder.com/50" alt="No Image" class="category-icon">
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($category['name']); ?></td>
                                <td>
                                    <a href="/dashboard-views/categories.php?edit_id=<?php echo $category['id']; ?>" class="update-btn">Update</a>
                                    <a href="/controller/delete_category.php?id=<?php echo $category['id']; ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this category?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3">No categories found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ======= Scripts ====== -->
    <script src="/JAVA/Category.js"></script>
</body>
</html>
