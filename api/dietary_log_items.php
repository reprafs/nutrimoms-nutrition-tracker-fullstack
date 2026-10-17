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
    $db     = getDB();
    $profileId = (int)($_SESSION['active_profile_id'] ?? 0);
    if ($profileId <= 0) {
        echo json_encode(["success" => false, "message" => "Profil aktif tidak ditemukan."]);
        exit;
    }
    $date   = trim($_GET['date'] ?? '') ?: date('Y-m-d');

    // ── Cari log ──────────────────────────────────────────────────────────────
    $logQuery = $db->prepare("
        SELECT id FROM dietary_logs
        WHERE profile_id = ? AND log_date = ? LIMIT 1
    ");
    $logQuery->execute([$profileId, $date]);
    $log = $logQuery->fetch();

    if (!$log) {
        echo json_encode(["success" => true, "log_id" => null, "items" => []]);
        exit;
    }

    // ── Ambil items + food info ───────────────────────────────────────────────
    $itemsQuery = $db->prepare("
        SELECT
            di.id,
            di.food_id,
            di.quantity_type,
            di.quantity_value,
            di.calories,
            di.protein,
            di.carbs,
            di.fat,
            di.meal_type,
            f.food_name,
            f.image,
            f.base_gram,
            f.piece_gram
        FROM dietary_items di
        LEFT JOIN foods f ON f.id = di.food_id
        WHERE di.dietary_log_id = ?
        ORDER BY di.id ASC
    ");
    $itemsQuery->execute([$log['id']]);
    $items = $itemsQuery->fetchAll();

    $formatted = array_map(function($item) {
        return [
            "id"             => (int)$item['id'],
            "food_id"        => (int)$item['food_id'],
            "food_name"      => $item['food_name'],
            "image"          => $item['image'],
            "base_gram"      => (float)$item['base_gram'],
            "piece_gram"     => $item['piece_gram'] !== null ? (float)$item['piece_gram'] : null,
            "meal_type"      => $item['meal_type'] ?? 'pagi',
            "quantity_type"  => $item['quantity_type'],
            "quantity_value" => (float)$item['quantity_value'],
            "calories"       => (float)$item['calories'],
            "protein"        => (float)$item['protein'],
            "carbs"          => (float)$item['carbs'],
            "fat"            => (float)$item['fat']
        ];
    }, $items);

    echo json_encode([
        "success" => true,
        "log_id"  => (int)$log['id'],
        "items"   => $formatted
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>