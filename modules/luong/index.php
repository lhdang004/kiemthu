<?php
require_once '../../config/database.php';
require_once '../header.php';

$database = new Database();
$conn = $database->getConnection();

// Get teachers list
$sql = "SELECT gv.ma_gv, gv.ho_ten FROM giaovien gv ORDER BY gv.ho_ten";
$teachers = $conn->query($sql)->fetchAll();

// Get semesters list
$sql = "SELECT * FROM hoc_ky ORDER BY nam_hoc DESC, ngay_bat_dau DESC";
$semesters = $conn->query($sql)->fetchAll();

echo getHeader("Tính lương giảng viên");
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= $root_path ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<div class="card shadow">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-calculator"></i> Tính lương giảng viên</h5>
    </div>

    <div class="card-body">
        <!-- Form chọn giáo viên và học kỳ -->
        <form method="POST" action="tinh_luong.php">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Giáo viên</label>
                        <select name="ma_gv" class="form-control" required>
                            <option value="">-- Chọn giáo viên --</option>
                            <?php foreach ($teachers as $teacher): ?>
                                <option value="<?= $teacher['ma_gv'] ?>">
                                    <?= htmlspecialchars($teacher['ho_ten']) ?> (Mã:
                                    <?= htmlspecialchars($teacher['ma_gv']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label><i class="fas fa-calendar"></i> Học kỳ</label>
                        <select name="ma_hk" class="form-control" required>
                            <option value="">-- Chọn học kỳ --</option>
                            <?php foreach ($semesters as $semester): ?>
                                <option value="<?= $semester['ma_hk'] ?>">
                                    <?= htmlspecialchars($semester['ten_hk'] . ' ' . $semester['nam_hoc']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="text-center mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-calculator"></i> Tính lương
                </button>
            </div>
        </form>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger mt-3">
                <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Hiển thị kết quả tính lương -->
        <?php if (isset($_SESSION['salary_result'])): ?>
            <div class="mt-4">
                <h5 class="text-primary mb-3">Kết quả tính lương</h5>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <tr>
                            <th style="width: 30%">Giáo viên:</th>
                            <td><?= htmlspecialchars($_SESSION['salary_result']['ten_gv']) ?></td>
                        </tr>
                        <tr>
                            <th>Học kỳ:</th>
                            <td><?= htmlspecialchars($_SESSION['salary_result']['hoc_ky']) ?></td>
                        </tr>
                        <tr>
                            <th>Hệ số giáo viên:</th>
                            <td><?= number_format($_SESSION['salary_result']['he_so_gv'], 1) ?></td>
                        </tr>
                    </table>

                    <h6 class="mt-4 mb-3">Chi tiết lương theo môn học:</h6>
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Môn học</th>
                                <th>Tên lớp</th>
                                <th>Số buổi</th>
                                <th>Tổng số tiết</th>
                                <th>Hệ số môn</th>
                                <th>Hệ số lớp</th>
                                <th>Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $currentMon = '';
                            foreach ($_SESSION['salary_result']['chi_tiet_mon'] as $mon):
                                $rowClass = ($currentMon === $mon['ten_mon']) ? 'table-light' : '';
                                $currentMon = $mon['ten_mon'];
                                ?>
                                <tr class="<?= $rowClass ?>">
                                    <td><?= htmlspecialchars($mon['ten_mon']) ?></td>
                                    <td><?= htmlspecialchars($mon['ten_lop_hoc']) ?></td>
                                    <td><?= $mon['so_buoi_day'] ?></td>
                                    <td><?= $mon['tong_so_tiet'] ?></td>
                                    <td><?= number_format($mon['he_so_mon'], 1) ?></td>
                                    <td><?= number_format($mon['he_so_lop'], 1) ?></td>
                                    <td class="text-right"><?= number_format($mon['luong_mon'], 0, ',', '.') ?> VNĐ</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-primary font-weight-bold" style="border-top: 2px solid #007bff">
                                <td colspan="6" class="text-right h5">
                                    <span class="badge badge-primary" style="font-size: 1em;">
                                        <i class="fas fa-coins mr-1"></i> Tổng cộng:
                                    </span>
                                </td>
                                <td class="text-right h5" style="min-width: 150px;">
                                    <span class="badge badge-light" style="font-size: 1em;">
                                        <?= number_format($_SESSION['salary_result']['tong_tien'], 0, ',', '.') ?> VNĐ
                                    </span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <?php unset($_SESSION['salary_result']); ?>
        <?php endif; ?>
    </div>
</div>

<?php echo getFooter(); ?>