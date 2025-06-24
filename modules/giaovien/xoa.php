<?php
require_once '../../config/database.php';

$database = new Database();
$conn = $database->getConnection();

$id = isset($_GET['id']) ? $_GET['id'] : die('Lỗi: Không tìm thấy ID');

try {
    $stmt = $conn->prepare("DELETE FROM giaovien WHERE ma_gv = ?");
    $stmt->execute([$id]);

    header("Location: index.php");
} catch (PDOException $e) {
    die("Lỗi xóa giáo viên: " . $e->getMessage());
}
