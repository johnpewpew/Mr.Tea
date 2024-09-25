<?php
// transactions.php
// Include the database configuration file
// Initialize variables
$searchQuery = '';
$filter = '';
$errors = [];
$success = '';

// Handle Search and Filter Inputs
if (isset($_GET['search'])) {
    $searchQuery = trim($_GET['search']);
}

if (isset($_GET['filter'])) {
    $filter = trim($_GET['filter']);
}

// Build the SQL query with search and filter
$sql = "SELECT * FROM transactions WHERE 1";
$params = [];

// Search functionality
if (!empty($searchQuery)) {
    $sql .= " AND (transaction_number LIKE :search OR order_details LIKE :search OR employee_name LIKE :search)";
    $params[':search'] = '%' . $searchQuery . '%';
}

// Filter functionality
if (!empty($filter)) {
    if ($filter === 'weekly') {
        $sql .= " AND transaction_date >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
    } elseif ($filter === 'monthly') {
        $sql .= " AND transaction_date >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
    } elseif ($filter === 'yearly') {
        $sql .= " AND transaction_date >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
    }
}

// Order by transaction_date descending
$sql .= " ORDER BY transaction_date DESC";

// Prepare and execute the query

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions</title>
    <!-- ======= Styles ====== -->
    <link rel="stylesheet" href="/CSS/transaction.css">
</head>
<body>
  <?php require_once ('sidebar.php');?>
    <div class="container">
        <h1>Transactions Record</h1>

        <!-- Display Success and Error Messages -->
        <?php if (!empty($success)): ?>
            <div class="success-message">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="error-messages">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Search and Filter Section -->
        <div class="search-section">
            <form method="GET" action="transactions.php">
                <input type="text" class="search-bar" name="search" placeholder="Search" value="<?php echo htmlspecialchars($searchQuery); ?>">
                <button type="submit" class="search-button">Search</button>

                <select class="filter-dropdown" name="filter">
                    <option value="">-- Filter --</option>
                    <option value="weekly" <?php if ($filter === 'weekly') echo 'selected'; ?>>Weekly</option>
                    <option value="monthly" <?php if ($filter === 'monthly') echo 'selected'; ?>>Monthly</option>
                    <option value="yearly" <?php if ($filter === 'yearly') echo 'selected'; ?>>Yearly</option>
                </select>
            </form>
        </div>

        <!-- Transactions Table -->
        <table>
            <thead>
                <tr>
                    <th>Transaction #</th>
                    <th>Transaction Date</th>
                    <th>Order</th>
                    <th>Employee</th>
                    <th>Quantity</th>
                    <th>Item Price</th>
                    <th>Subtotal</th>
                    <th>Total</th>
                    <th>Pay Now</th>
                </tr>
            </thead>
            <tbody id="transaction-list-body">
                <?php if ($transactions): ?>
                    <?php foreach ($transactions as $transaction): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($transaction['transaction_number']); ?></td>
                            <td><?php echo htmlspecialchars($transaction['transaction_date']); ?></td>
                            <td><?php echo htmlspecialchars($transaction['order_details']); ?></td>
                            <td><?php echo htmlspecialchars($transaction['employee_name']); ?></td>
                            <td><?php echo htmlspecialchars($transaction['quantity']); ?></td>
                            <td>$<?php echo number_format($transaction['item_price'], 2); ?></td>
                            <td>$<?php echo number_format($transaction['subtotal'], 2); ?></td>
                            <td>$<?php echo number_format($transaction['total'], 2); ?></td>
                            <td>
                                <?php if ($transaction['total'] > 0): ?>
                                    <form action="pay_now.php" method="POST">
                                        <input type="hidden" name="transaction_id" value="<?php echo $transaction['id']; ?>">
                                        <button type="submit" class="pay-now-button">Pay Now</button>
                                    </form>
                                <?php else: ?>
                                    <button class="view-receipt" onclick="window.location.href='view_receipt.php?id=<?php echo $transaction['id']; ?>'">View Receipt</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9">No transactions found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- ======= Scripts ====== -->
    <script src="/JAVA/transaction.js"></script>
</body>
</html>
