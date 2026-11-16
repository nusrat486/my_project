<?php
include 'db.php';
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $batch    = trim($_POST['batch']);
    $role     = $_POST['role'];

    // ইমেইল আগে থেকেই আছে কি না চেক করা
    $check_email = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check_email->bind_param("s", $email);
    $check_email->execute();
    $check_email->store_result();

    if ($check_email->num_rows > 0) {
        $message = "<div class='alert error'>Email already registered!</div>";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, batch, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $name, $email, $password, $batch, $role);

        if ($stmt->execute()) {
            $message = "<div class='alert success'>Registration successful! <a href='login.php'>Login here</a></div>";
        } else {
            $message = "<div class='alert error'>Something went wrong. Please try again!</div>";
        }
        $stmt->close();
    }
    $check_email->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - NoteHub</title>
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background: #f0f2f5; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .card { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        h2 { text-align: center; color: #1a73e8; margin-top: 0; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: 600; color: #333; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; }
        button { width: 100%; padding: 10px; background: #1a73e8; color: white; border: none; border-radius: 4px; font-size: 16px; cursor: pointer; font-weight: 600; }
        button:hover { background: #1557b0; }
        .alert { padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 14px; text-align: center; }
        .alert.success { background: #d4edda; color: #155724; }
        .alert.error { background: #f8d7da; color: #721c24; }
        .footer-text { text-align: center; margin-top: 15px; font-size: 14px; color: #666; }
        .footer-text a { color: #1a73e8; text-decoration: none; }
    </style>
</head>
<body>

<div class="card">
    <h2>NoteHub Register</h2>
    <?= $message; ?>
    <form action="register.php" method="POST">
        <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="name" required placeholder="e.g. Nusrat Jahan">
        </div>
        <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" required placeholder="e.g. student@gmail.com">
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required placeholder="Enter strong password">
        </div>
        <div class="form-group">
            <label>Batch</label>
            <input type="text" name="batch" required placeholder="e.g. 52nd">
        </div>
        <div class="form-group">
            <label>Select Role</label>
            <select name="role">
                <option value="student">Student</option>
                <option value="cr">CR (Class Representative)</option>
            </select>
        </div>
        <button type="submit">Create Account</button>
    </form>
    <div class="footer-text">
        Already have an account? <a href="login.php">Login here</a>
    </div>
</div>

</body>
</html>