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

    $data          = json_decode(file_get_contents("php://input"), true);
    $itemId        = $data['item_id']        ?? null;
    $quantityValue = $data['quantity_value'] ?? null;
    $mealType      = $data['meal_type']      ?? null;

    $validMealTypes = ['pagi', 'siang', 'malam', 'snack'];

    // Allow meal_type-only update (no quantity required)
    $updatingQty      = $quantityValue !== null;
    $updatingMealType = $mealType !== null && in_array($mealType, $validMealTypes);

    if (!$itemId || (!$updatingQty && !$updatingMealType)) {
        echo json_encode(["success" => false, "message" => "Parameter tidak valid"]);
        exit;
    }

    if ($updatingQty && (float)$quantityValue <= 0) {
        echo json_encode(["success" => false, "message" => "Quantity tidak valid"]);
        exit;
    }

    // ── Validasi ownership + ambil data food ──────────────────────────────────
    $checkQuery = $db->prepare("
        SELECT
            di.id,
            di.dietary_log_id,
            di.food_id,
            di.quantity_type,
            f.calories      AS base_calories,
            f.protein       AS base_protein,
            f.carbs         AS base_carbs,
            f.fat           AS base_fat,
            f.sugar         AS base_sugar,
            f.sodium        AS base_sodium,
            f.fiber         AS base_fiber,
            f.saturated_fat AS base_saturated_fat,
            f.cholesterol   AS base_cholesterol,
            f.omega_3       AS base_omega_3,
            f.base_gram,
            f.piece_gram
        FROM dietary_items di
        INNER JOIN dietary_logs dl ON dl.id = di.dietary_log_id
        LEFT JOIN foods f ON f.id = di.food_id
        WHERE di.id = ?
          AND dl.profile_id = ?
        LIMIT 1
    ");
   $checkQuery->execute([$itemId, $profileId]);
    $item = $checkQuery->fetch();

    if (!$item) {
        echo json_encode(["success" => false, "message" => "Item tidak ditemukan"]);
        exit;
    }

    $logId = $item['dietary_log_id'];

    // ── Hitung multiplier ─────────────────────────────────────────────────────
    // piece  → multiplier = qty × (piece_gram / base_gram)
    //          jika piece_gram NULL, fallback ke qty × 1 (base_gram dianggap = 1 piece)
    // gram   → multiplier = qty / base_gram
    $db->beginTransaction();

    if ($updatingMealType && !$updatingQty) {
        // ── Meal type only ────────────────────────────────────────────────────
        $db->prepare("UPDATE dietary_items SET meal_type = ? WHERE id = ?")
           ->execute([$mealType, $itemId]);

        $db->commit();
        echo json_encode(["success" => true, "meal_type" => $mealType]);
        exit;
    }

    // ── Quantity update (with optional meal_type) ─────────────────────────────
    $baseGram  = (float)$item['base_gram'];
    $pieceGram = $item['piece_gram'] !== null ? (float)$item['piece_gram'] : null;
    $qty       = (float)$quantityValue;

    if ($item['quantity_type'] === 'piece') {
        if ($pieceGram !== null && $baseGram > 0) {
            $multiplier = $qty * ($pieceGram / $baseGram);
        } else {
            $multiplier = $qty;
        }
    } else {
        $multiplier = $baseGram > 0 ? $qty / $baseGram : 0;
    }

    $calories     = round((float)$item['base_calories']      * $multiplier, 1);
    $protein      = round((float)$item['base_protein']       * $multiplier, 1);
    $carbs        = round((float)$item['base_carbs']         * $multiplier, 1);
    $fat          = round((float)$item['base_fat']           * $multiplier, 1);
    $sugar        = round((float)$item['base_sugar']         * $multiplier, 1);
    $sodium       = round((float)$item['base_sodium']        * $multiplier, 1);
    $fiber        = round((float)$item['base_fiber']         * $multiplier, 1);
    $saturatedFat = round((float)$item['base_saturated_fat'] * $multiplier, 1);
    $cholesterol  = round((float)$item['base_cholesterol']   * $multiplier, 1);
    $omega3       = round((float)$item['base_omega_3']       * $multiplier, 1);

    // ── Update item ───────────────────────────────────────────────────────────
    if ($updatingMealType) {
        $db->prepare("
            UPDATE dietary_items
            SET quantity_value = ?,
                calories = ?, protein = ?, carbs = ?, fat = ?,
                sugar = ?, sodium = ?, fiber = ?,
                saturated_fat = ?, cholesterol = ?, omega_3 = ?,
                meal_type = ?
            WHERE id = ?
        ")->execute([
            $qty,
            $calories, $protein, $carbs, $fat,
            $sugar, $sodium, $fiber,
            $saturatedFat, $cholesterol, $omega3,
            $mealType, $itemId
        ]);
    } else {
        $db->prepare("
            UPDATE dietary_items
            SET quantity_value = ?,
                calories = ?, protein = ?, carbs = ?, fat = ?,
                sugar = ?, sodium = ?, fiber = ?,
                saturated_fat = ?, cholesterol = ?, omega_3 = ?
            WHERE id = ?
        ")->execute([
            $qty,
            $calories, $protein, $carbs, $fat,
            $sugar, $sodium, $fiber,
            $saturatedFat, $cholesterol, $omega3,
            $itemId
        ]);
    }

    // ── Recalculate log total ─────────────────────────────────────────────────
    $totals = $db->prepare("
        SELECT
            SUM(calories)      AS c,  SUM(protein)       AS p,
            SUM(carbs)         AS cb, SUM(fat)            AS f,
            SUM(sugar)         AS sg, SUM(sodium)         AS sd,
            SUM(fiber)         AS fb, SUM(saturated_fat)  AS sf,
            SUM(cholesterol)   AS ch, SUM(omega_3)        AS om
        FROM dietary_items WHERE dietary_log_id = ?
    ");
    $totals->execute([$logId]);
    $t = $totals->fetch();

    $db->prepare("
        UPDATE dietary_logs
        SET total_calories = ?, total_protein = ?, total_carbs = ?, total_fat = ?,
            total_sugar = ?, total_sodium = ?, total_fiber = ?,
            total_saturated_fat = ?, total_cholesterol = ?, total_omega_3 = ?
        WHERE id = ?
    ")->execute([
        round($t['c']  ?? 0, 2), round($t['p']  ?? 0, 2),
        round($t['cb'] ?? 0, 2), round($t['f']  ?? 0, 2),
        round($t['sg'] ?? 0, 2), round($t['sd'] ?? 0, 2),
        round($t['fb'] ?? 0, 2), round($t['sf'] ?? 0, 2),
        round($t['ch'] ?? 0, 2), round($t['om'] ?? 0, 2),
        $logId
    ]);

    $db->commit();

    echo json_encode([
        "success"  => true,
        "calories" => $calories,
        "protein"  => $protein,
        "carbs"    => $carbs,
        "fat"      => $fat
    ]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>