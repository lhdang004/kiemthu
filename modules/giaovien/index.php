<?php
require_once '../../config/database.php';
require_once '../header.php';

$database = new Database();
$conn = $database->getConnection();

$search = isset($_GET['search']) ? $_GET['search'] : '';

$sql = "SELECT gv.*, k.ten_khoa, bc.ten_bangcap, u.username as tai_khoan 
        FROM giaovien gv
        LEFT JOIN khoa k ON gv.ma_khoa = k.ma_khoa
        LEFT JOIN bangcap bc ON gv.ma_bangcap = bc.ma_bangcap
        LEFT JOIN users u ON gv.ma_gv = u.ma_gv
        WHERE gv.ho_ten LIKE :search
        OR gv.email LIKE :search
        ORDER BY gv.ma_gv DESC";

$stmt = $conn->prepare($sql);
$stmt->execute([':search' => "%$search%"]);
$giaoviens = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo getHeader("Quản lý giảng viên");
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= $root_path ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<div class="container mt-4">
    <h2>Quản lý giảng viên</h2>

    <div class="d-flex justify-content-between mb-3">
        <a href="them.php" class="btn btn-success">Thêm giảng viên</a>
        <form class="form-inline">
            <input type="text" name="search" class="form-control" placeholder="Tìm kiếm..."
                value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn btn-primary ml-2">Tìm kiếm</button>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Mã GV</th>
                    <th>Họ tên</th>
                    <th>Email</th>
                    <th>Số điện thoại</th>
                    <th>Khoa</th>
                    <th>Bằng cấp</th>
                    <th>Tài khoản</th>
                    <th>Mật khẩu</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($giaoviens as $gv): ?>
                    <tr>
                        <td><?= $gv['ma_gv'] ?></td>
                        <td><?= htmlspecialchars($gv['ho_ten']) ?></td>
                        <td><?= htmlspecialchars($gv['email']) ?></td>
                        <td><?= htmlspecialchars($gv['so_dien_thoai']) ?></td>
                        <td><?= htmlspecialchars($gv['ten_khoa']) ?></td>
                        <td><?= htmlspecialchars($gv['ten_bangcap']) ?></td>
                        <td><?= htmlspecialchars($gv['tai_khoan']) ?></td>
                        <td>1234 (mặc định)</td>
                        <td class="action-buttons">
                            <a href="chitiet.php?id=<?= $gv['ma_gv'] ?>" class="btn btn-info btn-sm">
                                <i class="fas fa-eye"></i> Chi tiết
                            </a>
                            <a href="sua.php?id=<?= $gv['ma_gv'] ?>" class="btn btn-warning btn-sm">
                                <i class="fas fa-edit"></i> Sửa
                            </a>
                            <a href="xoa.php?id=<?= $gv['ma_gv'] ?>" class="btn btn-danger btn-sm"
                                onclick="return confirm('Bạn có chắc muốn xóa giảng viên này?')">
                                <i class="fas fa-trash"></i> Xóa
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>