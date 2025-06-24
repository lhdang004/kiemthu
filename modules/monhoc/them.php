<?php
require_once '../../config/database.php';
require_once '../header.php';

$database = new Database();
$conn = $database->getConnection();

// Lấy danh sách khoa
$khoas = $conn->query("SELECT * FROM khoa ORDER BY ten_khoa")->fetchAll();

function taoMaMH($conn, $ten_mon)
{
    // Lấy các chữ cái đầu của tên môn học
    $words = explode(' ', strtoupper($ten_mon));
    $prefix = '';
    foreach ($words as $word) {
        if (!empty($word)) {
            $prefix .= $word[0];
        }
    }

    // Nếu prefix quá ngắn, thêm 'MH'
    if (strlen($prefix) < 2) {
        $prefix = 'MH';
    }

    // Tìm số tiếp theo cho mã môn
    $sql = "SELECT ma_mon FROM mon_hoc WHERE ma_mon LIKE :prefix ORDER BY ma_mon DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':prefix' => $prefix . '%']);

    if ($stmt->rowCount() > 0) {
        $lastCode = $stmt->fetch(PDO::FETCH_ASSOC)['ma_mon'];
        $number = intval(substr($lastCode, -3)) + 1;
    } else {
        $number = 1;
    }

    return $prefix . str_pad($number, 3, '0', STR_PAD_LEFT);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Bắt buộc điền đầy đủ các trường
        $required = ['ten_mon', 'so_tiet', 'so_tin_chi', 'he_so', 'ma_khoa'];
        foreach ($required as $field) {
            if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
                throw new Exception("Vui lòng điền đầy đủ thông tin!");
            }
        }

        // Kiểm tra trùng tên học phần
        $stmtCheck = $conn->prepare("SELECT COUNT(*) FROM mon_hoc WHERE LOWER(TRIM(ten_mon)) = LOWER(TRIM(:ten_mon))");
        $stmtCheck->execute([':ten_mon' => $_POST['ten_mon']]);
        if ($stmtCheck->fetchColumn() > 0) {
            $error = "Học phần đã tồn tại!";
        } else {
            $ma_mon = taoMaMH($conn, $_POST['ten_mon']);
            $sql = "INSERT INTO mon_hoc (ma_mon, ten_mon, so_tiet, so_tin_chi, mo_ta, ma_khoa, he_so) 
                    VALUES (:ma_mon, :ten_mon, :so_tiet, :so_tin_chi, :mo_ta, :ma_khoa, :he_so)";

            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':ma_mon' => $ma_mon,
                ':ten_mon' => $_POST['ten_mon'],
                ':so_tiet' => $_POST['so_tiet'],
                ':so_tin_chi' => $_POST['so_tin_chi'],
                ':mo_ta' => $_POST['mo_ta'],
                ':ma_khoa' => !empty($_POST['ma_khoa']) ? $_POST['ma_khoa'] : null,
                ':he_so' => $_POST['he_so']
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

echo getHeader("Thêm Môn học");
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
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-book"></i> Thêm môn học mới</h5>
        </div>

        <div class="card-body">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            <form method="POST" class="needs-validation" novalidate>
                <div class="border rounded p-3 mb-3 bg-light">
                    <h6 class="text-primary mb-3"><i class="fas fa-info-circle"></i> Thông tin cơ bản</h6>
                    <div class="form-row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-book-open"></i> Tên môn học</label>
                                <input type="text" name="ten_mon" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-clock"></i> Số tiết</label>
                                <input type="number" name="so_tiet" class="form-control" required min="1">
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-building"></i> Khoa</label>
                                <select name="ma_khoa" class="form-control">
                                    <option value="">-- Chọn khoa --</option>
                                    <?php foreach ($khoas as $khoa): ?>
                                        <option value="<?= $khoa['ma_khoa'] ?>">
                                            <?= htmlspecialchars($khoa['ten_khoa']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-percentage"></i> Hệ số môn học</label>
                                <input type="number" name="he_so" class="form-control" value="1.0" step="0.1" min="1.0"
                                    max="1.5" required>
                                <small class="form-text text-muted">
                                    <i class="fas fa-info-circle"></i>
                                    Hệ số từ 1.0 đến 1.5 tùy độ khó của môn học
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-clock"></i> Số tín chỉ</label>
                                <input type="number" name="so_tin_chi" class="form-control" value="2" required min="1"
                                    max="10">
                                <small class="text-muted">Số tín chỉ từ 1-10</small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-align-left"></i> Mô tả</label>
                        <textarea name="mo_ta" class="form-control" rows="3"></textarea>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-primary btn-lg px-5">
                        <i class="fas fa-save"></i> Thêm môn học
                    </button>
                    <a href="index.php" class="btn btn-secondary btn-lg ml-2">
                        <i class="fas fa-arrow-left"></i> Quay lại
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php echo getFooter(); ?>