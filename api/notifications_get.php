<?php
session_start();

header('Content-Type: application/json');

require_once '../config/db.php';
require_once '../config/notification_helper.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

try {
    $db = getDB();
    $profileId = $_SESSION['profile_id'];

    if (!$profileId) {
        echo json_encode(["success" => false, "message" => "Profile tidak ditemukan"]);
        exit;
    }

    // 1) Pastikan insight mingguan sudah di-generate kalau memang waktunya.
    generateWeeklyInsightIfNeeded($db, $profileId);

    // 2) Ambil notifikasi tersimpan (insight/achievement) dari DB.
    $stored = getStoredNotifications($db, $profileId);

    // 3) Ambil reminder dinamis (tidak tersimpan di DB).
    $reminders = getReminderNotifications($db, $profileId);

    // 4) Gabungkan: reminder dinamis ditampilkan paling atas (paling relevan/segar),
    //    lalu notifikasi tersimpan terbaru.
    $notifications = array_merge($reminders, array_map(function ($row) {
        return [
            'id'         => (int)$row['id'],
            'type'       => $row['type'],
            'title'      => $row['title'],
            'body'       => $row['body'],
            'created_at' => $row['created_at'],
            'is_read'    => (int)$row['is_read'],
            'stored'     => true,
        ];
    }, $stored));

    $unreadCount = 0;
    foreach ($notifications as $n) {
        if ((int)$n['is_read'] === 0) {
            $unreadCount++;
        }
    }

    echo json_encode([
        "success"       => true,
        "notifications" => $notifications,
        "unread_count"  => $unreadCount
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>