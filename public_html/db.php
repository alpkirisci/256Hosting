<?php

// API Anahtarı
$api_key = "02dfbc4dd724fabae74a1abaa086eaa9";

// API URL'si 
$api_url = "https://api.themoviedb.org/3/movie/popular?api_key=" . $api_key . "&language=en-US&page=1";

// cURL oturumunu başlat
$ch = curl_init();

// API'ye istek gönderirken, Authorization başlığına API anahtarını ekleyin
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// API'den gelen cevabı al
$response = curl_exec($ch);

// cURL oturumunu kapat
curl_close($ch);

// API'den gelen cevabı JSON olarak çözümle
$data = json_decode($response, true);


try {
    $db = new PDO("mysql:host=localhost;dbname=test;charset=utf8mb4", "std", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $ex) {
    die("DB Connect Error: " . $ex->getMessage());
}


/*
// API'dan gelen veriyi işleyin ve veritabanına kaydedin
if (isset($data['results'])) {
    foreach ($data['results'] as $item) {
        // Veritabanına eklemek için hazırlanan SQL sorgusu
        $stmt = $db->prepare("INSERT INTO movies (image, title, year, rate, overview, category, trailer) 
                              VALUES (:image, :title, :year, :rate, :overview, :category, :trailer)");

        // API verilerinden gerekli bilgileri al
        $image = "https://image.tmdb.org/t/p/w500" . $item['poster_path']; // Poster image URL
        $title = $item['title'] ?? 'Unknown'; // Movie title
        $year = substr($item['release_date'], 0, 4); // Extract year from release date
        $rate = $item['vote_average'] ?? 0; // Rating
        $overview = $item['overview'] ?? 'No overview available'; // Overview
        $category = 'Unknown'; // Category - you might need to map this to actual genres
        $trailer = 'No trailer available'; // Trailer URL - you may need to get this from another endpoint

        // Parametreleri bağla
        $stmt->bindParam(':image', $image);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':year', $year);
        $stmt->bindParam(':rate', $rate);
        $stmt->bindParam(':overview', $overview);
        $stmt->bindParam(':category', $category);
        $stmt->bindParam(':trailer', $trailer);

        // Sorguyu çalıştır
        $stmt->execute();
    }

    //echo "Veri başarıyla eklendi.";
} else {
    echo "API'dan veri alınamadı.";
}  */

?>
