<?php
session_start();
include('db.php'); 

if (isset($_POST['movie_id']) && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $movie_id = $_POST['movie_id'];

    
    $query = "DELETE FROM user_favorites WHERE user_id = ? AND movie_id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$user_id, $movie_id]);

    echo json_encode(['status' => 'success']);
}
?>
