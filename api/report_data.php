<?php
session_start();
header('Content-Type: application/json');
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $db        = getDB();
    $profileId = (int)($_SESSION['active_profile_id'] ?? 0);
    if ($profileId <= 0) {
        echo json_encode(["success" => false, "message" => "Profil aktif tidak ditemukan."]);
        exit;
    }
    $date      = trim($_GET['date'] ?? '') ?: date('Y-m-d');

    // ── CONSUMED: ambil langsung dari dietary_logs (sudah diaggregasi per hari) ──
    $logQuery = $db->prepare("
        SELECT
            total_calories, total_protein, total_carbs, total_fat,
            total_sugar, total_sodium, total_fiber,
            total_saturated_fat, total_cholesterol, total_omega_3
        FROM dietary_logs
        WHERE profile_id = ? AND log_date = ?
        LIMIT 1
    ");
    $logQuery->execute([$profileId, $date]);
    $log = $logQuery->fetch(PDO::FETCH_ASSOC);

    $consumed = [
        'calories'      => (float)($log['total_calories']      ?? 0),
        'protein'       => (float)($log['total_protein']       ?? 0),
        'carbs'         => (float)($log['total_carbs']         ?? 0),
        'fat'           => (float)($log['total_fat']           ?? 0),
        'sugar'         => (float)($log['total_sugar']         ?? 0),
        'sodium'        => (float)($log['total_sodium']        ?? 0),
        'fiber'         => (float)($log['total_fiber']         ?? 0),
        'saturated_fat' => (float)($log['total_saturated_fat'] ?? 0),
        'cholesterol'   => (float)($log['total_cholesterol']   ?? 0),
        'omega_3'       => (float)($log['total_omega_3']       ?? 0),
    ];

    // ── TARGETS ──
    $targetQuery = $db->prepare("
        SELECT
            target_calories, target_protein, target_carbs, target_fat,
            target_sugar, target_sodium, target_fiber,
            target_saturated_fat, target_cholesterol, target_omega_3
        FROM nutrition_targets
        WHERE profile_id = ?
        LIMIT 1
    ");
    $targetQuery->execute([$profileId]);
    $target = $targetQuery->fetch(PDO::FETCH_ASSOC);

    $targets = [
        'target_calories'      => (float)($target['target_calories']      ?? 0),
        'target_protein'       => (float)($target['target_protein']       ?? 0),
        'target_carbs'         => (float)($target['target_carbs']         ?? 0),
        'target_fat'           => (float)($target['target_fat']           ?? 0),
        'target_sugar'         => (float)($target['target_sugar']         ?? 0),
        'target_sodium'        => (float)($target['target_sodium']        ?? 0),
        'target_fiber'         => (float)($target['target_fiber']         ?? 0),
        'target_saturated_fat' => (float)($target['target_saturated_fat'] ?? 0),
        'target_cholesterol'   => (float)($target['target_cholesterol']   ?? 0),
        'target_omega_3'       => (float)($target['target_omega_3']       ?? 0),
    ];

    // ── FOODS: dari dietary_items JOIN dietary_logs ──
    $foodQuery = $db->prepare("
        SELECT
            f.food_name,
            f.image,
            di.meal_type,
            di.calories,
            di.protein,
            di.carbs,
            di.fat,
            di.sugar,
            di.sodium,
            di.fiber,
            di.saturated_fat,
            di.cholesterol,
            di.omega_3,
            di.created_at,
            dl.log_date
        FROM dietary_logs dl
        JOIN dietary_items di ON di.dietary_log_id = dl.id
        JOIN foods f ON f.id = di.food_id
        WHERE dl.profile_id = ? AND dl.log_date = ?
        ORDER BY di.id ASC
    ");
    $foodQuery->execute([$profileId, $date]);
    $foods = $foodQuery->fetchAll(PDO::FETCH_ASSOC);

    $foods = array_map(function($f) {
        return [
            'food_name'     => $f['food_name'],
            'image'         => $f['image'],
            'meal_type'     => $f['meal_type'] ?? 'pagi',
            'calories'      => (float)$f['calories'],
            'protein'       => (float)$f['protein'],
            'carbs'         => (float)$f['carbs'],
            'fat'           => (float)$f['fat'],
            'sugar'         => (float)$f['sugar'],
            'sodium'        => (float)$f['sodium'],
            'fiber'         => (float)$f['fiber'],
            'saturated_fat' => (float)$f['saturated_fat'],
            'cholesterol'   => (float)$f['cholesterol'],
            'omega_3'       => (float)$f['omega_3'],
            'created_at'    => $f['created_at'],
            'log_date'      => $f['log_date'],
        ];
    }, $foods);

    echo json_encode([
        'success'  => true,
        'consumed' => $consumed,
        'targets'  => $targets,
        'foods'    => $foods,
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>