<?php
include_once "db.php";
include_once "header.php";
session_start();

// Kullanıcı oturumunu kontrol edin
if (!isset($_SESSION['username'])) {
    echo "Lütfen giriş yapın!";
    exit;
}

$username = $_SESSION['username']; // Kullanıcının oturumdaki kullanıcı adı

// Film ID'sini URL'den alıyoruz
$movie_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($movie_id <= 0) {
    header("Location: home.php");
    exit;
}

// Film bilgilerini al
$sql = "SELECT * FROM movies WHERE id = :id";
$stmt = $db->prepare($sql);
$stmt->bindParam(':id', $movie_id, PDO::PARAM_INT);
$stmt->execute();
$movie = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$movie) {
    echo "Film bulunamadı!";
    exit;
}

// Kullanıcının favoriye ekleyip eklemediğini kontrol et
$isFavorite = false;
$checkSql = "SELECT * FROM user_favorites WHERE user = :user AND movie_id = :movie_id";
$checkStmt = $db->prepare($checkSql);
$checkStmt->execute([':user' => $username, ':movie_id' => $movie_id]);
if ($checkStmt->fetch(PDO::FETCH_ASSOC)) {
    $isFavorite = true;
}
?>

<div class="hero mv-single-hero">
    <div class="container">
        <div class="row">
            <div class="col-md-12"></div>
        </div>
    </div>
</div>

<div class="page-single movie-single movie_single">
    <div class="container">
        <div class="row ipad-width2">
            <div class="col-md-4 col-sm-12 col-xs-12">
                <div class="movie-img sticky-sb">
                    <img src="<?php echo htmlspecialchars($movie['image']); ?>" alt="">
                </div>
            </div>
            <div class="col-md-8 col-sm-12 col-xs-12">
                <div class="movie-single-ct main-content">
                    <h1 class="bd-hd"><?php echo htmlspecialchars($movie['title']); ?> <span><?php echo htmlspecialchars($movie['year']); ?></span></h1>
                    <h3><strong>Category</strong> <?php echo htmlspecialchars($movie['category']); ?></h3>
                    <br>
                    <?php if (!empty($movie['rate'])): ?>
                        <h3><strong>Rating:</strong> <?php echo htmlspecialchars($movie['rate']); ?>/10</h3>
                    <?php endif; ?>
                    <br>
                    <div class="social-btn">
                        <button id="add-to-favorite" data-movie-id="<?php echo $movie['id']; ?>">
                            <?php echo $isFavorite ? 'Added to Favorite' : 'Add to Favorite'; ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#add-to-favorite').click(function() {
        var movieId = $(this).data('movie-id');
        var button = $(this);

        $.ajax({
            url: 'userfavoritelist.php',
            method: 'POST',
            data: { movie_id: movieId },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'added') {
                    button.text('Added to Favorite');
                } else if (response.status === 'removed') {
                    button.text('Add to Favorite');
                } else {
                    alert(response.message || 'An error occurred.');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                alert('An error occurred while processing your request.');
            }
        });
    });
});
</script>

<?php
require_once "footer.php";
?>
