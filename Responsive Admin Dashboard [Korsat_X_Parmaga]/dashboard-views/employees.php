<?php
// employees.php

// Start session if you plan to handle user authentication in the future
session_start();


// Include the database configuration file


// Initialize variables
$errors = [];
$success = "";

// Handle Form Submission for Registering a New Employee
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_employee'])) {
    // Sanitize and validate input data
    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);
    $username = trim($_POST['username']);
    $birthDate = $_POST['birth_date'];
    $age = intval($_POST['age']);

    // Validate required fields
    if (empty($firstName)) {
        $errors[] = "First name is required.";
    }
    if (empty($lastName)) {
        $errors[] = "Last name is required.";
    }
    if (empty($username)) {
        $errors[] = "Username is required.";
    }
    if (empty($birthDate)) {
        $errors[] = "Birth date is required.";
    }
    if ($age <= 0) {
        $errors[] = "Age must be a positive number.";
    }

    // Handle Image Upload
    $imagePath = NULL; // Default image path
    if (isset($_FILES['employee_image']) && $_FILES['employee_image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['employee_image']['tmp_name'];
        $fileName = $_FILES['employee_image']['name'];
        $fileSize = $_FILES['employee_image']['size'];
        $fileType = $_FILES['employee_image']['type'];
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

    // If no errors, insert the new employee into the database
    if (empty($errors)) {
        // Prepare SQL statement
        $sql = "INSERT INTO employees (first_name, last_name, username, birth_date, age, image) 
                VALUES (:first_name, :last_name, :username, :birth_date, :age, :image)";

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':first_name' => $firstName,
                ':last_name' => $lastName,
                ':username' => $username,
                ':birth_date' => $birthDate,
                ':age' => $age,
                ':image' => $imagePath
            ]);
            $success = "Employee registered successfully.";
            // Clear form fields after successful registration
            $firstName = $lastName = $username = $birthDate = $age = "";
        } catch (PDOException $e) {
            // Handle duplicate username error
            if ($e->getCode() == 23000) { // Integrity constraint violation
                $errors[] = "Username already exists. Please choose a different one.";
            } else {
                $errors[] = "Error: " . htmlspecialchars($e->getMessage());
            }
        }
    }
}

// Handle Deletion of an Employee
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['delete_id'])) {
    $deleteId = intval($_GET['delete_id']);

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
        } catch (PDOException $e) {
            $errors[] = "Error deleting employee: " . htmlspecialchars($e->getMessage());
        }
    } else {
        $errors[] = "Employee not found.";
    }
}

// Fetch All Employees from the Database
$fetchAllSql = "SELECT * FROM employees ORDER BY created_at DESC";
$fetchAllStmt = $pdo->query($fetchAllSql);
$employees = $fetchAllStmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employees</title>
    <!-- ======= Styles ====== -->
    <link rel="stylesheet" href="/CSS/employees.css">
</head>
<body>
    <div class="container">
        <!-- Employee Details Section -->
        <div class="employee-details">
            <h2>Employee Details</h2>

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

            <!-- Registration Form -->
            <form action="/dashboard-views/employees.php" method="POST" enctype="multipart/form-data" id="register-form">
                <div class="image-container">
                    <img src="https://via.placeholder.com/150" alt="Employee Image" id="employee-image">
                </div>
                <div class="form-group">
                    <label for="employee_image">Upload Image</label>
                    <input type="file" id="employee_image" name="employee_image" accept="image/*">
                </div>
                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name" placeholder="First Name" required>
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" placeholder="Last Name" required>
                </div>
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Username" required>
                </div>
                <div class="form-group">
                    <label for="birth_date">Birth Date</label>
                    <input type="date" id="birth_date" name="birth_date" required>
                </div>
                <div class="form-group">
                    <label for="age">Age</label>
                    <input type="number" id="age" name="age" placeholder="Age" min="0" required>
                </div>
                <button type="submit" name="register_employee" id="register-btn">Register Employee</button>
                <button type="button" id="delete-btn" onclick="window.location.href='/dashboard-views/employees.php'">Refresh List</button>
            </form>
        </div>

        <!-- Employee List Section -->
        <div class="employee-list">
            <h2>Employee List</h2>
            <input type="text" id="search-input" placeholder="Search employees...">
            <table>
                <thead>
                    <tr>
                        <th>Icon</th>
                        <th>Username</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Age</th>
                        <th>Birth Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="employee-list-body">
                    <?php if ($employees): ?>
                        <?php foreach ($employees as $employee): ?>
                            <tr>
                                <td>
                                    <?php if ($employee['image'] && file_exists($employee['image'])): ?>
                                        <img src="<?php echo htmlspecialchars($employee['image']); ?>" alt="Employee Image" class="employee-icon">
                                    <?php else: ?>
                                        <img src="https://via.placeholder.com/50" alt="No Image" class="employee-icon">
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($employee['username']); ?></td>
                                <td><?php echo htmlspecialchars($employee['first_name']); ?></td>
                                <td><?php echo htmlspecialchars($employee['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($employee['age']); ?></td>
                                <td><?php echo htmlspecialchars($employee['birth_date']); ?></td>
                                <td>
                                <a href="/controller/delete_employee.php?id=<?php echo $employee['id']; ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this employee?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7">No employees found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ======= Scripts ====== -->
    <script src="/JAVA/Employees.js"></script>
</body>
</html>
