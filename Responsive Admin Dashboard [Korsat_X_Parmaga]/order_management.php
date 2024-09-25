<?php
// order_management.php



require_once 'config/_init.php';
require_once 'classes/Product.php';
require_once 'classes/Category.php';
require_once 'classes/Order.php';
require_once 'classes/OrderItem.php';
require_once 'db.php';
require_once'_helper.php';

// Redirect to login if not authenticated

// Initialize current order in session if not already set
if (!isset($_SESSION['current_order'])) {
    $_SESSION['current_order'] = [
        'items' => [], // Each item: ['product_id' => int, 'name' => string, 'quantity' => int, 'price' => float, 'subtotal' => float]
        'total_amount' => 0.00
    ];
}

// Handle Add to Order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_order'])) {
    // CSRF Token Check (if implemented)
    // if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    //     die('Invalid CSRF token');
    // }

    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);

    // Fetch product details
    $product = Product::find($pdo, $product_id);
    if ($product && $product->quantity >= $quantity) {
        // Check if product already in order
        $found = false;
        foreach ($_SESSION['current_order']['items'] as &$item) {
            if ($item['product_id'] === $product_id) {
                $item['quantity'] += $quantity;
                $item['subtotal'] = $item['quantity'] * $item['price'];
                $found = true;
                break;
            }
        }
        unset($item); // Break reference

        if (!$found) {
            // Add new item to order
            $_SESSION['current_order']['items'][] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'quantity' => $quantity,
                'price' => $product->price,
                'subtotal' => $quantity * $product->price
            ];
        }

        // Update total amount
        $_SESSION['current_order']['total_amount'] = array_sum(array_column($_SESSION['current_order']['items'], 'subtotal'));

        // Optionally, decrease product stock
        // $product->quantity -= $quantity;
        // $product->update($pdo);
    } else {
        // Handle error: product not found or insufficient stock
        $_SESSION['error'] = "Product not found or insufficient stock.";
    }

    header("Location: order_management.php");
    exit();
}

// Handle Clear Order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_order'])) {
    $_SESSION['current_order'] = [
        'items' => [],
        'total_amount' => 0.00
    ];

    header("Location: order_management.php");
    exit();
}

// Handle Payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['pay_now']) || isset($_POST['pay_later']))) {
    $payment_status = isset($_POST['pay_now']) ? 'Paid' : 'Pending';

    if (empty($_SESSION['current_order']['items'])) {
        $_SESSION['error'] = "No items in the order to process.";
        header("Location: order_management.php");
        exit();
    }

    // Create a new order
    $order = new Order([
        'id' => null,
        'order_date' => null,
        'total_amount' => $_SESSION['current_order']['total_amount'],
        'payment_status' => $payment_status
    ]);

    // Add items to order
    foreach ($_SESSION['current_order']['items'] as $item) {
        $orderItem = new OrderItem([
            'id' => null,
            'order_id' => null,
            'product_id' => $item['product_id'],
            'quantity' => $item['quantity'],
            'price' => $item['price'],
            'subtotal' => $item['subtotal']
        ]);
        $order->addItem($orderItem);
    }

    // Save order to database
    $order->save($pdo);

    // Update product stock
    foreach ($_SESSION['current_order']['items'] as $item) {
        $product = Product::find($pdo, $item['product_id']);
        if ($product) {
            $product->quantity -= $item['quantity'];
            $product->update($pdo);
        }
    }

    // Clear current order
    $_SESSION['current_order'] = [
        'items' => [],
        'total_amount' => 0.00
    ];

    if ($payment_status === 'Paid') {
        $_SESSION['success'] = "Order paid successfully.";
    } else {
        $_SESSION['success'] = "Order saved for payment later.";
    }

    header("Location:  order_management.php");
    exit();
}

// Fetch categories for display
$categories = Category::all($pdo);

// Fetch products based on selected category via GET parameter
$selected_category = isset($_GET['category']) ? intval($_GET['category']) : null;
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

