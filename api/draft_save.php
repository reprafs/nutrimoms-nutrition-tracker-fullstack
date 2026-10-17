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
    $userId    = (int)$_SESSION['user_id'];
    $profileId = (int)($_SESSION['active_profile_id'] ?? 0);

    // Guard: active_profile_id harus ada dan benar-benar milik user ini
    if ($profileId <= 0) {
        echo json_encode(["success" => false, "message" => "Profil aktif tidak ditemukan, silakan pilih profil."]);
        exit;
    }
    $ownerCheck = $db->prepare("SELECT id FROM profiles WHERE id = ? AND user_id = ? LIMIT 1");
    $ownerCheck->execute([$profileId, $userId]);
    if (!$ownerCheck->fetchColumn()) {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "Profil tidak valid."]);
        exit;
    }

    $data  = json_decode(file_get_contents("php://input"), true);
    $items = $data['items'] ?? null;

    if (!$data || !is_array($items) || count($items) === 0) {
        echo json_encode(["success" => false, "message" => "Data items tidak ditemukan"]);
        exit;
    }

    // ── Log date ──────────────────────────────────────────────────────────────
    $today   = date('Y-m-d');
    $logDate = trim($data['log_date'] ?? '');
    if (empty($logDate) || $logDate > $today) $logDate = $today;

    $db->beginTransaction();

    // ── Cek / buat dietary_log ────────────────────────────────────────────────
    $checkLog = $db->prepare("
        SELECT id FROM dietary_logs
        WHERE profile_id = ? AND log_date = ? LIMIT 1
    ");
    $checkLog->execute([$profileId, $logDate]);
    $existingLog = $checkLog->fetch();

    if ($existingLog) {
        $logId = $existingLog['id'];
    } else {
       $db->prepare("
            INSERT INTO dietary_logs
                (profile_id, log_date,
                 total_calories, total_protein, total_carbs, total_fat,
                 total_sugar, total_sodium, total_fiber,
                 total_saturated_fat, total_cholesterol, total_omega_3)
            VALUES (?, ?, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0)
        ")->execute([$profileId, $logDate]);
        $logId = $db->lastInsertId();
    }

    // ── Ambil piece_gram untuk setiap food_id yang dipakai ───────────────────
    $foodIds = array_unique(array_filter(array_column($items, 'food_id')));
    $foodMeta = [];

    if (!empty($foodIds)) {
        $placeholders = implode(',', array_fill(0, count($foodIds), '?'));
        $foodStmt = $db->prepare("
            SELECT id, base_gram, piece_gram,
                   calories, protein, carbs, fat,
                   sugar, sodium, fiber, saturated_fat, cholesterol, omega_3
            FROM foods WHERE id IN ($placeholders)
        ");
        $foodStmt->execute(array_values($foodIds));
         foreach ($foodStmt->fetchAll() as $f) {
            $foodMeta[(int)$f['id']] = [
                'base_gram'      => (float)$f['base_gram'],
                'piece_gram'     => $f['piece_gram'] !== null ? (float)$f['piece_gram'] : null,
                'calories'       => (float)$f['calories'],
                'protein'        => (float)$f['protein'],
                'carbs'          => (float)$f['carbs'],
                'fat'            => (float)$f['fat'],
                'sugar'          => (float)($f['sugar']         ?? 0),
                'sodium'         => (float)($f['sodium']        ?? 0),
                'fiber'          => (float)($f['fiber']         ?? 0),
                'saturated_fat'  => (float)($f['saturated_fat'] ?? 0),
                'cholesterol'    => (float)($f['cholesterol']   ?? 0),
                'omega_3'        => (float)($f['omega_3']       ?? 0),
            ];
        }
    }

    // ── Insert items ──────────────────────────────────────────────────────────
    $allowedMealTypes = ['pagi', 'siang', 'malam', 'snack'];

    $insertItem = $db->prepare("
        INSERT INTO dietary_items
            (dietary_log_id, food_id, quantity_type, quantity_value,
             calories, protein, carbs, fat,
             sugar, sodium, fiber, saturated_fat, cholesterol, omega_3,
             meal_type)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($items as $item) {
        $foodId       = $item['food_id']        ?? null;
        $qType        = strtolower($item['quantity_type']  ?? '');
        $qValue       = $item['quantity_value'] ?? null;

        if ($foodId === null || $qType === '' || $qValue === null) continue;

        $meta      = $foodMeta[(int)$foodId] ?? null;
        $baseGram  = $meta['base_gram']  ?? (float)($item['base_gram'] ?? 100);
        $pieceGram = $meta['piece_gram'] ?? null;
        $qty       = (float)$qValue;

        // ── Hitung multiplier dengan piece_gram yang akurat ──────────────────
        if ($qType === 'piece') {
            if ($pieceGram !== null && $baseGram > 0) {
                $multiplier = $qty * ($pieceGram / $baseGram);
            } else {
                $multiplier = $qty; // fallback: 1 piece = 1 serving base_gram
            }
        } else {
            $multiplier = $baseGram > 0 ? $qty / $baseGram : 0;
        }

        // ── Kalkulasi dari DB (akurat, tidak trust nilai frontend) ────────────
        $calories      = round($meta['calories']      * $multiplier, 1);
        $protein       = round($meta['protein']       * $multiplier, 1);
        $carbs         = round($meta['carbs']         * $multiplier, 1);
        $fat           = round($meta['fat']           * $multiplier, 1);
        $sugar         = round($meta['sugar']         * $multiplier, 1);
        $sodium        = round($meta['sodium']        * $multiplier, 1);
        $fiber         = round($meta['fiber']         * $multiplier, 1);
        $saturatedFat  = round($meta['saturated_fat'] * $multiplier, 1);
        $cholesterol   = round($meta['cholesterol']   * $multiplier, 1);
        $omega3        = round($meta['omega_3']       * $multiplier, 1);

        // ── Meal type ─────────────────────────────────────────────────────────
        $mealType = strtolower(trim($item['meal_type'] ?? 'pagi'));
        if (!in_array($mealType, $allowedMealTypes, true)) $mealType = 'pagi';

        $insertItem->execute([
            (int)$logId,
            (int)$foodId,
            $qType,
            $qty,
            $calories,
            $protein,
            $carbs,
            $fat,
            $sugar,
            $sodium,
            $fiber,
            $saturatedFat,
            $cholesterol,
            $omega3,
            $mealType
        ]);
    }

    // ── Recalculate log totals ────────────────────────────────────────────────
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
        "success" => true,
        "log_id"  => (int)$logId,
        "message" => "Log berhasil disimpan"
    ]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>