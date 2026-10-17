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


    // ── 1) DATA CHART — urut ASC by logged_date, dipakai Line Chart ──────────
    $chartStmt = $db->prepare("
        SELECT weight, logged_date
        FROM profile_weights
        WHERE profile_id = ?
        ORDER BY logged_date ASC
    ");
    $chartStmt->execute([$profileId]);
    $chartRows = $chartStmt->fetchAll(PDO::FETCH_ASSOC);

    $chart = array_map(function ($row) {
        return [
            'weight' => (float)$row['weight'],
            'date'   => $row['logged_date'],
        ];
    }, $chartRows);

    // ── 2) HISTORY LIST — dikelompokkan per tahun, terbaru dulu ───────────────
    $histStmt = $db->prepare("
        SELECT id, weight, logged_date
        FROM profile_weights
        WHERE profile_id = ?
        ORDER BY logged_date DESC
    ");
    $histStmt->execute([$profileId]);
    $histRows = $histStmt->fetchAll(PDO::FETCH_ASSOC);

    $grouped = []; // [ '2026' => [ ['id'=>..,'label'=>'21 Jun','weight'=>15.2], ... ] ]
    foreach ($histRows as $row) {
        $year = date('Y', strtotime($row['logged_date']));
        if (!isset($grouped[$year])) {
            $grouped[$year] = [];
        }
        $grouped[$year][] = [
            'id'     => (int)$row['id'],
            'label'  => date('d M', strtotime($row['logged_date'])),
            'weight' => (float)$row['weight'],
        ];
    }

    // Format jadi array terurut (tahun terbaru dulu, sudah otomatis krn DESC + insertion order)
    $history = [];
    foreach ($grouped as $year => $entries) {
        $history[] = [
            'year'    => $year,
            'entries' => $entries,
        ];
    }

    // Berat terakhir (untuk Kotak 3 dashboard)
    $latest = !empty($histRows) ? (float)$histRows[0]['weight'] : null;

    echo json_encode([
        "success" => true,
        "chart"   => $chart,
        "history" => $history,
        "latest"  => $latest,
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}