<?php
// update_category.php
require_once 'config/_init.php';
// Start session if you plan to handle user authentication in the future
session_start();

// Include the database configuration file
require_once 'config/categoriesdata.php';

// Initialize variables
$errors = [];
$success = "";

// Check if 'id' is present in GET parameters
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "Invalid category ID.";
    exit();
}

$categoryId = intval($_GET['id']);

// Fetch the category's current data
$sql = "SELECT * FROM categories WHERE id = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute([':id' => $categoryId]);
$category = $stmt->fetch();

if (!$category) {
    echo "Category not found.";
    exit();
}

// Handle Form Submission for Updating the Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_category'])) {
    // Sanitize and validate input data
    $categoryName = trim($_POST['category_name']);

    // Validate required fields
    if (empty($categoryName)) {
        $errors[] = "Category name is required.";
    }

    // Handle Image Upload (Optional)
    $imagePath = $category['image']; // Keep existing image by default
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
                // Delete the old image if it exists
                if ($category['image'] && file_exists($category['image'])) {
                    unlink($category['image']);
                }
                $imagePath = $dest_path;
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

            // Fetch the updated category data
            $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = :id");
            $stmt->execute([':id' => $categoryId]);
            $category = $stmt->fetch();
        } catch (PDOException $e) {
            $errors[] = "Error updating category: " . htmlspecialchars($e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Category</title>
    <!-- ======= Styles ====== -->
    <link rel="stylesheet" href="/CSS/category.css">
</head>
<body>
    <div class="container">
        <!-- Update Category Section -->
        <div class="category-details">
            <h2>Update Category</h2>

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

            <!-- Update Category Form -->
            <form action="/controller/update/update_category.php?id=<?php echo $categoryId; ?>" method="POST" enctype="multipart/form-data">
                <div class="image-container">
                    <?php if ($category['image'] && file_exists($category['image'])): ?>
                        <img src="<?php echo htmlspecialchars($category['image']); ?>" alt="Category Image" id="category-image">
                    <?php else: ?>
                        <img src="https://via.placeholder.com/200" alt="Category Image" id="category-image">
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="category_image">Upload New Image (Optional)</label>
                    <input type="file" id="category_image" name="category_image" accept="image/*">
                </div>
                <div class="form-group">
                    <label for="category-name">Category Name</label>
                    <input type="text" id="category-name" name="category_name" placeholder="Category Name" required value="<?php echo htmlspecialchars($category['name']); ?>">
                </div>
                <button type="submit" name="update_category" id="update-btn">Update Category</button>
                <a href="categories.php" class="cancel-btn">Cancel</a>
            </form>
        </div>

        <!-- Category List Section (Optional) -->
        <div class="category-list">
            <h2>Category List</h2>
            <input type="text" id="search-input" placeholder="Search categories...">
            <table>
                <thead>
                    <tr>
                        <th>Icon</th>
                        <th>Category Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="category-list-body">
                    <!-- Category rows will go here -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- ======= Scripts ====== -->
    <script src="/JAVA/Category.js"></script>
</body>
</html>
