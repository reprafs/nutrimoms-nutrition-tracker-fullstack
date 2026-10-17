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
    $db       = getDB();
    $search   = trim($_GET['search']   ?? '');
    $category = trim($_GET['category'] ?? '');

    $query  = "SELECT id, food_name, image, calories, protein, carbs, fat, base_gram, piece_gram, category FROM foods WHERE 1=1";
    $params = [];

    if (!empty($search)) {
        $query   .= " AND food_name LIKE ?";
        $params[] = "%" . $search . "%";
    }

    if (!empty($category)) {
        $query   .= " AND category = ?";
        $params[] = $category;
    }

    $query .= " ORDER BY food_name ASC";

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $foods = $stmt->fetchAll();

    // Cast piece_gram: null stays null, otherwise float
    $foods = array_map(function($f) {
        $f['piece_gram'] = isset($f['piece_gram']) && $f['piece_gram'] !== null
            ? (float)$f['piece_gram']
            : null;
        return $f;
    }, $foods);

    echo json_encode(["success" => true, "data" => $foods]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Server error"]);
}
?>