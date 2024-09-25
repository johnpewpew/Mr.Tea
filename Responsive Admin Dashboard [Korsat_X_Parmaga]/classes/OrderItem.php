<?php
// classes/OrderItem.php
require_once 'config/_init.php';
class OrderItem 
{
    public $id;
    public $order_id;
    public $product_id;
    public $quantity;
    public $price;
    public $subtotal;

    public function __construct($orderItem)
    {
        $this->id = $orderItem['id'];
        $this->order_id = $orderItem['order_id'];
        $this->product_id = $orderItem['product_id'];
        $this->quantity = intval($orderItem['quantity']);
        $this->price = floatval($orderItem['price']);
        $this->subtotal = floatval($orderItem['subtotal']);
    }

    public function save($pdo)
    {
        $stmt = $pdo->prepare('INSERT INTO order_items (order_id, product_id, quantity, price, subtotal) VALUES (:order_id, :product_id, :quantity, :price, :subtotal)');
        $stmt->bindParam(':order_id', $this->order_id);
        $stmt->bindParam(':product_id', $this->product_id);
        $stmt->bindParam(':quantity', $this->quantity);
        $stmt->bindParam(':price', $this->price);
        $stmt->bindParam(':subtotal', $this->subtotal);
        $stmt->execute();
        $this->id = $pdo->lastInsertId();
    }

    public static function findByOrderId($pdo, $order_id)
    {
        $stmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id=:order_id');
        $stmt->execute([':order_id' => $order_id]);
        $items = $stmt->fetchAll();
        return array_map(fn($item) => new OrderItem($item), $items);
    }
}

