<?php
session_start();

header('Content-Type: application/json');

require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

try {
    $db = getDB();
    $profileId = $_SESSION['profile_id'];

    $data = json_decode(file_get_contents("php://input"), true);
    $notificationId = $data['notification_id'] ?? null;

    if (!$profileId) {
        echo json_encode(["success" => false, "message" => "Profile tidak ditemukan"]);
        exit;
    }

    if (!$notificationId) {
        echo json_encode(["success" => false, "message" => "notification_id wajib diisi"]);
        exit;
    }

    // Reminder dinamis (id string seperti "reminder_no_log_2026-06-20") tidak
    // tersimpan di DB, jadi tidak ada yang perlu dihapus di sisi server.
    // Anggap berhasil karena penghapusan di UI sudah cukup (reminder akan
    // otomatis tidak muncul lagi setelah kondisi pemicunya tidak terpenuhi).
    if (!is_numeric($notificationId)) {
        echo json_encode(["success" => true, "message" => "Reminder dihapus dari tampilan"]);
        exit;
    }

    // Validasi ownership sebelum hapus
    $checkStmt = $db->prepare("
        SELECT id FROM notifications
        WHERE id = ? AND profile_id = ?
        LIMIT 1
    ");
    $checkStmt->execute([$notificationId, $profileId]);

    if (!$checkStmt->fetch()) {
        echo json_encode(["success" => false, "message" => "Notifikasi tidak ditemukan"]);
        exit;
    }

    // Soft delete — bukan DELETE permanen. Row tetap ada (ditandai is_deleted)
    // supaya generateWeeklyInsightIfNeeded() tahu insight minggu ini sudah
    // pernah dibuat dan tidak generate ulang notif yang sama setelah dihapus.
    $deleteStmt = $db->prepare("UPDATE notifications SET is_deleted = 1 WHERE id = ?");
    $deleteStmt->execute([$notificationId]);

    echo json_encode(["success" => true, "message" => "Notifikasi dihapus"]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>