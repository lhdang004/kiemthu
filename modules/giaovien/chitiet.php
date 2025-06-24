<?php
require_once '../../config/database.php';
require_once '../header.php';

$database = new Database();
$conn = $database->getConnection();

$id = isset($_GET['id']) ? $_GET['id'] : die('Lỗi: Không tìm thấy ID');

// Lấy thông tin chi tiết giảng viên
$sql = "SELECT gv.*, k.ten_khoa, bc.ten_bangcap, bc.he_so_luong 
        FROM giaovien gv
        LEFT JOIN khoa k ON gv.ma_khoa = k.ma_khoa
        LEFT JOIN bangcap bc ON gv.ma_bangcap = bc.ma_bangcap
        WHERE gv.ma_gv = ?";

$stmt = $conn->prepare($sql);
$stmt->execute([$id]);
$giaovien = $stmt->fetch(PDO::FETCH_ASSOC);

echo getHeader("Chi tiết giảng viên");
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= $root_path ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<div class="container">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h3 class="card-title mb-0">Thông tin chi tiết giảng viên</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-bordered">
                        <tr>
                            <th width="30%">Họ và tên</th>
                            <td><?= htmlspecialchars($giaovien['ho_ten']) ?></td>
                        </tr>
                        <tr>
                            <th>Giới tính</th>
                            <td><?= $giaovien['gioi_tinh'] ?></td>
                        </tr>
                        <tr>
                            <th>Ngày sinh</th>
                            <td><?= date('d/m/Y', strtotime($giaovien['ngay_sinh'])) ?></td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td><?= htmlspecialchars($giaovien['email']) ?></td>
                        </tr>
                        <tr>
                            <th>Số điện thoại</th>
                            <td><?= htmlspecialchars($giaovien['so_dien_thoai']) ?></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-bordered">
                        <tr>
                            <th width="30%">Khoa</th>
                            <td><?= htmlspecialchars($giaovien['ten_khoa']) ?></td>
                        </tr>
                        <tr>
                            <th>Bằng cấp</th>
                            <td><?= htmlspecialchars($giaovien['ten_bangcap']) ?></td>
                        </tr>
                        <tr>
                            <th>Hệ số lương</th>
                            <td><?= number_format($giaovien['he_so_luong']) ?> VNĐ/giờ</td>
                        </tr>
                        <tr>
                            <th>Ngày vào làm</th>
                            <td><?= date('d/m/Y', strtotime($giaovien['ngay_vao_lam'])) ?></td>
                        </tr>
                        <tr>
                            <th>Địa chỉ</th>
                            <td><?= nl2br(htmlspecialchars($giaovien['dia_chi'])) ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="text-center mt-3">
                <a href="sua.php?id=<?= $giaovien['ma_gv'] ?>" class="btn btn-warning">
                    <i class="fas fa-edit"></i> Sửa thông tin
                </a>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Quay lại
                </a>
            </div>
        </div>
    </div>
</div>