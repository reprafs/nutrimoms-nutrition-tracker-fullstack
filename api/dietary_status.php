<?php
session_start();

header('Content-Type: application/json');

require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {

    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "Unauthorized"
    ]);

    exit;
}

try {

    $db = getDB();

    $profileId = (int)($_SESSION['active_profile_id'] ?? 0);
    if ($profileId <= 0) {
        echo json_encode(["success" => false, "message" => "Profil aktif tidak ditemukan."]);
        exit;
    }

    $start = trim($_GET['start'] ?? '');
    $end = trim($_GET['end'] ?? '');

    if (empty($start) || empty($end)) {

        echo json_encode([
            "success" => false,
            "message" => "Parameter start dan end wajib diisi"
        ]);

        exit;
    }

    $query = "
        SELECT log_date
        FROM dietary_logs
        WHERE profile_id = ?
        AND log_date BETWEEN ? AND ?
        AND (total_calories > 0 OR total_protein > 0 OR total_carbs > 0 OR total_fat > 0)
    ";

    $stmt = $db->prepare($query);
    $stmt->execute([$profileId, $start, $end]);

    $rows = $stmt->fetchAll();

    $dates = array_map(function($row) {
        return $row['log_date'];
    }, $rows);

    echo json_encode([
        "success" => true,
        "data" => $dates
    ]);

} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>