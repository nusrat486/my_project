<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NoteHub - Academic Resource Sharing Portal</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #1f4037, #99f2c8);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            color: #333;
        }

        header {
            background: rgba(255, 255, 255, 0.95);
            padding: 15px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .logo {
            font-size: 24px;
            font-weight: 700;
            color: #1f4037;
            text-decoration: none;
            letter-spacing: 1px;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn {
            display: inline-block;
            padding: 10px 22px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .btn-login {
            background: transparent;
            color: #1f4037;
            border: 2px solid #1f4037;
        }

        .btn-login:hover {
            background: #1f4037;
            color: #ffffff;
        }

        .btn-register {
            background: #1f4037;
            color: #ffffff;
            border: 2px solid #1f4037;
        }

        .btn-register:hover {
            background: #163029;
            border-color: #163029;
        }

        .hero {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            text-align: center;
        }

        .hero-card {
            background: rgba(255, 255, 255, 0.96);
            padding: 45px 35px;
            border-radius: 12px;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .hero-card h1 {
            font-size: 34px;
            color: #1f4037;
            margin-bottom: 15px;
            line-height: 1.2;
        }

        .hero-card p {
            font-size: 16px;
            color: #555;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .hero-buttons {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        .hero-buttons .btn {
            padding: 12px 28px;
            font-size: 15px;
        }

        footer {
            text-align: center;
            padding: 15px;
            color: #ffffff;
            font-size: 13px;
            background: rgba(0, 0, 0, 0.2);
        }
    </style>
</head>
<body>

    <header>
        <a href="index.php" class="logo">📚 NoteHub</a>
        <div class="nav-links">
            <a href="login.php" class="btn btn-login">Login</a>
            <a href="register.php" class="btn btn-register">Register</a>
        </div>
    </header>

    <main class="hero">
        <div class="hero-card">
            <h1>All Lecture Notes in One Place</h1>
            <p>Access course materials, download lecture slides, and request missing class notes directly from your CR and classmates.</p>
            
            <div class="hero-buttons">
                <a href="login.php" class="btn btn-login">Login</a>
                <a href="register.php" class="btn btn-register">Create Account</a>
            </div>
        </div>
    </main>

    <footer>
        &copy; <?= date("Y"); ?> NoteHub Portal. All rights reserved.
    </footer>

</body>
</html>