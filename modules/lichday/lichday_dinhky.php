<?php
require_once '../../config/database.php';
require_once '../header.php';

$database = new Database();
$conn = $database->getConnection();

// Lấy danh sách giáo viên dựa trên role
$sql = "SELECT g.ma_gv, g.ho_ten, b.he_so as he_so_gv 
        FROM giaovien g 
        JOIN bangcap b ON g.ma_bangcap = b.ma_bangcap";
if ($_SESSION['role'] === 'teacher') {
    $sql .= " WHERE g.ma_gv = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$_SESSION['ma_gv']]);
} else {
    $stmt = $conn->query($sql);
}
$giaoviens = $stmt->fetchAll();

// Lấy danh sách khoa
$khoas = $conn->query("SELECT ma_khoa, ten_khoa FROM khoa ORDER BY ten_khoa")->fetchAll();

// Chỉ lấy các môn học của khoa đầu tiên ban đầu
$firstKhoa = $khoas[0]['ma_khoa'] ?? null;
$monhocs = [];
if ($firstKhoa) {
    $stmt = $conn->prepare("SELECT ma_mon, ten_mon FROM mon_hoc WHERE ma_khoa = ? OR ma_khoa IS NULL ORDER BY ten_mon");
    $stmt->execute([$firstKhoa]);
    $monhocs = $stmt->fetchAll();
}

// Lấy thông tin học kỳ hiện tại
$current_date = date('Y-m-d');
$sql = "SELECT * FROM hoc_ky 
        WHERE ngay_bat_dau <= :current_date 
        AND ngay_ket_thuc >= :current_date 
        ORDER BY nam_hoc DESC, ngay_bat_dau DESC";

$stmt = $conn->prepare($sql);
$stmt->execute([':current_date' => $current_date]);
$current_hk = $stmt->fetch();

