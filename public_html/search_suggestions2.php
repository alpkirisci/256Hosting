<?php
// Hata ayıklama için hataları göster
error_reporting(E_ALL);
ini_set('display_errors', 1);


include_once "db.php";


header('Content-Type: application/json');


if (isset($_GET['query'])) {
    $query = $_GET['query'];

    try {
        
        $sql = "SELECT id, title FROM movies WHERE title LIKE :searchTerm";
        $stmt = $db->prepare($sql);

        
        $searchTerm = "%" . $query . "%";
        $stmt->bindParam(':searchTerm', $searchTerm, PDO::PARAM_STR);

        $stmt->execute();

        $movies = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($movies);

    } catch (PDOException $e) {
        
        echo json_encode(["error" => "Query failed: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["error" => "Query parameter not set"]);
}
?>

