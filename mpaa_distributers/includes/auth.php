<?php
session_start();

// Simple authentication check for Mpaa Distributers
function isLoggedIn() {
    return isset($_SESSION['user']);
}

function requireAuth() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

// Single logout function
function logout() {
    session_destroy();
    header("Location: login.php");
    exit();
}
?>