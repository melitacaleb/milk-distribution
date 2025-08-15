<?php
session_start();
require_once 'includes/db_connect.php';

if(isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // Single user for Mpaa Distributers
    if($username === 'mpaa_admin' && $password === 'mpaa@2023') {
        $_SESSION['user'] = 'System Admin';
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid credentials";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Mpaa Distributers - Login</title>
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <h2>Mpaa Distributers</h2>
        <form method="POST">
            <?php if(isset($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="login">Login</button>
        </form>
    </div>
</body>
</html>