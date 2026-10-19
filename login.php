<?php
session_start();
require 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Kredensial tidak valid! Periksa kembali username dan password.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Manajemen Inventaris</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
            background-color: #f4f7f6; 
            margin: 0;
        }
        .login-box { 
            background: #fff; 
            padding: 30px; 
            border-radius: 8px; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.1); 
            width: 100%;
            max-width: 350px;
        }
        .login-box h2 { 
            text-align: center; 
            margin-bottom: 20px; 
            color: #333;
        }
        .form-group { 
            margin-bottom: 15px; 
        }
        .form-group label { 
            display: block; 
            margin-bottom: 5px; 
            color: #666;
        }
        .form-group input[type="text"] { 
            width: 100%; 
            padding: 10px; 
            border: 1px solid #ccc; 
            border-radius: 4px; 
            box-sizing: border-box; 
        }
        
        /* CSS untuk ikon mata standar */
        .password-container {
            position: relative;
            display: flex;
            align-items: center;
        }
        .password-container input {
            width: 100%;
            padding: 10px;
            padding-right: 40px; 
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .toggle-password {
            position: absolute;
            right: 10px;
            cursor: pointer;
            color: #888;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .toggle-password:hover {
            color: #333;
        }

        button { 
            width: 100%; 
            padding: 10px; 
            margin-top: 10px;
            background-color: #0d6efd; 
            color: white; 
            border: none; 
            border-radius: 4px; 
            font-size: 16px;
            cursor: pointer; 
        }
        button:hover {
            background-color: #0b5ed7;
        }
        .error-msg { 
            color: #dc3545; 
            background-color: #f8d7da; 
            padding: 10px; 
            border-radius: 4px; 
            margin-bottom: 15px; 
            font-size: 14px; 
            text-align: center; 
        }
        .account-hint {
            margin-top: 20px;
            font-size: 12px;
            color: #777;
            text-align: center;
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
        }
    </style>
</head>
<body>

    <div class="login-box">
        <h2>Sistem Inventaris</h2>
        
        <?php if ($error): ?>
            <div class="error-msg"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autocomplete="off">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-container">
                    <input type="password" id="password" name="password" required>
                    
                    <span class="toggle-password" onclick="toggleVisibility()" title="Lihat/Sembunyikan Password">
                        <!-- Ikon Mata Terbuka (SVG) -->
                        <svg id="eye-open" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        <!-- Ikon Mata Tertutup/Tercoret (SVG) -->
                        <svg id="eye-closed" style="display: none;" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                            <line x1="1" y1="1" x2="23" y2="23"></line>
                        </svg>
                    </span>
                    
                </div>
            </div>
            
            <button type="submit">Login</button>
        </form>

        <div class="account-hint">
            <strong>Akun Pengujian:</strong><br>
            Admin: <code>admin_fazri</code> | Pass: <code>password</code><br>
            Staff: <code>staff_satu</code> | Pass: <code>password</code>
        </div>
    </div>

    <script>
        function toggleVisibility() {
            const passwordInput = document.getElementById('password');
            const eyeOpen = document.getElementById('eye-open');
            const eyeClosed = document.getElementById('eye-closed');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeOpen.style.display = 'none';
                eyeClosed.style.display = 'block';
            } else {
                passwordInput.type = 'password';
                eyeOpen.style.display = 'block';
                eyeClosed.style.display = 'none';
            }
        }
    </script>
</body>
</html>