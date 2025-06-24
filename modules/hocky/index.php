<?php
require_once '../../config/database.php';
require_once '../header.php';

$database = new Database();
$conn = $database->getConnection();

// Update query to get status
$sql = "SELECT hk.*, 
        (SELECT COUNT(*) FROM lich_day WHERE ma_hk = hk.ma_hk) as so_lich_day
        FROM hoc_ky hk
        ORDER BY hk.nam_hoc DESC, hk.ngay_bat_dau DESC";

$hockys = $conn->query($sql)->fetchAll();



echo getHeader("Quản lý Kỳ học");
?>


<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= $root_path ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<?php if (isset($error)): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
    </div>
    <script>
        alert("<?= str_replace('"', '\"', $error) ?>");
    </script>
<?php endif; ?>
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Danh sách kỳ học</h5>
            <a href="them.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Thêm kỳ học mới
            </a>
        </div>
    </div>
    <div class="card-body">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php
                switch ($_GET['success']) {
                    case 1:
                        echo "Thêm kỳ học mới thành công!";
                        break;
                    case 2:
                        echo "Cập nhật kỳ học thành công!";
                        break;
                    case 3:
                        echo "Xóa kỳ học thành công!";
                        break;
                }
                ?>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="thead-light">
                    <tr>
                        <th>Mã kỳ học</th>
                        <th>Tên kỳ học</th>
                        <th>Năm học</th>
                        <th>Thời gian</th>
                        <th>Số lịch dạy</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($hockys as $hk): ?>
                        <tr>
                            <td><?= $hk['ma_hk'] ?></td>
                            <td><?= htmlspecialchars($hk['ten_hk']) ?></td>
                            <td><?= htmlspecialchars($hk['nam_hoc']) ?></td>
                            <td>
                                <?= date('d/m/Y', strtotime($hk['ngay_bat_dau'])) ?> -
                                <?= date('d/m/Y', strtotime($hk['ngay_ket_thuc'])) ?>
                            </td>
                            <td class="text-center"><?= $hk['so_lich_day'] ?></td>
                            <td>
                                <?php
                                $badge_class = match ($hk['trang_thai'] ?? 'Sắp diễn ra') {
                                    'Sắp diễn ra' => 'info',
                                    'Đang diễn ra' => 'success',
                                    'Đã kết thúc' => 'secondary',
                                    default => 'info'
                                };
                                ?>
                                <span class="badge badge-<?= $badge_class ?>">
                                    <?= htmlspecialchars($hk['trang_thai'] ?? 'Sắp diễn ra') ?>
                                </span>
                            </td>
                            <td>
                                <a href="sua.php?id=<?= $hk['ma_hk'] ?>" class="btn btn-warning btn-sm">
                                    <i class="fas fa-edit"></i> Sửa
                                </a>
                                <?php if (($hk['trang_thai'] ?? 'Sắp diễn ra') === 'Sắp diễn ra'): ?>
                                    <a href="doi_trang_thai.php?id=<?= $hk['ma_hk'] ?>&action=start"
                                        class="btn btn-success btn-sm">
                                        <i class="fas fa-play"></i> Bắt đầu
                                    </a>
                                <?php elseif ($hk['trang_thai'] === 'Đang diễn ra'): ?>
                                    <a href="doi_trang_thai.php?id=<?= $hk['ma_hk'] ?>&action=end"
                                        class="btn btn-danger btn-sm">
                                        <i class="fas fa-stop"></i> Kết thúc
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
    function xoaKyHoc(maHK) {
        if (confirm('Bạn có chắc chắn muốn xóa kỳ học này?')) {
            window.location.href = `xoa.php?id=${maHK}`;
        }
    }
</script>

<?php echo getFooter(); ?>