// Lấy danh sách học kỳ cho dropdown - Chỉ lấy học kỳ đang diễn ra
$hockys = $conn->query("SELECT * FROM hoc_ky 
                        WHERE trang_thai = 'Đang diễn ra'
                        OR (
                            CURRENT_DATE BETWEEN ngay_bat_dau AND ngay_ket_thuc
                            AND trang_thai != 'Đã kết thúc'
                        )
                        ORDER BY nam_hoc DESC, ngay_bat_dau DESC")->fetchAll();

function taoMaLichDK($conn) {
    $sql = "SELECT CONCAT('LD', LPAD(COALESCE(MAX(CAST(SUBSTRING_INDEX(SUBSTRING(ma_lich, 1, 6), 'LD', -1) AS UNSIGNED)) + 1, 1), 4, '0')) as ma_lich 
            FROM lich_day 
            WHERE ma_lich LIKE 'LD%'";
    $result = $conn->query($sql)->fetch();
    return $result['ma_lich'];
}

function taoMaLich($conn) {
    $sql = "SELECT CONCAT('L', LPAD(COALESCE(MAX(CAST(SUBSTRING_INDEX(SUBSTRING(ma_lich, 1, 9), 'L', -1) AS UNSIGNED)) + 1, 1), 8, '0')) as ma_lich 
            FROM lich_day";
    $result = $conn->query($sql)->fetch();
    return $result['ma_lich'];
}

function tinhHeSoLop($soSV) {
    if ($soSV < 20) return -0.3;
    if ($soSV < 30) return -0.2;
    if ($soSV < 40) return -0.1;
    if ($soSV < 50) return 0.0;
    if ($soSV < 60) return 0.1;
    if ($soSV < 70) return 0.2;
    if ($soSV < 80) return 0.3;
    if ($soSV < 90) return 0.4;
    if ($soSV < 100) return 0.5;
    if ($soSV < 110) return 0.6;
    if ($soSV < 120) return 0.7;
    return 0.7 + (floor(($soSV - 120) / 10) * 0.1);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Kiểm tra quyền thêm lịch
        if ($_SESSION['role'] === 'teacher' && $_POST['ma_gv'] !== $_SESSION['ma_gv']) {
            throw new Exception("Bạn không có quyền thêm lịch cho giáo viên khác!");
        }

        // Bắt buộc điền tất cả trường
        $requiredFields = [
            'ma_gv', 'ma_mon', 'ma_hk', 'so_sinh_vien', 'ten_lop_hoc',
            'thu_trong_tuan', 'tiet_bat_dau', 'so_tiet', 'phong_hoc'
        ];
        foreach ($requiredFields as $field) {
            if (!isset($_POST[$field]) || (is_array($_POST[$field]) ? in_array('', $_POST[$field], true) : trim($_POST[$field]) === '')) {
                throw new Exception("Vui lòng điền đầy đủ thông tin!");
            }
        }
        // Kiểm tra số lượng các trường mảng phải bằng nhau
        $count = count($_POST['thu_trong_tuan']);
        if (
            $count == 0 ||
            $count != count($_POST['tiet_bat_dau']) ||
            $count != count($_POST['so_tiet'])
        ) {
            throw new Exception("Vui lòng điền đầy đủ thông tin cho tất cả các buổi!");
        }

        $conn->beginTransaction();

        // Basic input validation
        if (empty($_POST['ma_gv']) || empty($_POST['ma_mon']) || empty($_POST['ma_hk'])) {
            throw new Exception("Vui lòng điền đầy đủ thông tin!");
        }

        // Validate foreign keys
        $stmt = $conn->prepare("SELECT COUNT(*) FROM giaovien WHERE ma_gv = ?");
        $stmt->execute([$_POST['ma_gv']]);
        if ($stmt->fetchColumn() == 0) {
            throw new Exception("Giảng viên không tồn tại!");
        }

        $stmt = $conn->prepare("SELECT COUNT(*) FROM mon_hoc WHERE ma_mon = ?");
        $stmt->execute([$_POST['ma_mon']]);
        if ($stmt->fetchColumn() == 0) {
            throw new Exception("Môn học không tồn tại!");
        }

        $stmt = $conn->prepare("SELECT COUNT(*) FROM hoc_ky WHERE ma_hk = ?");
        $stmt->execute([$_POST['ma_hk']]);
        if ($stmt->fetchColumn() == 0) {
            throw new Exception("Học kỳ không tồn tại!");
        }

        // Generate IDs outside the loop
        $base_ma_lich_dk = taoMaLichDK($conn);
        $count = count($_POST['thu_trong_tuan']);

        // Calculate he_so_lop once
        $he_so_lop = tinhHeSoLop($_POST['so_sinh_vien'] ?? 40);

        for ($i = 0; $i < $count; $i++) {
            $ma_lich_dk = $base_ma_lich_dk . '_' . ($i + 1); // Unique ID for each iteration
            $stmt = $conn->prepare("INSERT INTO lich_day 
                (ma_lich, ma_gv, ma_mon, ma_hk, thu_trong_tuan, tiet_bat_dau, so_tiet, 
                phong_hoc, so_buoi_tuan, so_sinh_vien, ngay_day, ten_lop_hoc, he_so_lop) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), ?, ?)");

            $stmt->execute([
                $ma_lich_dk,
                $_POST['ma_gv'],
                $_POST['ma_mon'],
                $_POST['ma_hk'],
                $_POST['thu_trong_tuan'][$i],
                $_POST['tiet_bat_dau'][$i],
                $_POST['so_tiet'][$i],
                $_POST['phong_hoc'],
                $count,
                $_POST['so_sinh_vien'] ?? 40,
                $_POST['ten_lop_hoc'],
                $he_so_lop  // Add calculated coefficient
            ]);

            // Create lich_day entries
            $hk_info = $conn->prepare("SELECT * FROM hoc_ky WHERE ma_hk = ?");
            $hk_info->execute([$_POST['ma_hk']]);
            $hk_info = $hk_info->fetch();

            $start_date = new DateTime($hk_info['ngay_bat_dau']);
            $end_date = new DateTime($hk_info['ngay_ket_thuc']);
            $thu = $_POST['thu_trong_tuan'][$i];

            while ($start_date <= $end_date) {
                if ($start_date->format('N') == $thu) {
                    $ma_lich = taoMaLich($conn) . '_' . $i;
                    $sql = "INSERT INTO lich_day 
                            (ma_lich, ma_gv, ma_mon, ma_hk, ngay_day, 
                            tiet_bat_dau, so_tiet, phong_hoc, ma_lich_goc, so_sinh_vien, ten_lop_hoc, he_so_lop) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                    $stmt = $conn->prepare($sql);
                    $stmt->execute([
                        $ma_lich,
                        $_POST['ma_gv'],
                        $_POST['ma_mon'],
                        $_POST['ma_hk'],
                        $start_date->format('Y-m-d'),
                        $_POST['tiet_bat_dau'][$i],
                        $_POST['so_tiet'][$i],
                        $_POST['phong_hoc'], 
                        $ma_lich_dk,
                        $_POST['so_sinh_vien'] ?? 40,
                        $_POST['ten_lop_hoc'],
                        $he_so_lop  // Add calculated coefficient
                    ]);
                }
                $start_date->modify('+1 day');
            }
        }

        // Thêm hoặc cập nhật lớp học vào bảng lop_hoc (cho phép trùng tên lớp, chỉ cần mã lớp duy nhất)
        $ten_lop_hoc = $_POST['ten_lop_hoc'];
        $so_sinh_vien = $_POST['so_sinh_vien'] ?? 40;
        $ma_mon = $_POST['ma_mon'];
        $ma_hk = $_POST['ma_hk'];

        // Mã lớp = tên lớp + mã môn (loại bỏ khoảng trắng, ký tự đặc biệt)
        $ma_lop = preg_replace('/[^A-Za-z0-9]/', '', $ten_lop_hoc) . $ma_mon;

        $stmtInsert = $conn->prepare("INSERT INTO lop_hoc (ma_lop, ten_lop, so_sinh_vien, ma_mon, ma_hk) VALUES (?, ?, ?, ?, ?)");
        $stmtInsert->execute([$ma_lop, $ten_lop_hoc, $so_sinh_vien, $ma_mon, $ma_hk]);

        $conn->commit();
        header("Location: index.php");
        exit();
    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        // Kiểm tra lỗi trùng mã lớp
        if (
            ($e instanceof PDOException || $e instanceof Exception)
            && strpos($e->getMessage(), 'Duplicate entry') !== false
            && strpos($e->getMessage(), 'for key \'PRIMARY\'') !== false
        ) {
            $error = "Lỗi: Lớp đã tồn tại, hãy thay đổi tên lớp!";
        } else {
            $error = "Lỗi: " . $e->getMessage();
        }
    }
}

