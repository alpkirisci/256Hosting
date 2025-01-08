<?php
include_once "db.php";
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

$username = $_SESSION['username']; 
$movie_id = isset($_POST['movie_id']) ? (int)$_POST['movie_id'] : 0;

if ($movie_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid movie ID']);
    exit;
}

try {
    // Kullanıcının favorilere ekleyip eklemediğini kontrol et
    $checkSql = "SELECT * FROM user_favorites WHERE user = :user AND movie_id = :movie_id";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->execute([':user' => $username, ':movie_id' => $movie_id]);
    $isFavorite = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($isFavorite) {
        
        $deleteSql = "DELETE FROM user_favorites WHERE user = :user AND movie_id = :movie_id";
        $deleteStmt = $db->prepare($deleteSql);
        $deleteStmt->execute([':user' => $username, ':movie_id' => $movie_id]);
        echo json_encode(['status' => 'removed']);
    } else {
        
        $insertSql = "INSERT INTO user_favorites (user, movie_id) VALUES (:user, :movie_id)";
        $insertStmt = $db->prepare($insertSql);
        $insertStmt->execute([':user' => $username, ':movie_id' => $movie_id]);
        echo json_encode(['status' => 'added']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>

