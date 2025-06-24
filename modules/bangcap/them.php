<?php
require_once '../../config/database.php';
require_once '../header.php';

$database = new Database();
$conn = $database->getConnection();

function taoMaBangCap($conn)
{
    $sql = "SELECT ma_bangcap FROM bangcap ORDER BY ma_bangcap DESC LIMIT 1";
    $stmt = $conn->query($sql);
    if ($stmt->rowCount() > 0) {
        $last = $stmt->fetch(PDO::FETCH_ASSOC)['ma_bangcap'];
        $num = intval(substr($last, 2)) + 1;
        return 'BC' . str_pad($num, 3, '0', STR_PAD_LEFT);
    }
    return 'BC001';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Kiểm tra trùng tên bằng cấp
        $stmtCheck = $conn->prepare("SELECT COUNT(*) FROM bangcap WHERE LOWER(TRIM(ten_bangcap)) = LOWER(TRIM(:ten))");
        $stmtCheck->execute([':ten' => $_POST['ten_bangcap']]);
        if ($stmtCheck->fetchColumn() > 0) {
            $error = "Bằng cấp đã tồn tại!";
        } else {
            $ma = taoMaBangCap($conn);
            $stmt = $conn->prepare("INSERT INTO bangcap (ma_bangcap, ten_bangcap, he_so_luong, he_so) 
                                    VALUES (:ma, :ten, :luong, :heso)");
            $stmt->execute([
                ':ma' => $ma,
                ':ten' => $_POST['ten_bangcap'],
                ':luong' => $_POST['he_so_luong'],
                ':heso' => $_POST['he_so']
            ]);
            header("Location: index.php");
            exit();
        }
    } catch (Exception $e) {
        $error = "Lỗi: " . $e->getMessage();
    } catch (PDOException $e) {
        $error = "Lỗi: " . $e->getMessage();
    }
}

echo getHeader("Thêm Bằng cấp");
?>



<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= $root_path ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<div class="card mb-4">
    <div class="card-header d-flex align-items-center">
        <i class="fas fa-plus-circle"></i> Thêm bằng cấp mới
    </div>

    <div class="card-body">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="ten_bangcap"><i class="fas fa-graduation-cap mr-1"></i> Tên bằng cấp</label>
                    <input type="text" name="ten_bangcap" id="ten_bangcap" class="form-control"
                        placeholder="VD: Cử nhân CNTT" required>
                </div>

                <div class="form-group col-md-6">
                    <label for="he_so_luong"><i class="fas fa-money-bill mr-1"></i> Hệ số lương (VNĐ/giờ)</label>
                    <input type="number" name="he_so_luong" id="he_so_luong" class="form-control" min="100000"
                        step="50000" required>
                </div>

                <div class="form-group col-md-6">
                    <label for="he_so"><i class="fas fa-calculator mr-1"></i> Hệ số giảng dạy</label>
                    <input type="number" name="he_so" id="he_so" class="form-control" min="1.0" max="3.0" step="0.1"
                        required>
                </div>
            </div>

            <div class="text-center mt-4">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="fas fa-save"></i> Lưu
                </button>
                <a href="index.php" class="btn btn-secondary ml-2">
                    <i class="fas fa-arrow-left"></i> Quay lại
                </a>
            </div>
        </form>
    </div>
</div>

<?php echo getFooter(); ?>