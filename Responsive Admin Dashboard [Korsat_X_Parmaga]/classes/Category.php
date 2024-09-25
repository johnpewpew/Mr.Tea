<?php
// classes/Category.php

require_once 'config/_init.php';
class Category 
{
    public $id;
    public $name;

    public function __construct($category)
    {
        $this->id = $category['id'];
        $this->name = $category['name'];
    }

    public static function all($pdo)
    {
        $stmt = $pdo->prepare('SELECT * FROM categories');
        $stmt->execute();
        $categories = $stmt->fetchAll();
        return array_map(fn($cat) => new Category($cat), $categories);
    }

    public static function find($pdo, $id)
    {
        $stmt = $pdo->prepare('SELECT * FROM categories WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $category = $stmt->fetch();
        return $category ? new Category($category) : null;
    }
}

