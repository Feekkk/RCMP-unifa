<?php

$DB_HOST = '127.0.0.1';
$DB_PORT = '3306';
$DB_NAME = 'your_database_name';       
$DB_USER = 'your_username';           
$DB_PASS = 'your_password';               
$DB_CHARSET = 'utf8mb4';

$dsn = "mysql:host=$DB_HOST;port=$DB_PORT;dbname=$DB_NAME;charset=$DB_CHARSET";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
} catch (PDOException $e) {
    exit('Database connection failed.');
}