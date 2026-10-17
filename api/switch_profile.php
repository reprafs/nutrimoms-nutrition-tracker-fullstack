<?php
/**
 * api/switch_profile.php
 * ─────────────────────────────────────────────────────────────────────
 * Endpoint POST (JSON) untuk mengganti active_profile_id di session.
 * Validasi ketat: profile_id yang dikirim HARUS milik user yang login.
 * ─────────────────────────────────────────────────────────────────────
 */

session_start();
header('Content-Type: application/json');

// ── Auth check ────────────────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// ── Parse JSON body ───────────────────────────────────────────────────
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

$newProfileId = isset($data['profile_id']) ? (int)$data['profile_id'] : 0;

if ($newProfileId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Profile ID tidak valid.']);
    exit;
}

// ── Validasi: profil harus benar-benar milik user ini ─────────────────
require_once '../config/db.php';
$db     = getDB();
$userId = (int)$_SESSION['user_id'];

$check = $db->prepare(
    "SELECT id FROM profiles WHERE id = ? AND user_id = ? LIMIT 1"
);
$check->execute([$newProfileId, $userId]);
$owned = $check->fetchColumn();

if (!$owned) {
    // Profil tidak ditemukan atau bukan milik user ini — tolak
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Profil tidak ditemukan.']);
    exit;
}

// ── Switch! ───────────────────────────────────────────────────────────
$_SESSION['active_profile_id'] = $newProfileId;

echo json_encode([
    'success'            => true,
    'active_profile_id'  => $newProfileId,
    'message'            => 'Profil berhasil diganti.'
]);
exit;