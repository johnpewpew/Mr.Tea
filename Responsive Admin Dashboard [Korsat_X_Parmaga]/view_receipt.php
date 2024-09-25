<?php
// view_receipt.php

// Include the database configuration file
require_once 'config/transactiondata.php';

// Check if 'id' is present in GET parameters
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "Invalid Transaction ID.";
    exit();
}

$transactionId = intval($_GET['id']);

// Fetch the transaction details
$sql = "SELECT * FROM transactions WHERE id = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute([':id' => $transactionId]);
$transaction = $stmt->fetch();

if (!$transaction) {
    echo "Transaction not found.";
    exit();
}

// Optionally, handle displaying the receipt file if exists
$receiptExists = false;
$receiptPath = '';
if ($transaction['receipt_path'] && file_exists($transaction['receipt_path'])) {
    $receiptExists = true;
    $receiptPath = $transaction['receipt_path'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Receipt</title>
    <!-- ======= Styles ====== -->
    <link rel="stylesheet" href="/CSS/transaction.css">
</head>
<body>

    <div class="container">
        <h1>Transaction Receipt</h1>

        <div class="receipt-details">
            <p><strong>Transaction #:</strong> <?php echo htmlspecialchars($transaction['transaction_number']); ?></p>
            <p><strong>Transaction Date:</strong> <?php echo htmlspecialchars($transaction['transaction_date']); ?></p>
            <p><strong>Order Details:</strong> <?php echo htmlspecialchars($transaction['order_details']); ?></p>
            <p><strong>Employee Name:</strong> <?php echo htmlspecialchars($transaction['employee_name']); ?></p>
            <p><strong>Quantity:</strong> <?php echo htmlspecialchars($transaction['quantity']); ?></p>
            <p><strong>Item Price:</strong> $<?php echo number_format($transaction['item_price'], 2); ?></p>
            <p><strong>Subtotal:</strong> $<?php echo number_format($transaction['subtotal'], 2); ?></p>
            <p><strong>Total:</strong> $<?php echo number_format($transaction['total'], 2); ?></p>
        </div>

        <?php if ($receiptExists): ?>
            <div class="receipt-file">
                <h2>Receipt File</h2>
                <?php
                $fileExtension = strtolower(pathinfo($receiptPath, PATHINFO_EXTENSION));
                if (in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif'])) {
                    echo '<img src="' . htmlspecialchars($receiptPath) . '" alt="Receipt Image" class="receipt-image">';
                } elseif ($fileExtension === 'pdf') {
                    echo '<a href="' . htmlspecialchars($receiptPath) . '" target="_blank">Download Receipt PDF</a>';
                } else {
                    echo '<p>Unsupported receipt format.</p>';
                }
                ?>
            </div>
        <?php else: ?>
            <p>No receipt file available for this transaction.</p>
        <?php endif; ?>

        <button onclick="window.history.back();" class="back-button">Back</button>
    </div>

    <!-- ======= Scripts ====== -->
    <script src="/JAVA/transaction.js"></script>
</body>
</html>
