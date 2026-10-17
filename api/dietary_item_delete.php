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

    $rawBody = file_get_contents("php://input");
    $data = json_decode($rawBody, true);

    $itemId = $data['item_id'] ?? null;

    if (!$itemId) {

        echo json_encode([
            "success" => false,
            "message" => "item_id wajib diisi"
        ]);

        exit;
    }

    // =========================================
    // VALIDASI OWNERSHIP (item -> log -> user)
    // =========================================

    $checkQuery = $db->prepare("
        SELECT di.id, di.dietary_log_id
        FROM dietary_items di
        INNER JOIN dietary_logs dl ON dl.id = di.dietary_log_id
        WHERE di.id = ?
        AND dl.profile_id = ?
        LIMIT 1
    ");

    $checkQuery->execute([$itemId, $profileId]);

    $item = $checkQuery->fetch();

    if (!$item) {

        echo json_encode([
            "success" => false,
            "message" => "Item tidak ditemukan"
        ]);

        exit;
    }

    $logId = $item['dietary_log_id'];

    $db->beginTransaction();

    // =========================================
    // DELETE ITEM
    // =========================================

    $deleteItem = $db->prepare("DELETE FROM dietary_items WHERE id = ?");
    $deleteItem->execute([$itemId]);

    // =========================================
    // RECALCULATE TOTAL LOG
    // =========================================

    $recalculate = $db->prepare("
        SELECT
            SUM(calories) as total_calories,
            SUM(protein) as total_protein,
            SUM(carbs) as total_carbs,
            SUM(fat) as total_fat
        FROM dietary_items
        WHERE dietary_log_id = ?
    ");

    $recalculate->execute([$logId]);

    $totals = $recalculate->fetch();

    $updateLog = $db->prepare("
        UPDATE dietary_logs
        SET
            total_calories = ?,
            total_protein = ?,
            total_carbs = ?,
            total_fat = ?
        WHERE id = ?
    ");

    $updateLog->execute([
        round($totals['total_calories'] ?? 0, 2),
        round($totals['total_protein'] ?? 0, 2),
        round($totals['total_carbs'] ?? 0, 2),
        round($totals['total_fat'] ?? 0, 2),
        $logId
    ]);

    $db->commit();

    echo json_encode([
        "success" => true,
        "message" => "Item berhasil dihapus"
    ]);

} catch (Exception $e) {

    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>