echo getHeader("Lập lịch dạy");
?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= $root_path ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<div class="container mt-4">
    <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle"></i> <?= $error ?>
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
    <?php endif; ?>

    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-calendar-alt"></i> Lập lịch dạy </h5>
        </div>

        <div class="card-body">
            <form method="POST" class="needs-validation" novalidate>
                <!-- Phần 1: Thông tin giảng viên và môn học -->
                <div class="border rounded p-3 mb-3 bg-light">
                    <h6 class="text-primary mb-3"><i class="fas fa-info-circle"></i> Thông tin cơ bản</h6>
                    <div class="form-row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-user"></i> Giáo viên</label>
                                <select name="ma_gv" class="form-control custom-select" required>
                                    <option value="">-- Chọn giáo viên --</option>
                                    <?php foreach ($giaoviens as $gv): ?>
                                            <option value="<?= $gv['ma_gv'] ?>">
                                                <?= htmlspecialchars($gv['ho_ten']) ?>
                                            </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-building"></i> Khoa</label>
                                <select name="ma_khoa" class="form-control" id="select-khoa" required>
                                    <option value="">-- Chọn khoa --</option>
                                    <?php foreach ($khoas as $khoa): ?>
                                            <option value="<?= $khoa['ma_khoa'] ?>"><?= htmlspecialchars($khoa['ten_khoa']) ?>
                                            </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-book"></i> Môn học</label>
                                <select name="ma_mon" class="form-control" id="select-monhoc" required>
                                    <option value="">-- Chọn môn học --</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Phần 2: Thông tin thời gian -->
                <div class="border rounded p-3 mb-3 bg-light">
                    <h6 class="text-primary mb-3"><i class="fas fa-clock"></i> Thông tin thời gian</h6>
                    <div class="form-row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-calendar"></i> Học kỳ</label>
                                <select name="ma_hk" class="form-control" required>
                                    <option value="">-- Chọn học kỳ --</option>
                                    <?php foreach ($hockys as $hk): ?>
                                            <option value="<?= $hk['ma_hk'] ?>" 
                                                    <?= ($current_hk && $hk['ma_hk'] == $current_hk['ma_hk']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($hk['ten_hk']) ?> - <?= $hk['nam_hoc'] ?>
                                                (<?= date('d/m/Y', strtotime($hk['ngay_bat_dau'])) ?> -
                                                <?= date('d/m/Y', strtotime($hk['ngay_ket_thuc'])) ?>)
                                            </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-calendar-day"></i> Số buổi/tuần</label>
                                <input type="number" name="so_buoi_tuan" class="form-control" min="1" max="6" value="1"
                                    id="so_buoi_tuan" onchange="taoThuTrongTuan(this.value)" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            
                        </div>
                    </div>
                </div>
                <!-- Thông tin lớp học -->
                <div class="border rounded p-3 mb-3 bg-light">
                    <h6 class="text-primary mb-3"><i class="fas fa-users"></i> Thông tin lớp học</h6>
                    <div class="form-row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-users"></i> Số sinh viên</label>
                                <input type="number" name="so_sinh_vien" id="so_sinh_vien" class="form-control"
                                    value="40" min="1" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h6>Hệ số quy đổi:</h6>
                                    <p class="mb-1">Hệ số lớp: <span id="he_so_lop">0</span></p>
                                    <p class="mb-1">Hệ số giáo viên: <span id="he_so_gv">0</span></p>
                                    <p class="mb-0">Hệ số học phần: <span id="mon_hoc">1.0</span></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-users"></i> Tên Lớp Học</label>
                                <input type="text" name="ten_lop_hoc" id="ten_lop_hoc" class="form-control"
                                    value="N01" required>
                            </div>
                        </div>
                        
                    </div>
                </div>

                <!-- Phần 3: Thông tin buổi học -->
                <div class="border rounded p-3 mb-3 bg-light">
                    <h6 class="text-primary mb-3"><i class="fas fa-calendar-check"></i> Thông tin buổi học</h6>
                    <div class="form-row">
                        <div class="col-12" id="thu_trong_tuan_container">
                            <!-- Thứ trong tuần sẽ được thêm động bằng JavaScript -->
                        </div>
                    </div>
                </div>

                

                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-primary btn-lg px-5">
                        <i class="fas fa-save"></i> Lập lịch 
                    </button>
                    <a href="index.php" class="btn btn-secondary btn-lg ml-2">
                        <i class="fas fa-arrow-left"></i> Quay lại
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.querySelector('form').addEventListener('submit', function (e) {
        const hocky = document.querySelector('select[name="ma_hk"]').value;
        if (!hocky) {
            e.preventDefault();
            alert('Vui lòng chọn học kỳ!');
        }
    });

    // Update JavaScript event handler
    document.getElementById('select-khoa').addEventListener('change', function () {
        const maKhoa = this.value;
        const select = document.getElementById('select-monhoc');
        select.innerHTML = '<option value="">-- Chọn môn học --</option>';

        if (maKhoa) {
            fetch(`get_monhoc.php?ma_khoa=${maKhoa}`)
                .then(response => response.json())
                .then(data => {
                    data.forEach(mon => {
                        const option = document.createElement('option');
                        option.value = mon.ma_mon;
                        option.text = mon.ten_mon;
                        option.dataset.heso = mon.he_so || 1.0;
                        select.add(option);
                    });
                })
                .catch(error => console.error('Error:', error));
        }
    });

    document.querySelector('select[name="ma_mon"]').addEventListener('change', function () {
        const selectedOption = this.options[this.selectedIndex];
        const heSoMon = selectedOption.dataset.heso || 1.0;
        document.getElementById('mon_hoc').textContent = heSoMon;
        updateTotalCoefficient();
    });

    function taoThuTrongTuan(soBuoi) {
        const container = document.getElementById('thu_trong_tuan_container');
        container.innerHTML = '';

        for (let i = 1; i <= soBuoi; i++) {
            const col = document.createElement('div');
            col.className = 'col-12 mb-3';
            col.innerHTML = `
                <div class="border rounded p-3">
                    <h6 class="text-primary mb-3">Buổi ${i}</h6>
                    <div class="form-row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-calendar-day"></i> Thứ trong tuần</label>
                                <select name="thu_trong_tuan[]" class="form-control" required>
                                    <option value="2">Thứ 2</option>
                                    <option value="3">Thứ 3</option>
                                    <option value="4">Thứ 4</option>
                                    <option value="5">Thứ 5</option>
                                    <option value="6">Thứ 6</option>
                                    <option value="7">Thứ 7</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-hourglass-start"></i> Tiết bắt đầu</label>
                                <input type="number" name="tiet_bat_dau[]" class="form-control" min="1" max="10" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-hourglass-end"></i> Số tiết</label>
                                <input type="number" name="so_tiet[]" class="form-control" min="1" max="6" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-door-open"></i> Phòng học</label>
                                <input type="text" name="phong_hoc" class="form-control" required>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            container.appendChild(col);
        }
    }

    // Khởi tạo form với 1 buổi
    document.addEventListener('DOMContentLoaded', function () {
        taoThuTrongTuan(1);
    });

    function updateTotalCoefficient() {
        const heSoGV = parseFloat(document.getElementById('he_so_gv').textContent) || 1.0;
        const heSoMon = parseFloat(document.getElementById('mon_hoc').textContent) || 1.0;
        const heSoLop = parseFloat(document.getElementById('he_so_lop').textContent) || 0;

        const totalCoefficient = heSoGV * (heSoMon + heSoLop);
        document.getElementById('total_coefficient').textContent = totalCoefficient.toFixed(2);
    }

    document.querySelector('select[name="ma_gv"]').addEventListener('change', function () {
        const maGV = this.value;
        fetch(`get_giaovien_info.php?ma_gv=${maGV}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('he_so_gv').textContent = data.he_so_gv;
                updateTotalCoefficient();
            });
    });

    document.querySelector('select[name="ma_mon"]').addEventListener('change', function () {
        const maMon = this.value;
        fetch(`get_monhoc_info.php?ma_mon=${maMon}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('he_so').textContent = data.he_so;
                updateTotalCoefficient();
            });
    });

    function tinhHeSoLop(soSV) {
        if (soSV < 20) return -0.3;
        if (soSV < 30) return -0.2;
        if (soSV < 40) return -0.1;
        if (soSV < 50) return 0.0;
        if (soSV < 60) return 0.1;
        if (soSV < 70) return 0.2;
        if (soSV < 80) return 0.3;
        if (soSV < 90) return 0.4;
        if (soSV < 100) return 0.5;
        if (soSV < 110) return 0.6;
        if (soSV < 120) return 0.7;
        return 0.7 + (Math.floor((soSV - 120) / 10) * 0.1);
    }

    document.getElementById('so_sinh_vien').addEventListener('input', function () {
        const soSV = parseInt(this.value) || 0;
        const heSoLop = tinhHeSoLop(soSV);
        document.getElementById('he_so_lop').textContent = heSoLop.toFixed(1);
        updateTotalCoefficient();
    });

    // Khởi tạo giá trị hệ số lớp khi trang load
    document.addEventListener('DOMContentLoaded', function () {
        const soSV = parseInt(document.getElementById('so_sinh_vien').value) || 0;
        const heSoLop = tinhHeSoLop(soSV);
        document.getElementById('he_so_lop').textContent = heSoLop.toFixed(1);
    });
</script>

<?php echo getFooter(); ?>