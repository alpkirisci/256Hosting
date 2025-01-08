<?php
include_once "header.php";

$query = "SELECT * FROM movies LIMIT 10";  
$stmt = $db->prepare($query);
$stmt->execute();
$movies = $stmt->fetchAll(PDO::FETCH_ASSOC);


?>

<div class="slider movie-items">
    <div class="container">
        <div class="row">
            <div class="social-link">
                <p>Follow us: </p>
                <a href="#"><i class="ion-social-facebook"></i></a>
                <a href="#"><i class="ion-social-twitter"></i></a>
                <a href="#"><i class="ion-social-googleplus"></i></a>
                <a href="#"><i class="ion-social-youtube"></i></a>
            </div>
            <div class="slick-multiItemSlider">
                <?php foreach ($movies as $movie): ?>
                <div class="movie-item">
                    <div class="mv-img">
                        <a href="#"><img src="<?php echo htmlspecialchars($movie['image']); ?>" alt="" width="285" height="437"></a>
                    </div>
                    <div class="title-in">
                        <div class="cate">
                            <span class="blue"><a href="#"><?php echo htmlspecialchars($movie['category']); ?></a></span>
                        </div>
                        <h6><a href="moviesingle.php?id=<?php echo $movie['id']; ?>"><?php echo htmlspecialchars($movie['title']); ?> <span>(<?php echo htmlspecialchars($movie['year']); ?>)</span></a></h6>
                        <p><i class="ion-android-star"></i><span><?php echo htmlspecialchars($movie['rate']); ?></span> /10</p>
                    </div>
                </div>
                <?php endforeach; ?>
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
<script>
	document.getElementById('login-form').addEventListener('submit', function (e) {
    e.preventDefault(); 

    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;

    fetch('login_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ username, password })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Giriş başarılı, navbar'ı güncelle.
                document.querySelector('.menu-right').innerHTML = `
                    <li><a href="#" onclick="logout()">Logout</a></li>
                    <li><span class="navbar-text">Hello ${data.username}!</span></li>
                `;
                document.getElementById('login-content').style.display = 'none';
            } else {
                alert('Invalid username or password.');
            }
        })
        .catch(error => console.error('Error:', error));
});

function logout() {
    fetch('logout.php', { method: 'POST' })
        .then(() => location.reload());
}

</script>
</body>


</html>
