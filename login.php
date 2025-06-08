<?php
session_start();
require_once 'config.php';

$error = '';
$success = '';

// Xử lý đăng nhập
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            header('Location: index.php');
            exit();
        } else {
            $error = 'Email hoặc mật khẩu không đúng';
        }
    } catch(PDOException $e) {
        $error = "Lỗi: " . $e->getMessage();
    }
}

// Xử lý đăng ký
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Kiểm tra email đã tồn tại
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        $error = 'Email đã được sử dụng';
    } 
    // Kiểm tra mật khẩu
    elseif ($password !== $confirm_password) {
        $error = 'Mật khẩu xác nhận không khớp';
    } else {
        // Mã hóa mật khẩu và lưu user
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        if ($stmt->execute([$name, $email, $hashed_password])) {
            $success = 'Đăng ký thành công! Vui lòng đăng nhập.';
        } else {
            $error = 'Có lỗi xảy ra, vui lòng thử lại';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Nhập - Music Shop</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="logo">
                <h1>Music Shop</h1>
            </div>
            <ul class="nav-links">
                <li><a href="index.php">Trang Chủ</a></li>
                <li><a href="index.php#san-pham">Sản Phẩm</a></li>
                <li><a href="index.php#ve-chung-toi">Về Chúng Tôi</a></li>
                <li><a href="index.php#lien-he">Liên Hệ</a></li>
            </ul>
        </nav>
    </header>

    <main class="auth-container">
        <div class="auth-box">
            <div class="auth-tabs">
                <button class="auth-tab active" onclick="showTab('login')">Đăng Nhập</button>
                <button class="auth-tab" onclick="showTab('register')">Đăng Ký</button>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <div id="login-form" class="auth-form active">
                <h2>Đăng Nhập</h2>
                <form method="POST" action="">
                    <div class="form-group">
                        <input type="email" name="email" placeholder="Email" required>
                    </div>
                    <div class="form-group">
                        <input type="password" name="password" placeholder="Mật khẩu" required>
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="remember"> Ghi nhớ đăng nhập
                        </label>
                    </div>
                    <button type="submit" name="login" class="auth-btn">Đăng Nhập</button>
                </form>
                <p class="auth-link">
                    <a href="forgot-password.php">Quên mật khẩu?</a>
                </p>
            </div>

            <div id="register-form" class="auth-form">
                <h2>Đăng Ký</h2>
                <form method="POST" action="">
                    <div class="form-group">
                        <input type="text" name="name" placeholder="Họ và tên" required>
                    </div>
                    <div class="form-group">
                        <input type="email" name="email" placeholder="Email" required>
                    </div>
                    <div class="form-group">
                        <input type="password" name="password" placeholder="Mật khẩu" required>
                    </div>
                    <div class="form-group">
                        <input type="password" name="confirm_password" placeholder="Xác nhận mật khẩu" required>
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" required> Tôi đồng ý với điều khoản sử dụng
                        </label>
                    </div>
                    <button type="submit" name="register" class="auth-btn">Đăng Ký</button>
                </form>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; 2024 Music Shop. All rights reserved.</p>
    </footer>

    <script>
        function showTab(tabName) {
            document.querySelectorAll('.auth-form').forEach(form => {
                form.classList.remove('active');
            });
            
            document.querySelectorAll('.auth-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            document.getElementById(tabName + '-form').classList.add('active');
            event.target.classList.add('active');
        }
    </script>
</body>
</html> 