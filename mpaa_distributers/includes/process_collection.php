<?php
require_once 'includes/auth.php';
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'farmer_id' => $_POST['farmer_id'],
        'collection_date' => $_POST['collection_date'],
        'quantity' => $_POST['quantity'],
        'quality' => $_POST['quality'],
        'fat_content' => $_POST['fat_content'] ?? null,
        'price_per_liter' => $_POST['price_per_liter']
    ];
    
    if (addCollection($conn, $data)) {
        $_SESSION['success'] = "Collection recorded successfully!";
    } else {
        $_SESSION['error'] = "Failed to record collection.";
    }
    
    header("Location: reports.php");
    exit();
}