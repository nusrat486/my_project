<?php
session_start();
include 'db.php';
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT id, name, password, batch, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Password verify kora
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name']    = $user['name'];
            $_SESSION['batch']   = $user['batch'];
            $_SESSION['role']    = $user['role'];

            // Dashboard-e pathiye dewa
            header("Location: dashboard.php");
            exit();
        } else {
            $message = "<div class='alert error'>Invalid password!</div>";
        }
    } else {
        $message = "<div class='alert error'>No account found with this email!</div>";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - NoteHub</title>
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background: #f0f2f5; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .card { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        h2 { text-align: center; color: #1a73e8; margin-top: 0; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: 600; color: #333; }
        input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; }
        button { width: 100%; padding: 10px; background: #1a73e8; color: white; border: none; border-radius: 4px; font-size: 16px; cursor: pointer; font-weight: 600; }
        button:hover { background: #1557b0; }
        .alert { padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 14px; text-align: center; }
        .alert.error { background: #f8d7da; color: #721c24; }
        .footer-text { text-align: center; margin-top: 15px; font-size: 14px; color: #666; }
        .footer-text a { color: #1a73e8; text-decoration: none; }
    </style>
</head>
<body>

<div class="card">
    <h2>NoteHub Login</h2>
    <?= $message; ?>
    <form action="login.php" method="POST">
        <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" required placeholder="Enter your email">
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required placeholder="Enter password">
        </div>
        <button type="submit">Login</button>
    </form>
    <div style="text-align: right; margin-bottom: 15px;">
    <a href="reset_password.php" style="color: #e11d48; text-decoration: none; font-size: 13px;">Forgot Password?</a>
</div>
    <div class="footer-text">
        Don't have an account? <a href="register.php">Register here</a>
    </div>
</div>

</body>
</html>