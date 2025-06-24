<?php
require_once '../../config/database.php';

$database = new Database();
$conn = $database->getConnection();

$stmt_khoa = $conn->query("SELECT * FROM khoa");
$khoas = $stmt_khoa->fetchAll(PDO::FETCH_ASSOC);

$stmt_bangcap = $conn->query("SELECT * FROM bangcap");
$bangcaps = $stmt_bangcap->fetchAll(PDO::FETCH_ASSOC);

function taoMaGV($conn)
{
    // Lấy tất cả mã giáo viên hiện có và tìm số lớn nhất
    $stmt = $conn->query("SELECT ma_gv FROM giaovien");
    $max = 0;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Chấp nhận cả GV001, GV0001, GV0002, ...
        if (preg_match('/^GV0*(\d+)$/', $row['ma_gv'], $m)) {
            $num = intval($m[1]);
            if ($num > $max)
                $max = $num;
        }
    }
    // Lặp để chắc chắn mã chưa tồn tại (tránh trường hợp bị xóa rồi thêm lại)
    do {
        $max++;
        $newCode = 'GV' . str_pad($max, 4, '0', STR_PAD_LEFT);
        $check = $conn->prepare("SELECT 1 FROM giaovien WHERE ma_gv = ?");
        $check->execute([$newCode]);
        $exists = $check->fetchColumn();
    } while ($exists);
    return $newCode;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Bắt buộc điền đầy đủ thông tin
        $required = [
            'ho_ten',
            'gioi_tinh',
            'ngay_sinh',
            'ngay_vao_lam',
            'email',
            'ma_khoa',
            'ma_bangcap'
        ];
        foreach ($required as $field) {
            if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
                throw new Exception("Hãy điền đầy đủ thông tin!");
            }
        }

        // Kiểm tra trùng email
        $stmtCheck = $conn->prepare("SELECT COUNT(*) FROM giaovien WHERE LOWER(email) = LOWER(?)");
        $stmtCheck->execute([trim($_POST['email'])]);
        if ($stmtCheck->fetchColumn() > 0) {
            throw new Exception("Email đã tồn tại !");
        }

        $conn->beginTransaction();

        $ma_gv = taoMaGV($conn);
        $sql = "INSERT INTO giaovien (ma_gv, ho_ten, gioi_tinh, ngay_sinh, dia_chi, email, 
                so_dien_thoai, ma_khoa, ma_bangcap, ngay_vao_lam) 
                VALUES (:ma_gv, :ho_ten, :gioi_tinh, :ngay_sinh, :dia_chi, :email, 
                :so_dien_thoai, :ma_khoa, :ma_bangcap, :ngay_vao_lam)";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':ma_gv' => $ma_gv,
            ':ho_ten' => $_POST['ho_ten'],
            ':gioi_tinh' => $_POST['gioi_tinh'],
            ':ngay_sinh' => $_POST['ngay_sinh'],
            ':dia_chi' => $_POST['dia_chi'],
            ':email' => $_POST['email'],
            ':so_dien_thoai' => $_POST['so_dien_thoai'],
            ':ma_khoa' => $_POST['ma_khoa'],
            ':ma_bangcap' => $_POST['ma_bangcap'],
            ':ngay_vao_lam' => $_POST['ngay_vao_lam']
        ]);

        $conn->commit();
        header("Location: index.php");
        exit();
    } catch (Exception $e) {
        if ($conn->inTransaction())
            $conn->rollBack();
        $error = $e->getMessage();
    } catch (PDOException $e) {
        $conn->rollBack();
        // Kiểm tra lỗi trùng email
        if ($e->getCode() == '23000' && strpos($e->getMessage(), 'email') !== false) {
            $error = "Email đã tồn tại !";
        } else {
            $error = "Lỗi: " . $e->getMessage();
        }
    }
}

