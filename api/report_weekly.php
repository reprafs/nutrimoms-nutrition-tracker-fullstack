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
    $start     = trim($_GET['start'] ?? '') ?: date('Y-m-d');
    $end       = trim($_GET['end']   ?? '') ?: date('Y-m-d');
    $groupMeal = isset($_GET['group_meal']) && $_GET['group_meal'] == '1';

    if ($groupMeal) {
        // ── Per (log_date, meal_type) untuk stacked bar chart ──
        // dietary_logs punya total_* tapi tidak per meal_type,
        // jadi kita SUM dari dietary_items yang punya meal_type
        $query = $db->prepare("
            SELECT
                dl.log_date,
                di.meal_type,
                SUM(di.calories)      AS total_calories,
                SUM(di.protein)       AS total_protein,
                SUM(di.carbs)         AS total_carbs,
                SUM(di.fat)           AS total_fat,
                SUM(di.sugar)         AS total_sugar,
                SUM(di.sodium)        AS total_sodium,
                SUM(di.fiber)         AS total_fiber,
                SUM(di.saturated_fat) AS total_saturated_fat,
                SUM(di.cholesterol)   AS total_cholesterol,
                SUM(di.omega_3)       AS total_omega_3
            FROM dietary_logs dl
            JOIN dietary_items di ON di.dietary_log_id = dl.id
            WHERE dl.profile_id = ?
              AND dl.log_date BETWEEN ? AND ?
            GROUP BY dl.log_date, di.meal_type
            ORDER BY dl.log_date ASC,
                     FIELD(di.meal_type, 'pagi','siang','malam','snack')
        ");
        $query->execute([$profileId, $start, $end]);

    } else {
        // ── Total per hari langsung dari dietary_logs (sudah diaggregasi) ──
        $query = $db->prepare("
            SELECT
                log_date,
                total_calories,
                total_protein,
                total_carbs,
                total_fat,
                total_sugar,
                total_sodium,
                total_fiber,
                total_saturated_fat,
                total_cholesterol,
                total_omega_3
            FROM dietary_logs
            WHERE profile_id = ?
              AND log_date BETWEEN ? AND ?
            ORDER BY log_date ASC
        ");
        $query->execute([$profileId, $start, $end]);
    }

    $logs = $query->fetchAll(PDO::FETCH_ASSOC);

    $numericCols = [
        'total_calories','total_protein','total_carbs','total_fat',
        'total_sugar','total_sodium','total_fiber',
        'total_saturated_fat','total_cholesterol','total_omega_3'
    ];
    $logs = array_map(function($row) use ($numericCols) {
        foreach ($numericCols as $col) {
            if (isset($row[$col])) $row[$col] = (float)$row[$col];
        }
        return $row;
    }, $logs);

    echo json_encode(['success' => true, 'logs' => $logs]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>