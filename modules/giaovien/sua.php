<?php
require_once '../../config/database.php';
require_once '../header.php';

$database = new Database();
$conn = $database->getConnection();

$id = isset($_GET['id']) ? $_GET['id'] : die('Lỗi: Không tìm thấy ID');

// Lấy thông tin giảng viên
$stmt = $conn->prepare("SELECT * FROM giaovien WHERE ma_gv = ?");
$stmt->execute([$id]);
$giaovien = $stmt->fetch(PDO::FETCH_ASSOC);

// Lấy danh sách khoa và bằng cấp
$stmt_khoa = $conn->query("SELECT * FROM khoa");
$khoas = $stmt_khoa->fetchAll(PDO::FETCH_ASSOC);

$stmt_bangcap = $conn->query("SELECT * FROM bangcap");
$bangcaps = $stmt_bangcap->fetchAll(PDO::FETCH_ASSOC);

// Get user account info
$stmt = $conn->prepare("SELECT username, password FROM users WHERE ma_gv = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $conn->beginTransaction();

        $sql = "UPDATE giaovien SET 
                ho_ten = :ho_ten,
                gioi_tinh = :gioi_tinh,
                ngay_sinh = :ngay_sinh,
                dia_chi = :dia_chi,
                email = :email,
                so_dien_thoai = :so_dien_thoai,
                ma_khoa = :ma_khoa,
                ma_bangcap = :ma_bangcap,
                ngay_vao_lam = :ngay_vao_lam
                WHERE ma_gv = :ma_gv";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':ho_ten' => $_POST['ho_ten'],
            ':gioi_tinh' => $_POST['gioi_tinh'],
            ':ngay_sinh' => $_POST['ngay_sinh'],
            ':dia_chi' => $_POST['dia_chi'],
            ':email' => $_POST['email'],
            ':so_dien_thoai' => $_POST['so_dien_thoai'],
            ':ma_khoa' => $_POST['ma_khoa'],
            ':ma_bangcap' => $_POST['ma_bangcap'],
            ':ngay_vao_lam' => $_POST['ngay_vao_lam'],
            ':ma_gv' => $id
        ]);

        // Update user account
        if (!empty($_POST['username']) && !empty($_POST['password'])) {
            $sql = "UPDATE users SET username = :username, 
                    password = :password WHERE ma_gv = :ma_gv";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':username' => $_POST['username'],
                ':password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
                ':ma_gv' => $id
            ]);
        }

        $conn->commit();

        header("Location: index.php");
        exit();
    } catch (PDOException $e) {
        $conn->rollBack();
        $error = "Lỗi: " . $e->getMessage();
    }
}

