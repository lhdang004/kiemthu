<?php
require_once '../../config/database.php';
require_once '../header.php';

$database = new Database();
$conn = $database->getConnection();

function taoMaHocKy($conn)
{
    $sql = "SELECT ma_hk FROM hoc_ky ORDER BY ma_hk DESC LIMIT 1";
    $stmt = $conn->query($sql);
    if ($stmt->rowCount() > 0) {
        $lastCode = $stmt->fetch(PDO::FETCH_ASSOC)['ma_hk'];
        $number = intval(substr($lastCode, 2)) + 1;
        return 'HK' . str_pad($number, 3, '0', STR_PAD_LEFT);
    }
    return 'HK001';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $ma_hk = taoMaHocKy($conn);
        // Bắt buộc điền đầy đủ các trường
        $required = ['ten_hk', 'nam_hoc', 'ngay_bat_dau', 'ngay_ket_thuc', 'luong_hocky'];
        foreach ($required as $field) {
            if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
                throw new Exception("Vui lòng điền đầy đủ thông tin bắt buộc!");
            }
        }

        // Kiểm tra ngày bắt đầu và kết thúc
        if ($_POST['ngay_ket_thuc'] <= $_POST['ngay_bat_dau']) {
            throw new Exception("Ngày kết thúc phải sau ngày bắt đầu!");
        }

        // Kiểm tra năm học hợp lệ
        if (!preg_match("/^\d{4}-\d{4}$/", $_POST['nam_hoc'])) {
            throw new Exception("Năm học không hợp lệ (định dạng: YYYY-YYYY)!");
        }

        // Kiểm tra trùng thời gian học kỳ khác
        $sqlCheck = "SELECT COUNT(*) FROM hoc_ky 
            WHERE 
                (ngay_bat_dau <= :ngay_ket_thuc AND ngay_ket_thuc >= :ngay_bat_dau)";
        $stmtCheck = $conn->prepare($sqlCheck);
        $stmtCheck->execute([
            ':ngay_bat_dau' => $_POST['ngay_bat_dau'],
            ':ngay_ket_thuc' => $_POST['ngay_ket_thuc']
        ]);
        if ($stmtCheck->fetchColumn() > 0) {
            throw new Exception("Lỗi: Trùng thời gian với học kỳ khác!");
        }

        // Kiểm tra lương học kỳ là số dương
        if (!is_numeric($_POST['luong_hocky']) || floatval($_POST['luong_hocky']) < 0) {
            throw new Exception("Lương học kỳ phải là số không âm!");
        }

        $sql = "INSERT INTO hoc_ky (ma_hk, ten_hk, nam_hoc, ngay_bat_dau, ngay_ket_thuc, luong_hocky, trang_thai) 
                VALUES (:ma_hk, :ten_hk, :nam_hoc, :ngay_bat_dau, :ngay_ket_thuc, :luong_hocky, 'Sắp diễn ra')";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':ma_hk' => $ma_hk,
            ':ten_hk' => $_POST['ten_hk'],
            ':nam_hoc' => $_POST['nam_hoc'],
            ':ngay_bat_dau' => $_POST['ngay_bat_dau'],
            ':ngay_ket_thuc' => $_POST['ngay_ket_thuc'],
            ':luong_hocky' => $_POST['luong_hocky']
        ]);

        header('Location: index.php?success=1');
        exit();
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

echo getHeader("Thêm Kỳ học mới");
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
<?php endif; ?>
<div class="card">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-plus-circle"></i> Thêm kỳ học mới</h5>
    </div>
    <div class="card-body">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
            <script>
                alert("<?= str_replace('"', '\"', $error) ?>");
            </script>
        <?php endif; ?>

        <form method="POST" onsubmit="return validateForm()">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Tên kỳ học</label>
                        <input type="text" name="ten_hk" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Năm học</label>
                        <input type="text" name="nam_hoc" class="form-control" placeholder="VD: 2023-2024" required
                            pattern="\d{4}-\d{4}">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Ngày bắt đầu</label>
                        <input type="date" name="ngay_bat_dau" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Ngày kết thúc</label>
                        <input type="date" name="ngay_ket_thuc" class="form-control" required>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Lương học kỳ</label>
                        <input type="number" name="luong_hocky" class="form-control" min="0" step="any" required>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Lưu kỳ học
            </button>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Quay lại
            </a>
        </form>
    </div>
</div>

<script>
    function validateForm() {
        const start = document.getElementsByName('ngay_bat_dau')[0].value;
        const end = document.getElementsByName('ngay_ket_thuc')[0].value;
        if (end <= start) {
            alert('Ngày kết thúc phải sau ngày bắt đầu!');
            return false;
        }
        return true;
    }
</script>

<?php echo getFooter(); ?>