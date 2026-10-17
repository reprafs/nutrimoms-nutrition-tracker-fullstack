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
    $db        = getDB();
    $profileId = (int)($_SESSION['active_profile_id'] ?? 0);
    if ($profileId <= 0) {
        echo json_encode(["success" => false, "message" => "Profil aktif tidak ditemukan."]);
        exit;
    }

    $start = trim($_GET['start'] ?? '') ?: date('Y-m-d');
    $end   = trim($_GET['end']   ?? '') ?: $start;

    // Validasi format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start)) $start = date('Y-m-d');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $end))   $end   = $start;

    $itemsQuery = $db->prepare("
        SELECT
            di.id,
            di.food_id,
            di.quantity_type,
            di.quantity_value   AS quantity,
            di.calories,
            di.protein,
            di.carbs,
            di.fat,
            di.meal_type,
            di.created_at,
            dl.log_date,
            f.food_name,
            f.image,
            f.base_gram,
            f.piece_gram
        FROM dietary_items di
        JOIN dietary_logs dl ON dl.id = di.dietary_log_id
        LEFT JOIN foods f ON f.id = di.food_id
        WHERE dl.profile_id = ?
          AND dl.log_date BETWEEN ? AND ?
        ORDER BY dl.log_date ASC, di.id ASC
    ");
    $itemsQuery->execute([$profileId, $start, $end]);
    $items = $itemsQuery->fetchAll();

    $formatted = array_map(function($item) {
        return [
            "id"            => (int)$item['id'],
            "food_id"       => (int)$item['food_id'],
            "food_name"     => $item['food_name'],
            "image"         => $item['image'],
            "base_gram"     => (float)$item['base_gram'],
            "piece_gram"    => $item['piece_gram'] !== null ? (float)$item['piece_gram'] : null,
            "meal_type"     => $item['meal_type'] ?? 'pagi',
            "quantity_type" => $item['quantity_type'],
            "quantity"      => (float)$item['quantity'],
            "calories"      => (float)$item['calories'],
            "protein"       => (float)$item['protein'],
            "carbs"         => (float)$item['carbs'],
            "fat"           => (float)$item['fat'],
            "log_date"      => $item['log_date'],   // ← field kunci, dari JOIN
            "created_at"    => $item['created_at']
        ];
    }, $items);

    echo json_encode([
        "success" => true,
        "items"   => $formatted
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>