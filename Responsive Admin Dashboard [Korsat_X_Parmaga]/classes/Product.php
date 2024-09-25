<?php
// classes/Product.php

require_once 'config/_init.php';

require_once __DIR__.'/Category.php';
class Product 
{
    public $id;
    public $name;
    public $category_id;
    public $quantity;
    public $price;
    public $category;

    public function __construct($product, $pdo)
    {
        $this->id = $product['id'];
        $this->name = $product['name'];
        $this->category_id = $product['category_id'];
        $this->quantity = intval($product['quantity']);
        $this->price = floatval($product['price']);
        $this->category = Category::find($pdo, $this->category_id);
    }

    public function update($pdo)
    {
        $stmt = $pdo->prepare('UPDATE products SET name=:name, category_id=:category_id, quantity=:quantity, price=:price WHERE id=:id');
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':category_id', $this->category_id);
        $stmt->bindParam(':quantity', $this->quantity);
        $stmt->bindParam(':price', $this->price);
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();
    }

    public function delete($pdo)
    {
        $stmt = $pdo->prepare('DELETE FROM products WHERE id=:id');
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();
    }

    public static function all($pdo)
    {
        $stmt = $pdo->prepare('SELECT * FROM products');
        $stmt->execute();
        $products = $stmt->fetchAll();
        return array_map(fn($prod) => new Product($prod, $pdo), $products);
    }

    public static function find($pdo, $id)
    {
        $stmt = $pdo->prepare('SELECT * FROM products WHERE id=:id');
        $stmt->execute([':id' => $id]);
        $product = $stmt->fetch();
        return $product ? new Product($product, $pdo) : null;
    }

    public static function add($pdo, $name, $category_id, $quantity, $price)
    {
        $stmt = $pdo->prepare('INSERT INTO products (name, category_id, quantity, price) VALUES (:name, :category_id, :quantity, :price)');
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':category_id', $category_id);
        $stmt->bindParam(':quantity', $quantity);
        $stmt->bindParam(':price', $price);
        $stmt->execute();
        return self::find($pdo, $pdo->lastInsertId());
    }
}

