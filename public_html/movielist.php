<?php
include_once "header.php";
include_once "db.php";

// Sayfa numarasını al
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$movies_per_page = 5; 
$offset = ($page - 1) * $movies_per_page;

// SQL sorgusu, sadece ilgili sayfadaki filmleri almak için
$sql = "SELECT * FROM movies LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($sql);
$stmt->bindParam(':limit', $movies_per_page, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$movies = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Filmlerin toplam sayısını almak için
$sql_count = "SELECT COUNT(*) FROM movies";
$stmt_count = $db->query($sql_count);
$total_movies = $stmt_count->fetchColumn();
$total_pages = ceil($total_movies / $movies_per_page);
?>

<div class="hero common-hero">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="hero-ct">
                    <h1>Movie Listing - List</h1>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="page-single movie_list">
    <div class="container">
        <div class="row ipad-width2">
            <div class="col-md-8 col-sm-12 col-xs-12">
                <div class="topbar-filter">
                    <p>Found <span><?php echo $total_movies; ?> movies</span> in total</p>
                </div>

                <?php foreach ($movies as $movie): ?>
                    <div class="movie-item-style-2">
					<img src="<?php echo htmlspecialchars($movie['image']); ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>">


                        <div class="mv-item-infor">
						<h6><a href="moviesingle.php?id=<?php echo $movie['id']; ?>"><?php echo htmlspecialchars($movie['title']); ?> <span>(<?php echo htmlspecialchars($movie['year']); ?>)</span></a></h6>

                            <p class="rate"><i class="ion-android-star"></i><span><?php echo htmlspecialchars($movie['rate']); ?></span> /10</p>
                            <p class="describe"><?php echo htmlspecialchars($movie['overview']); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>

                
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>">Previous</a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>" <?php if ($i == $page) echo 'class="active"'; ?>><?php echo $i; ?></a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Footer part -->
<?php
require_once "footer.php";
?>