if ($selected_category) {
    $stmt = $pdo->prepare('SELECT * FROM products WHERE category_id = :category_id');
    $stmt->bindParam(':category_id', $selected_category, PDO::PARAM_INT);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($search_query) {
    $stmt = $pdo->prepare('SELECT * FROM products WHERE name LIKE :search');
    $search_term = '%' . $search_query . '%';
    $stmt->bindParam(':search', $search_term, PDO::PARAM_STR);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Fetch all products
    $stmt = $pdo->prepare('SELECT * FROM products');
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<?php
include 'config.php';

if(!isset($_SESSION['user_name'])){
   header('location:index.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Management</title>
    <link rel="stylesheet" href="meme.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@100..900&display=swap" rel="stylesheet">
    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
</head>
<body>

    <div class="container">
        <!-- Sidebar Navigation -->
        <div class="sidebar">
            <div class="nav-item home">
                <span class="material-icons-outlined">home</span>HOME
            </div>
            <div class="nav-item pending">
                <span class="material-icons-outlined">pending</span>PENDING
            </div>
            <div class="nav-item logout">
                <a href="logout.php" style="color: inherit; text-decoration: none;">
                    <span class="material-icons-outlined">logout</span>Logout
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Search and Category Buttons -->
            <div class="search-container">
                <form method="GET" action="order_management.php">
                    <input type="text" placeholder="Search" name="search" id="search-input" value="<?= htmlspecialchars($search_query) ?>">
                    <button class="search-button" type="submit"><h3>Search</h3></button>
                </form>
            </div>
            <div class="buttons-container">
                <?php foreach ($categories as $category): ?>
                    <form method="GET" action="order_management.php" style="display: inline;">
                        <input type="hidden" name="category" value="<?= $category->id ?>">
                        <button class="category-button" type="submit"><h5><?= htmlspecialchars($category->name) ?></h5></button>
                    </form>
                <?php endforeach; ?>
                <form method="GET" action="order_management.php" style="display: inline;">
                    <button class="category-button" type="submit"><h5>ALL</h5></button>
                </form>
            </div>

            <!-- Products Listing -->
            <div class="products-container">
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $product): ?>
                        <div class="product-card">
                            <h3><?= htmlspecialchars($product['name']) ?></h3>
                            <p>Price: ₱<?= number_format($product['price'], 2) ?></p>
                            <p>Available: <?= $product['quantity'] ?></p>
                            <?php if ($product['quantity'] > 0): ?>
                                <form method="POST" action="order_management.php">
                                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                    <input type="number" name="quantity" value="1" min="1" max="<?= $product['quantity'] ?>" required>
                                    <button type="submit" name="add_to_order">Add to Order</button>
                                </form>
                            <?php else: ?>
                                <p style="color: red;">Out of Stock</p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No products found.</p>
                <?php endif; ?>
            </div>

             <!-- Pagination (Placeholder for Future Implementation) -->
             <div class="pagination">
                <button class="page-button active">1</button>
                <button class="page-button">2</button>
                <button class="page-button">Last</button>
                <button class="page-button">Next</button>
            </div>

        </div>

        <!-- Order Section -->
        <div class="order-section">
            <div class="order-header">
                <h1>Current Order</h1>
                <form method="POST" action="order_management.php">
                    <button class="clear-button" type="submit" name="clear_order"><h3>Clear</h3></button>
                </form>
            </div>
            <div class="order-list" id="order-list">
                <?php if (!empty($_SESSION['current_order']['items'])): ?>
                    <?php foreach ($_SESSION['current_order']['items'] as $item): ?>
                        <div class="order-item">
                            <p><?= htmlspecialchars($item['name']) ?> - Qty: <?= $item['quantity'] ?></p>
                            <p>Subtotal: ₱<?= number_format($item['subtotal'], 2) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No items in the order.</p>
                <?php endif; ?>
            </div>
            <div class="order-footer">
                <p>Total: ₱<span id="total-amount"><?= number_format($_SESSION['current_order']['total_amount'], 2) ?></span></p>
                <div class="payment-buttons">
                    <form method="POST" action="order_management.php" style="display: inline;">
                        <button class="pay-later" type="submit" name="pay_later"><h1>Pay Later</h1></button>
                    </form>
                    <form method="POST" action="order_management.php" style="display: inline;">
                        <button class="pay-now" type="submit" name="pay_now"><h1>Pay Now</h1></button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Display Success and Error Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="message success">
            <?= htmlspecialchars($_SESSION['success']) ?>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="message error">
            <?= htmlspecialchars($_SESSION['error']) ?>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <script src="/JAVA/oder.js"></script>
</body>
</html>
