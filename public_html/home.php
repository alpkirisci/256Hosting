<?php
include_once "db.php";
include_once "header.php";

try {
    //5 film seçmek için sorgu
    $query = $db->query("SELECT * FROM movies ORDER BY RAND() LIMIT 5");
    $movies = $query->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $ex) {
    die("Database Query Error: " . $ex->getMessage());
}

?>

<div class="slider sliderv2">
    <div class="container">
        <div class="row">
            <div class="slider-single-item">
                <?php foreach ($movies as $movie): ?>
                <div class="movie-item">
                    <div class="row">
                        <div class="col-md-8 col-sm-12 col-xs-12">
                            <div class="title-in">
                                <div class="cate">
                                    <?php 
                                    $categories = explode(',', $movie['category']); 
                                    foreach ($categories as $category): ?>
                                        <span class="blue"><a href="#"><?= htmlspecialchars(trim($category)) ?></a></span>
                                    <?php endforeach; ?>
                                </div>
                                <h1>
                                    <a href="#">
                                        <?= htmlspecialchars($movie['title']) ?><br>
                                        <span><?= htmlspecialchars($movie['year']) ?></span>
                                    </a>
                                </h1>
                                <div class="social-btn">
                                    <a href="<?= htmlspecialchars($movie['trailer']) ?>" class="parent-btn"><i class="ion-play"></i> Watch Trailer</a>
                                    
                                </div>
							
                                <div class="mv-details">
                                    <p><i class="ion-android-star"></i><span><?= htmlspecialchars($movie['rate']) ?></span>/10</p>
									
                                    <ul class="mv-infor">
									
                                        <li><?= htmlspecialchars($movie['overview']) ?></li>
                                    </ul>
                                </div>
                                <div class="btn-transform transform-vertical">
                                    <div><a href="moviesingle.php?id=<?= htmlspecialchars($movie['id']) ?>" class="item item-1 redbtn">more detail</a></div>
                                    <div><a href="moviesingle.php?id=<?= htmlspecialchars($movie['id']) ?>" class="item item-2 redbtn hvrbtn">more detail</a></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-12 col-xs-12">
                            <div class="mv-img-2">
                                <a href="#"><img src="<?= htmlspecialchars($movie['image']) ?>" alt="<?= htmlspecialchars($movie['title']) ?>"></a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>


<div class="movie-items full-width">
    <div class="row">
        <div class="col-md-12">
            <div class="title-hd">
                <h2>Top 5</h2>
            </div>
            <div class="tabs">
                <div class="tab-content">
                    <div id="tab1-h2" class="tab active">
                        <div class="row">
                            <div class="slick-multiItem2">
                                <?php
                                // en yüksek puanlı 5 farklı filmi al
                                $query = "SELECT id, title, rate, image FROM movies 
                                          GROUP BY id, title, rate, image 
                                          ORDER BY rate DESC, id ASC LIMIT 5";
                                $stmt = $db->prepare($query);
                                $stmt->execute();
                                $top_movies = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                // Her bir filmi HTML'ye ekle
                                foreach ($top_movies as $movie): ?>
                                    <div class="slide-it">
                                        <div class="movie-item">
                                            <div class="mv-img">
                                                <img src="<?= htmlspecialchars($movie['image']) ?>" alt="<?= htmlspecialchars($movie['title']) ?>">
                                            </div>
                                            <div class="hvr-inner">
                                                <a href="moviesingle.php?id=<?= htmlspecialchars($movie['id']) ?>"> Read more <i class="ion-android-arrow-dropright"></i> </a>
                                            </div>
                                            <div class="title-in">
                                                <h6><a href="moviesingle.php?id=<?= htmlspecialchars($movie['id']) ?>"><?= htmlspecialchars($movie['title']) ?></a></h6>
                                                <p><i class="ion-android-star"></i><span><?= htmlspecialchars($movie['rate']) ?></span> /10</p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>



<?php
require_once "footer.php";
?>

<script src="js/jquery.js"></script>
<script src="js/plugins.js"></script>
<script src="js/plugins2.js"></script>
<script src="js/custom.js"></script>
</body>

<!-- homev207:28-->
</html>