echo getHeader("Sửa Thông tin giảng viên");
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Sửa giảng viên</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <!-- Header Navigation nếu cần -->
    <!-- ...existing code... -->

    <div class="container mt-4">
        <div class="card shadow-lg">
            <div class="card-header bg-gradient-info">
                <h5 class="mb-0">
                    <i class="fas fa-user-edit mr-2"></i>
                    Sửa thông tin giảng viên
                </h5>
            </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <?= htmlspecialchars($error) ?>
                        <button type="button" class="close" data-dismiss="alert">
                            <span>&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <form method="POST" class="needs-validation" novalidate>
                    <h6 class="text-primary mb-3">
                        <i class="fas fa-user mr-2"></i>
                        Thông tin cá nhân
                    </h6>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="ho_ten">
                                <i class="fas fa-signature mr-1"></i>
                                Họ và tên <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="ho_ten" name="ho_ten" class="form-control"
                                value="<?= htmlspecialchars($giaovien['ho_ten']) ?>" required>
                            <div class="invalid-feedback">Vui lòng nhập họ và tên</div>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="gioi_tinh">
                                <i class="fas fa-venus-mars mr-1"></i>
                                Giới tính <span class="text-danger">*</span>
                            </label>
                            <select name="gioi_tinh" id="gioi_tinh" class="form-control" required>
                                <option value="">-- Chọn giới tính --</option>
                                <option value="Nam" <?= $giaovien['gioi_tinh']=='Nam'?'selected':'' ?>>Nam</option>
                                <option value="Nữ" <?= $giaovien['gioi_tinh']=='Nữ'?'selected':'' ?>>Nữ</option>
                            </select>
                            <div class="invalid-feedback">Vui lòng chọn giới tính</div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="ngay_sinh">
                                <i class="fas fa-birthday-cake mr-1"></i>
                                Ngày sinh <span class="text-danger">*</span>
                            </label>
                            <input type="date" id="ngay_sinh" name="ngay_sinh" class="form-control"
                                value="<?= htmlspecialchars($giaovien['ngay_sinh']) ?>"
                                max="<?= date('Y-m-d', strtotime('-18 years')) ?>" required>
                            <div class="invalid-feedback">Vui lòng chọn ngày sinh hợp lệ</div>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="ngay_vao_lam">
                                <i class="fas fa-calendar-plus mr-1"></i>
                                Ngày vào làm <span class="text-danger">*</span>
                            </label>
                            <input type="date" id="ngay_vao_lam" name="ngay_vao_lam" class="form-control"
                                value="<?= htmlspecialchars($giaovien['ngay_vao_lam']) ?>"
                                max="<?= date('Y-m-d') ?>" required>
                            <div class="invalid-feedback">Vui lòng chọn ngày vào làm</div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="dia_chi">
                            <i class="fas fa-map-marker-alt mr-1"></i>
                            Địa chỉ
                        </label>
                        <textarea name="dia_chi" id="dia_chi" class="form-control" rows="3"><?= htmlspecialchars($giaovien['dia_chi']) ?></textarea>
                    </div>

                    <h6 class="text-primary mb-3 mt-4">
                        <i class="fas fa-address-book mr-2"></i>
                        Thông tin liên hệ
                    </h6>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="email">
                                <i class="fas fa-envelope mr-1"></i>
                                Email <span class="text-danger">*</span>
                            </label>
                            <input type="email" id="email" name="email" class="form-control"
                                value="<?= htmlspecialchars($giaovien['email']) ?>" required>
                            <div class="invalid-feedback">Vui lòng nhập email hợp lệ</div>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="so_dien_thoai">
                                <i class="fas fa-phone mr-1"></i>
                                Số điện thoại
                            </label>
                            <input type="tel" id="so_dien_thoai" name="so_dien_thoai" class="form-control"
                                value="<?= htmlspecialchars($giaovien['so_dien_thoai']) ?>"
                                pattern="[0-9]{10,11}">
                            <div class="invalid-feedback">Số điện thoại không hợp lệ</div>
                        </div>
                    </div>

                    <h6 class="text-primary mb-3 mt-4">
                        <i class="fas fa-briefcase mr-2"></i>
                        Thông tin công việc
                    </h6>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="ma_khoa">
                                <i class="fas fa-building mr-1"></i>
                                Khoa <span class="text-danger">*</span>
                            </label>
                            <select name="ma_khoa" id="ma_khoa" class="form-control" required>
                                <option value="">-- Chọn khoa --</option>
                                <?php foreach ($khoas as $khoa): ?>
                                    <option value="<?= htmlspecialchars($khoa['ma_khoa']) ?>" <?= $giaovien['ma_khoa']==$khoa['ma_khoa']?'selected':'' ?>>
                                        <?= htmlspecialchars($khoa['ten_khoa']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Vui lòng chọn khoa</div>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="ma_bangcap">
                                <i class="fas fa-graduation-cap mr-1"></i>
                                Bằng cấp <span class="text-danger">*</span>
                            </label>
                            <select name="ma_bangcap" id="ma_bangcap" class="form-control" required>
                                <option value="">-- Chọn bằng cấp --</option>
                                <?php foreach ($bangcaps as $bangcap): ?>
                                    <option value="<?= htmlspecialchars($bangcap['ma_bangcap']) ?>" <?= $giaovien['ma_bangcap']==$bangcap['ma_bangcap']?'selected':'' ?>>
                                        <?= htmlspecialchars($bangcap['ten_bangcap']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Vui lòng chọn bằng cấp</div>
                        </div>
                    </div>

                    <div class="form-group mt-4 text-right">
                        <a href="index.php" class="btn btn-secondary mr-2">
                            <i class="fas fa-times mr-1"></i>
                            Hủy bỏ
                        </a>
                        <button type="reset" class="btn btn-outline-secondary mr-2">
                            <i class="fas fa-undo mr-1"></i>
                            Làm mới
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i>
                            Lưu thay đổi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Bootstrap form validation
        (function () {
            'use strict';
            window.addEventListener('load', function () {
                var forms = document.getElementsByClassName('needs-validation');
                var validation = Array.prototype.filter.call(forms, function (form) {
                    form.addEventListener('submit', function (event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();

        // Auto-format phone number
        document.getElementById('so_dien_thoai').addEventListener('input', function (e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) value = value.slice(0, 11);
            e.target.value = value;
        });
    </script>
</body>
</html>