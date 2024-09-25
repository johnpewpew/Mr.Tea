<?php


// config/_init.php

session_start();

// Database connection
require_once 'db.php';
require_once '_config.php';
require_once'_helper.php';

// Include all necessary classes
require_once __DIR__ . '/../classes/Category.php';
require_once __DIR__ . '/../classes/Product.php';
require_once __DIR__ . '/../classes/Order.php';
require_once __DIR__ . '/../classes/OrderItem.php';

try{
    $connection = new PDO("mysql:host=".DB_HOST.";dbname=".DB_DATABASE, DB_USERNAME, DB_PASSWORD);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {

    header('Content-type: text/plain');

    die("
        Error: Failed to connect to database
        Reason: {$e->getMessage()}
        Note: 
            - Try to open config.php and check if the mysql is configured correctly.
            - Make sure that the mysql server is running.
    ");
}


