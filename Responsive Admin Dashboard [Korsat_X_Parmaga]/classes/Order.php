<?php
// classes/Order.php
require_once 'config/_init.php';
class Order 
{
    public $id;
    public $order_date;
    public $total_amount;
    public $payment_status;
    public $items = []; // Array of OrderItem objects

    public function __construct($order)
    {
        $this->id = $order['id'];
        $this->order_date = $order['order_date'];
        $this->total_amount = floatval($order['total_amount']);
        $this->payment_status = $order['payment_status'];
    }

    public function addItem($orderItem)
    {
        $this->items[] = $orderItem;
    }

    public function calculateTotal()
    {
        $this->total_amount = 0;
        foreach ($this->items as $item) {
            $this->total_amount += $item->subtotal;
        }
    }

    public function save($pdo)
    {
        // Insert Order
        $stmt = $pdo->prepare('INSERT INTO orders (order_date, total_amount, payment_status) VALUES (NOW(), :total_amount, :payment_status)');
        $stmt->bindParam(':total_amount', $this->total_amount);
        $stmt->bindParam(':payment_status', $this->payment_status);
        $stmt->execute();
        $this->id = $pdo->lastInsertId();

        // Insert Order Items
        foreach ($this->items as $item) {
            $item->order_id = $this->id;
            $item->save($pdo);
        }
    }

    public static function all($pdo)
    {
        $stmt = $pdo->prepare('SELECT * FROM orders');
        $stmt->execute();
        $orders = $stmt->fetchAll();
        $orderObjects = [];
        foreach ($orders as $order) {
            $orderObj = new Order($order);
            $orderObj->items = OrderItem::findByOrderId($pdo, $orderObj->id);
            $orderObjects[] = $orderObj;
        }
        return $orderObjects;
    }

    public static function find($pdo, $id)
    {
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE id=:id');
        $stmt->execute([':id' => $id]);
        $order = $stmt->fetch();
        if ($order) {
            $orderObj = new Order($order);
            $orderObj->items = OrderItem::findByOrderId($pdo, $orderObj->id);
            return $orderObj;
        }
        return null;
    }
}

