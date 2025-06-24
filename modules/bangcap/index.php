<?php
require_once '../../config/database.php';
require_once '../header.php';

$database = new Database();
$conn = $database->getConnection();

// Lấy danh sách bằng cấp
$sql = "SELECT * FROM bangcap ORDER BY ma_bangcap";
$stmt = $conn->query($sql);
$bangcaps = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo getHeader("Quản lý Bằng cấp");
?>

<div class="card">
    <div class="card-body">
        <div class="mb-3">
            <a href="them.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Thêm Bằng cấp
            </a>
        </div>

        <table class="table table-striped table-bordered">
            <thead class="thead-dark">
                <tr>
                    <th>Mã bằng cấp</th>
                    <th>Tên bằng cấp</th>
                    <th>Hệ số lương</th>
                    <th>Hệ số giảng dạy</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bangcaps as $bc): ?>
                    <tr>
                        <td><?= htmlspecialchars($bc['ma_bangcap']) ?></td>
                        <td><?= htmlspecialchars($bc['ten_bangcap']) ?></td>
                        <td><?= number_format($bc['he_so_luong']) ?> VNĐ/giờ</td>
                        <td><?= number_format($bc['he_so'], 1) ?></td>
                        <td>
                            <a href="sua.php?id=<?= $bc['ma_bangcap'] ?>" class="btn btn-warning btn-sm">
                                <i class="fas fa-edit"></i> Sửa
                            </a>
                            <a href="xoa.php?id=<?= $bc['ma_bangcap'] ?>"
                                onclick="return confirm('Bạn có chắc muốn xóa bằng cấp này?')"
                                class="btn btn-danger btn-sm">
                                <i class="fas fa-trash"></i> Xóa
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>