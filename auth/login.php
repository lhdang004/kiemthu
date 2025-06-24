<?php
require_once '../config/init.php';
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $conn = $database->getConnection();

    if (!$conn) {
        $error = "Lỗi kết nối cơ sở dữ liệu";
    } else {
        $username = $_POST['username'];
        $password = $_POST['password'];

        $stmt = $conn->prepare("SELECT u.*, g.ho_ten FROM users u 
                               LEFT JOIN giaovien g ON u.ma_gv = g.ma_gv 
                               WHERE u.username = ? AND u.active = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        // Kiểm tra mật khẩu
        $valid_password = false;
        if ($user) {
            if ($user['role'] === 'teacher' && ($password === '1234' || password_verify($password, $user['password']))) {
                $valid_password = true;
            } elseif (
                ($user['role'] === 'admin' && $password === 'admin') ||
                ($user['role'] === 'accountant' && $password === 'ketoan')
            ) {
                $valid_password = true;
            }
        }

        if ($user && $valid_password) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['ma_gv'] = $user['ma_gv'];
            $_SESSION['ho_ten'] = $user['ho_ten'];

            header("Location: ../index.php");
            exit();
        } else {
            $error = "Tên đăng nhập hoặc mật khẩu không đúng";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Đăng nhập</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        html,
        body {
            height: 100%;
            margin: 0;
        }

        body {
            background-color: #f1f4f8;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        }

        .login-card {
            width: 100%;
            max-width: 400px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .login-card .card-header {
            background: linear-gradient(135deg, #2196f3, #00bcd4);
            color: white;
            padding: 1rem 1.5rem;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .login-card .card-body {
            padding: 2rem;
        }

        .form-group label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            display: block;
        }

        .form-control {
            border-radius: 8px;
            padding: 0.65rem 1rem;
            font-size: 0.95rem;
        }

        .btn-login {
            font-weight: 600;
            padding: 0.6rem;
            font-size: 1rem;
            border-radius: 8px;
        }

        .alert {
            font-size: 0.9rem;
            border-left: 4px solid #dc3545;
            border-radius: 6px;
            margin-bottom: 1.25rem;
        }

        .login-note {
            margin-top: 1.25rem;
            font-size: 0.85rem;
            color: #6c757d;
            text-align: center;
        }

        @media (max-width: 576px) {
            .login-card .card-body {
                padding: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <div class="login-card">
        <div class="card-header">
            <i class="fas fa-user-lock mr-2"></i> Đăng nhập hệ thống
        </div>
        <div class="card-body">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" novalidate>
                <div class="form-group">
                    <label for="username"><i class="fas fa-user mr-1"></i> Tên đăng nhập</label>
                    <input type="text" name="username" id="username" class="form-control"
                        placeholder="Nhập tên đăng nhập" required>
                </div>

                <div class="form-group">
                    <label for="password"><i class="fas fa-key mr-1"></i> Mật khẩu</label>
                    <input type="password" name="password" id="password" class="form-control"
                        placeholder="Nhập mật khẩu" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-login">
                    <i class="fas fa-sign-in-alt mr-2"></i> Đăng nhập
                </button>
            </form>

            <div class="login-note">
                <i class="fas fa-info-circle mr-1"></i>
                <?php if (isset($_GET['role']) && $_GET['role'] === 'admin'): ?>
                    Tài khoản admin:<br>
                    - Username: <strong>admin</strong><br>
                    - Password: <strong>admin</strong>
                <?php elseif (isset($_GET['role']) && $_GET['role'] === 'accountant'): ?>
                    Tài khoản kế toán:<br>
                    - Username: <strong>ketoan</strong><br>
                    - Password: <strong>ketoan</strong>
                <?php else: ?>
                    Giáo viên đăng nhập với:<br>
                    - Tên đăng nhập: <strong>[họ tên không dấu]@teacher.edu.vn</strong><br>
                    - Mật khẩu mặc định: <strong>1234</strong><br>
                    <small>VD: Nguyễn Văn A -> nguyenvana@teacher.edu.vn</small>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>

</html>