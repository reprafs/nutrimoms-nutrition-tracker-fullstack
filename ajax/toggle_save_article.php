<?php
// ajax/toggle_save_article.php
// Handler AJAX untuk save/unsave artikel (level user_id)

session_start();
header('Content-Type: application/json');

// Pastikan user sudah login
if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized', 'code' => 401]);
    exit;
}

// Hanya terima POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Ambil & validasi article_id
$articleId = isset($_POST['article_id']) ? (int)$_POST['article_id'] : 0;
if ($articleId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid article ID']);
    exit;
}

$userId = (int)$_SESSION['user_id'];

try {
    // Sesuaikan path config database sesuai struktur proyekmu
    require_once __DIR__ . '/../config/db.php';  // 2 level up: ajax/ → root → config/
    $db = getDB();

    // Cek apakah sudah disimpan
    $checkSql = "SELECT id FROM saved_articles WHERE user_id = ? AND article_id = ? LIMIT 1";
    $check    = $db->prepare($checkSql);
    $check->execute([$userId, $articleId]);
    $existing = $check->fetchColumn();

    if ($existing) {
        // Sudah disimpan → hapus (unsave)
        $del = $db->prepare("DELETE FROM saved_articles WHERE user_id = ? AND article_id = ?");
        $del->execute([$userId, $articleId]);
        echo json_encode([
            'success' => true,
            'saved'   => false,
            'message' => 'Artikel dihapus dari simpanan'
        ]);
    } else {
        // Belum disimpan → simpan
        $ins = $db->prepare("INSERT INTO saved_articles (user_id, article_id) VALUES (?, ?)");
        $ins->execute([$userId, $articleId]);
        echo json_encode([
            'success' => true,
            'saved'   => true,
            'message' => 'Artikel berhasil disimpan'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}