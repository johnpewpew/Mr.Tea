-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 27, 2024 at 10:54 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

-- Database: `database_pos`


CREATE TABLE `users`(
  `id` INT(10) NOT NULL AUTO_INCREMENT , 
  `name` VARCHAR(50) NOT NULL , 
  `email` VARCHAR(50) NOT NULL , 
  `password` VARCHAR(50) NOT NULL , 
  `user_type` VARCHAR(50) NOT NULL DEFAULT 'user' , PRIMARY KEY (`id`)
) ENGINE = InnoDB;



INSERT INTO `users` (`id`, `name`, `email`, `password`, `user_type`) VALUES 
(NULL, 'admin', 'admin@gmail.com', '0192023a7bbd73250516f069df18b500', 'admin'),
(NULL, 'cashier', 'cashier@gmail.com', '84c8137f06fd53b0636e0818f3954cdb', 'user');