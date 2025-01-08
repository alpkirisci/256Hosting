<?php
session_start();
include('db.php'); // Veritabanı bağlantısı

if (isset($_POST['movie_id']) && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['username']; // Oturumdaki kullanıcı ID'si
    $movie_id = $_POST['movie_id']; // Film ID'si

    // Favorilere eklemek için SQL sorgusu
    $query = "INSERT INTO user_favorites (user_id, movie_id) VALUES (?, ?)";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$user_id, $movie_id]);

    echo json_encode(['status' => 'success']);
}
?>
