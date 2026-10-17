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
    $profileId = $_SESSION['profile_id'] ?? null;

    if (!$profileId) {
        echo json_encode(["success" => false, "message" => "Profile tidak ditemukan"]);
        exit;
    }

    $data = json_decode(file_get_contents("php://input"), true);

    $category = $data['category'] ?? ''; // 'Makan Pagi' | 'Makan Siang' | 'Makan Malam' | 'Camilan' | 'Minum Air'
    $time     = $data['time']     ?? ''; // contoh: "07:30"

    // ── Validasi kategori → mapping type + title ──────────────────────────────
    $categoryMap = [
        'Makan Pagi'  => ['type' => 'reminder_meal',  'title' => 'Waktunya Makan Pagi'],
        'Makan Siang' => ['type' => 'reminder_meal',  'title' => 'Waktunya Makan Siang'],
        'Makan Malam' => ['type' => 'reminder_meal',  'title' => 'Waktunya Makan Malam'],
        'Camilan'     => ['type' => 'reminder_meal',  'title' => 'Waktunya Camilan'],
        'Minum Air'   => ['type' => 'reminder_water', 'title' => 'Waktunya Minum Air'],
    ];

    if (!isset($categoryMap[$category])) {
        echo json_encode(["success" => false, "message" => "Kategori pengingat tidak valid"]);
        exit;
    }

    // ── Validasi format jam (HH:MM, 24 jam) ────────────────────────────────────
    if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $time)) {
        echo json_encode(["success" => false, "message" => "Format jam tidak valid"]);
        exit;
    }

    $type  = $categoryMap[$category]['type'];
    $title = $categoryMap[$category]['title'];
    $body  = $time;

    // ── Insert ke tabel notifications (Prepared Statement) ─────────────────────
    $insert = $db->prepare("
        INSERT INTO notifications (profile_id, type, title, body, is_read, is_deleted, created_at)
        VALUES (?, ?, ?, ?, 0, 0, NOW())
    ");
    $insert->execute([$profileId, $type, $title, $body]);

    $newId = (int)$db->lastInsertId();

    echo json_encode([
        "success" => true,
        "message" => "Pengingat berhasil disimpan",
        "reminder" => [
            'id'    => $newId,
            'type'  => $type,
            'title' => $title,
            'body'  => $body,
        ],
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}