function vn_to_str($str)
{
    $unicode = [
        'a' => 'á|à|ả|ã|ạ|ă|ắ|ằ|ẳ|ẵ|ặ|â|ấ|ầ|ẩ|ẫ|ậ',
        'A' => 'Á|À|Ả|Ã|Ạ|Ă|Ắ|Ằ|Ẳ|Ẵ|Ặ|Â|Ấ|Ầ|Ẩ|Ẫ|Ậ',
        'd' => 'đ',
        'D' => 'Đ',
        'e' => 'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ',
        'E' => 'É|È|Ẻ|Ẽ|Ẹ|Ê|Ế|Ề|Ể|Ễ|Ệ',
        'i' => 'í|ì|ỉ|ĩ|ị',
        'I' => 'Í|Ì|Ỉ|Ĩ|Ị',
        'o' => 'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ',
        'O' => 'Ó|Ò|Ỏ|Õ|Ọ|Ô|Ố|Ồ|Ổ|Ỗ|Ộ|Ơ|Ớ|Ờ|Ở|Ỡ|Ợ',
        'u' => 'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự',
        'U' => 'Ú|Ù|Ủ|Ũ|Ụ|Ư|Ứ|Ừ|Ử|Ữ|Ự',
        'y' => 'ý|ỳ|ỷ|ỹ|ỵ',
        'Y' => 'Ý|Ỳ|Ỷ|Ỹ|Ỵ'
    ];
    foreach ($unicode as $ascii => $uni)
        $str = preg_replace("/($uni)/i", $ascii, $str);
    return str_replace(' ', '', strtolower($str));
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm GIẢNG VIÊN - Hệ thống Quản lý</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body>
    <!-- Header Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="../../index.php">
                <i class="fas fa-graduation-cap mr-2"></i>
                Hệ thống Quản lý
            </a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-users mr-1"></i>
                            Danh sách GIẢNG VIÊN
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../../dashboard.php">
                            <i class="fas fa-tachometer-alt mr-1"></i>
                            Dashboard
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-4">
        <!-- Card Header with Icon -->
        <div class="card shadow-lg">
            <div class="card-header bg-gradient-info">
                <h5 class="mb-0">
                    <i class="fas fa-user-plus mr-2"></i>
                    Thêm GIẢNG VIÊN Mới
                </h5>
            </div>

            <div class="card-body">
                <!-- Alert for Errors -->
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <?= htmlspecialchars($error) ?>
                        <button type="button" class="close" data-dismiss="alert">
                            <span>&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Form -->
                <form method="POST" class="needs-validation" novalidate>
                    <!-- Thông tin cá nhân -->
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
                                placeholder="Nhập họ và tên đầy đủ" required>
                            <div class="invalid-feedback">
                                Vui lòng nhập họ và tên
                            </div>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="gioi_tinh">
                                <i class="fas fa-venus-mars mr-1"></i>
                                Giới tính <span class="text-danger">*</span>
                            </label>
                            <select name="gioi_tinh" id="gioi_tinh" class="form-control" required>
                                <option value="">-- Chọn giới tính --</option>
                                <option value="Nam">Nam</option>
                                <option value="Nữ">Nữ</option>
                            </select>
                            <div class="invalid-feedback">
                                Vui lòng chọn giới tính
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="ngay_sinh">
                                <i class="fas fa-birthday-cake mr-1"></i>
                                Ngày sinh <span class="text-danger">*</span>
                            </label>
                            <input type="date" id="ngay_sinh" name="ngay_sinh" class="form-control"
                                max="<?= date('Y-m-d', strtotime('-18 years')) ?>" required>
                            <div class="invalid-feedback">
                                Vui lòng chọn ngày sinh hợp lệ
                            </div>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="ngay_vao_lam">
                                <i class="fas fa-calendar-plus mr-1"></i>
                                Ngày vào làm <span class="text-danger">*</span>
                            </label>
                            <input type="date" id="ngay_vao_lam" name="ngay_vao_lam" class="form-control"
                                max="<?= date('Y-m-d') ?>" required>
                            <div class="invalid-feedback">
                                Vui lòng chọn ngày vào làm
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="dia_chi">
                            <i class="fas fa-map-marker-alt mr-1"></i>
                            Địa chỉ
                        </label>
                        <textarea name="dia_chi" id="dia_chi" class="form-control" rows="3"
                            placeholder="Nhập địa chỉ chi tiết"></textarea>
                    </div>

                    <!-- Thông tin liên hệ -->
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
                                placeholder="example@email.com" required>
                            <div class="invalid-feedback">
                                Vui lòng nhập email hợp lệ
                            </div>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="so_dien_thoai">
                                <i class="fas fa-phone mr-1"></i>
                                Số điện thoại
                            </label>
                            <input type="tel" id="so_dien_thoai" name="so_dien_thoai" class="form-control"
                                placeholder="0xxx xxx xxx" pattern="[0-9]{10,11}">
                            <div class="invalid-feedback">
                                Số điện thoại không hợp lệ
                            </div>
                        </div>
                    </div>

                    <!-- Thông tin công việc -->
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
                                    <option value="<?= htmlspecialchars($khoa['ma_khoa']) ?>">
                                        <?= htmlspecialchars($khoa['ten_khoa']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">
                                Vui lòng chọn khoa
                            </div>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="ma_bangcap">
                                <i class="fas fa-graduation-cap mr-1"></i>
                                Bằng cấp <span class="text-danger">*</span>
                            </label>
                            <select name="ma_bangcap" id="ma_bangcap" class="form-control" required>
                                <option value="">-- Chọn bằng cấp --</option>
                                <?php foreach ($bangcaps as $bangcap): ?>
                                    <option value="<?= htmlspecialchars($bangcap['ma_bangcap']) ?>">
                                        <?= htmlspecialchars($bangcap['ten_bangcap']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">
                                Vui lòng chọn bằng cấp
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
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
                            Thêm GIẢNG VIÊN
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Information Note -->
        <div class="alert alert-info mt-3">
            <i class="fas fa-info-circle mr-2"></i>
            <strong>Lưu ý:</strong>
            Tài khoản đăng nhập sẽ được tự động tạo với:
            <ul class="mb-0 mt-2">
                <li><strong>Username:</strong> [tên_không_dấu]@teacher.edu.vn</li>
                <li><strong>Mật khẩu mặc định:</strong> 1234</li>
            </ul>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <!-- Custom JavaScript -->
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

        // Set default date for ngay_vao_lam to today
        document.getElementById('ngay_vao_lam').valueAsDate = new Date();
    </script>
</body>

</html>