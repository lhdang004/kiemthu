<?php
require_once '../../config/database.php';
require_once '../header.php';

$database = new Database();
$conn = $database->getConnection();

// Lấy danh sách khoa
$khoas = $conn->query("SELECT * FROM khoa ORDER BY ten_khoa")->fetchAll();

// Lấy danh sách Học phần với thông tin khoa
$sql = "SELECT m.*, k.ten_khoa 
        FROM mon_hoc m 
        LEFT JOIN khoa k ON m.ma_khoa = k.ma_khoa 
        ORDER BY m.ten_mon";

$stmt = $conn->query($sql);
$monhocs = $stmt->fetchAll();

// Nhóm Học phần theo khoa
$monhoc_by_khoa = [];
foreach ($monhocs as $mon) {
    $khoa_name = $mon['ten_khoa'] ?? 'Học phần chung';
    $monhoc_by_khoa[$khoa_name][] = $mon;
}

echo getHeader("Quản lý Học phần");
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= $root_path ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<div class="container mt-4">
    <div class="card shadow">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-book"></i> Danh sách Học phần</h5>
            <a href="them.php" class="btn btn-light">
                <i class="fas fa-plus"></i> Thêm Học phần
            </a>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-bordered">
                    <thead class="thead-dark">
                        <tr>
                            <th><i class="fas fa-hashtag"></i> Mã môn</th>
                            <th><i class="fas fa-book-open"></i> Tên Học phần</th>
                            <th><i class="fas fa-clock"></i> Số tiết</th>
                            <th><i class="fas fa-graduation-cap"></i> Số tín chỉ</th>
                            <th><i class="fas fa-building"></i> Khoa</th>
                            <th><i class="fas fa-calculator"></i> Hệ số</th>
                            <th><i class="fas fa-cogs"></i> Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($monhocs as $mh): ?>
                            <tr>
                                <td><?= htmlspecialchars($mh['ma_mon'] ?? '') ?></td>
                                <td><?= htmlspecialchars($mh['ten_mon'] ?? '') ?></td>
                                <td><?= htmlspecialchars($mh['so_tiet'] ?? '') ?></td>
                                <td><?= htmlspecialchars($mh['so_tin_chi'] ?? 0) ?></td>
                                <td><?= htmlspecialchars($mh['ten_khoa'] ?? '') ?></td>
                                <td><?= htmlspecialchars($mh['he_so'] ?? 0) ?></td>
                                <td>
                                    <a href="sua.php?id=<?= $mh['ma_mon'] ?>" class="btn btn-warning btn-sm">
                                        <i class="fas fa-edit"></i> Sửa
                                    </a>
                                    <a href="xoa.php?id=<?= $mh['ma_mon'] ?>"
                                        onclick="return confirm('Bạn có chắc muốn xóa môn học này?')"
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
    </div>
</div>

<?php echo getFooter(); ?>