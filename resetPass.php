<?php
session_start();
include 'db.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = trim($_POST['email']);
    $batch    = trim($_POST['batch']);
    $role     = $_POST['role'];
    $new_pass = trim($_POST['new_password']);

    // ১. চেক করা ইউজার তথ্য সঠিক কি না
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND batch = ? AND role = ?");
    $stmt->bind_param("sss", $email, $batch, $role);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        // তথ্য মিললে নতুন পাসওয়ার্ড হ্যাশ করে আপডেট করা
        $hashed_password = password_hash($new_pass, PASSWORD_DEFAULT);
        
        $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $update_stmt->bind_param("ss", $hashed_password, $email);

        if ($update_stmt->execute()) {
            $message = "<div class='alert success'>Password updated successfully! <a href='login.php'>Login now</a></div>";
        } else {
            $message = "<div class='alert error'>Failed to reset password. Please try again!</div>";
        }
        $update_stmt->close();
    } else {
        $message = "<div class='alert error'>Details didn't match our records!</div>";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - NoteHub</title>
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background: #f0f2f5; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .card { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); width: 100%; max-width: 420px; }
        h2 { text-align: center; color: #1a73e8; margin-top: 0; }
        p { text-align: center; font-size: 13px; color: #666; margin-bottom: 20px; }
        .form-group { margin-bottom: 14px; }
        label { display: block; margin-bottom: 5px; font-weight: 600; color: #333; font-size: 14px; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; }
        button { width: 100%; padding: 11px; background: #e11d48; color: white; border: none; border-radius: 4px; font-size: 15px; cursor: pointer; font-weight: 600; }
        button:hover { background: #be123c; }
        .alert { padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 14px; text-align: center; }
        .alert.success { background: #d4edda; color: #155724; }
        .alert.error { background: #f8d7da; color: #721c24; }
        .footer-text { text-align: center; margin-top: 15px; font-size: 14px; color: #666; }
        .footer-text a { color: #1a73e8; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>

<div class="card">
    <h2>Reset Password</h2>
    <p>Verify your account information to set a new password.</p>
    <?= $message; ?>
    
    <form action="reset_password.php" method="POST">
        <div class="form-group">
            <label>Registered Email</label>
            <input type="email" name="email" required placeholder="Enter your account email">
        </div>
        <div class="form-group">
            <label>Batch</label>
            <input type="text" name="batch" required placeholder="e.g. 2nd">
        </div>
        <div class="form-group">
            <label>Role</label>
            <select name="role">
                <option value="student">Student</option>
                <option value="cr">CR (Class Representative)</option>
            </select>
        </div>
        <div class="form-group">
            <label>New Password</label>
            <input type="password" name="new_password" required placeholder="Enter new strong password">
        </div>
        <button type="submit">Update Password</button>
    </form>
    
    <div class="footer-text">
        Remembered your password? <a href="login.php">Back to Login</a>
    </div>
</div>

</body>
</html>