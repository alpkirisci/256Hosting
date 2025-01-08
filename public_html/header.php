<?php
include_once "db.php";


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}



if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Logout işlemi
if (isset($_GET['oppType']) && $_GET['oppType'] == 'logout') {
    session_unset();  // Oturumdaki tüm verileri temizle
    session_destroy();  // Oturumu tamamen sonlandır
    header("Location:index.php");
    exit;
}


// Giriş ve Kayıt İşlemleri
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF Token Doğrulama (Opsiyonel)
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Geçersiz istek!");
    }

    // Giriş işlemi
    if (isset($_POST['login_type'])) {
        $login_type = $_POST['login_type'] ?? '';
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        try {
            // Veritabanında kullanıcıyı arıyoruz
            $stmt = $db->prepare("SELECT * FROM users WHERE username = :username");
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {  
                // Giriş başarılı
                if ($login_type === 'admin_content_creator') {
                    if ($user['role'] === 'admin' || $user['role'] === 'content_creator') {
                        session_regenerate_id(true); 
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['role'] = $user['role'];
                        header("Location: admin_content_creator_dashboard.php");  
                        exit;
                    } else {
                        // Kullanıcı admin veya content_creator değil
                        echo "<p style='color:red;'>Bu alana erişim yetkiniz yok!</p>";
                    }
                } else {
                    // Normal kullanıcı girişi
                    session_regenerate_id(true); 
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    header("Location: index.php");  
                    exit;
                }
            } else {
                
                echo "<p style='color:red;'>Geçersiz kullanıcı adı veya şifre!</p>";
            }
        } catch (PDOException $e) {
            echo "Hata: " . $e->getMessage();
        }
    }
    // Kayıt işlemi
    else if (isset($_POST['role'])) {
        
        $username = trim($_POST['username'] ?? '');
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $repassword = trim($_POST['repassword'] ?? '');
        $role = $_POST['role'] ?? 'user'; 
        $avatar = "avatars/logo.png"; 

        // Hata mesajları için bir dizi
        $errors = [];

        // Kullanıcı adı doğrulama
        if (!preg_match("/^[a-zA-Z][a-zA-Z0-9-_\.]{8,20}$/", $username)) {
            $errors[] = "Kullanıcı adı 8-20 karakter uzunluğunda olmalı ve yalnızca harf, rakam, tire ve alt çizgi içerebilir.";
        }

        // Ad ve soyad doğrulama
        if (empty($first_name) || empty($last_name)) {
            $errors[] = "Ad ve soyad alanları boş bırakılamaz.";
        }

        // Email doğrulama
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Geçerli bir email adresi girin.";
        }

        // Şifre doğrulama
        if (!preg_match("/(?=^.{8,}$)((?=.*\d)|(?=.*\W+))(?![.\n])(?=.*[A-Z])(?=.*[a-z]).*$/", $password)) {
            $errors[] = "Şifre en az 8 karakter uzunluğunda olmalı, büyük harf, küçük harf ve sayı içermelidir.";
        }

        // Şifre eşleşme kontrolü
        if ($password !== $repassword) {
            $errors[] = "Şifreler eşleşmiyor.";
        }

        // Hatalar yoksa veritabanına kaydet
        if (empty($errors)) {
            // Şifreyi hashleyerek güvenli hale getirme
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            try {
                
                $stmt = $db->prepare("INSERT INTO users (username, first_name, last_name, email, password, role, avatar) 
                                      VALUES (:username, :first_name, :last_name, :email, :password, :role, :avatar)");
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':first_name', $first_name);
                $stmt->bindParam(':last_name', $last_name);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':password', $hashedPassword);
                $stmt->bindParam(':role', $role);
                $stmt->bindParam(':avatar', $avatar);

                if ($stmt->execute()) {
                    echo "<p style='color: green;'>Kayıt başarılı!</p>";
                } else {
                    echo "<p style='color: red;'>Bir hata oluştu, lütfen tekrar deneyin.</p>";
                }
            } catch (PDOException $e) {
                echo "Hata: " . $e->getMessage();
            }
        } else {
            
            foreach ($errors as $error) {
                echo "<p style='color: red;'>$error</p>";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="no-js">

<head>
    <!-- Basic need -->
    <title>Open Pediatrics</title>
    <meta charset="UTF-8">
    <meta name="description" content="">
    <meta name="keywords" content="">
    <meta name="author" content="">
    <link rel="profile" href="#">

    <!--Google Font-->
    <link rel="stylesheet" href='http://fonts.googleapis.com/css?family=Dosis:400,700,500|Nunito:300,400,600' />
    <!-- Mobile specific meta -->
    <meta name=viewport content="width=device-width, initial-scale=1">
    <meta name="format-detection" content="telephone-no">

    <!-- CSS files -->
    <link rel="stylesheet" href="css/plugins.css">
    <link rel="stylesheet" href="css/style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <style>
        /* search bar css'i */
.top-search {
    margin: 8px 0;
    text-align: center;
}

.top-search input[type="text"] {
    padding: 10px;
    width: 100%;
    border: 1px solid #ccc;
    border-radius: 5px;
    font-size: 16px;
}

.top-search button {
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    color: white;
    font-size: 16px;
    cursor: pointer;
}

.top-search button:hover {
    background-color: #0056b3;
}
    .search-result-item {
        background-color: #007BFF;
        color: white;
        padding: 10px;
        margin: 5px 0;
        border-radius: 5px;
        list-style-type: none;
        z-index: 1000;
        position: relative;
    }

    .search-result-item a {
        color: white;
        text-decoration: none;
    }

    .search-result-item a:hover {
        text-decoration: underline;
    }

   #search-results {
        position: absolute;
        z-index: 1000;
        background-color: white;
        width: calc(80%  - 10px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        border-radius: 5px;
   }

    </style>

</head>

<body>
    <!--preloading-->
    <div id="preloader">
        <img class="logo" src="images/logo1.png" alt="" width="119" height="58">
        <div id="status">
            <span></span>
            <span></span>
        </div>
    </div>
    <!--end of preloading-->

    <!--login form popup-->
    <div class="login-wrapper" id="login-content">
        <div class="login-content">
            <a href="#" class="close">x</a>
            <h3>Login</h3>
            <form method="post" action="#">
                <!-- Gizli login_type alanı eklendi -->
                <input type="hidden" name="login_type" value="user">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">
                <div class="row">
                    <label for="username">
                        Username:
                        <input type="text" name="username" id="username" placeholder="Hugh Jackman" pattern="^[a-zA-Z][a-zA-Z0-9-_\.]{8,20}$" required="required" />
                    </label>
                </div>

                <div class="row">
                    <label for="password">
                        Password:
                        <input type="password" name="password" id="password" placeholder="******" pattern="(?=^.{8,}$)((?=.*\d)|(?=.*\W+))(?![.\n])(?=.*[A-Z])(?=.*[a-z]).*$" required="required" />
                    </label>
                </div>
                <div class="row">
                    <div class="remember">
                        <div>
                            <input type="checkbox" name="remember" value="Remember me"><span>Remember me</span>
                        </div>
                        <a href="#">Forget password ?</a>
                    </div>
                </div>
                <div class="row">
                    <button type="submit">Login</button>
                </div>
            </form>
            <div class="row">
                <p>Or via social</p>
                <div class="social-btn-2">
                    <a class="fb" href="#"><i class="ion-social-facebook"></i>Facebook</a>
                    <a class="tw" href="#"><i class="ion-social-twitter"></i>twitter</a>
                </div>
            </div>
        </div>
    </div>
    <!--end of login form popup-->

    <!--login-admin-content form popup-->
    <div class="login-wrapper" id="login-admin-content">
        <div class="login-content">
            <a href="#" class="close">x</a>
            <h3>Admin/Content Creator Login</h3>
            <form method="post" action="#">
                <!-- Gizli login_type alanı eklendi -->
                <input type="hidden" name="login_type" value="admin_content_creator">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">
                <div class="row">
                    <label for="admin-username">
                        Username:
                        <input type="text" name="username" id="admin-username" placeholder="AdminUsername" required="required" />
                    </label>
                </div>

                <div class="row">
                    <label for="admin-password">
                        Password:
                        <input type="password" name="password" id="admin-password" placeholder="******" required="required" />
                    </label>
                </div>
                <div class="row">
                    <div class="remember">
                        <div>
                            <input type="checkbox" name="remember" value="Remember me"><span>Remember me</span>
                        </div>
                        <a href="#">Forget password?</a>
                    </div>
                </div>
                <div class="row">
                    <button type="submit">Login</button>
                </div>
            </form>
            <div class="row">
                <p>Or via social</p>
                <div class="social-btn-2">
                    <a class="fb" href="#"><i class="ion-social-facebook"></i>Facebook</a>
                    <a class="tw" href="#"><i class="ion-social-twitter"></i>twitter</a>
                </div>
            </div>
        </div>
    </div>
    <!-- End of Admin/Content Creator Login Form Popup -->

    <!--signup form popup-->
    <div class="login-wrapper" id="signup-content">
        <div class="login-content">
            <a href="#" class="close">x</a>
            <h3>Sign Up</h3>
            <form method="post" action="#">
                <!-- CSRF Token Eklenmesi -->
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">
                <!-- Gizli role alanı eklendi -->
                <input type="hidden" name="role" value="user">
                <div class="row">
                    <label for="username-2">
                        Username:
                        <input type="text" name="username" id="username-2" placeholder="Hugh Jackman" pattern="^[a-zA-Z][a-zA-Z0-9-_\.]{8,20}$" required="required" />
                    </label>
                </div>
                <div class="row">
                    <label for="first-name">
                        First Name:
                        <input type="text" name="first_name" id="first-name" placeholder="Hugh" required="required" />
                    </label>
                </div>
                <div class="row">
                    <label for="last-name">
                        Last Name:
                        <input type="text" name="last_name" id="last-name" placeholder="Jackman" required="required" />
                    </label>
                </div>
                <div class="row">
                    <label for="email-2">
                        Your Email:
                        <input type="email" name="email" id="email-2" placeholder="example@mail.com" required="required" />
                    </label>
                </div>
                <div class="row">
                    <label for="password-2">
                        Password:
                        <input type="password" name="password" id="password-2" placeholder="******" required="required" />
                    </label>
                </div>
                <div class="row">
                    <label for="repassword-2">
                        Re-type Password:
                        <input type="password" name="repassword" id="repassword-2" placeholder="******" required="required" />
                    </label>
                </div>
                <!-- Role seçimi eklendi -->
                <div class="row">
                    <label for="role">
                        Role:
                        <select name="role" id="role" required="required">
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                            <option value="content_creator">Content Creator</option>
                        </select>
                    </label>
                </div>
                <div class="row">
                    <button type="submit">Sign Up</button>
                </div>
            </form>
        </div>
    </div>
    <!--end of signup form popup-->

    <!-- BEGIN | Header -->
    <header class="ht-header">
        <div class="container">
            <nav class="navbar navbar-default navbar-custom">
                <div class="navbar-header logo">
                    <div class="navbar-toggle" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
                        <span class="sr-only">Toggle navigation</span>
                        <div id="nav-icon1">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                    </div>
                    <a href="index.php"><img class="logo" src="images/logo1.png" alt="" width="119" height="58"></a>
                </div>
                <div class="collapse navbar-collapse flex-parent" id="bs-example-navbar-collapse-1">
                    <ul class="nav navbar-nav flex-child-menu menu-left">
                    <li class="dropdown first">
                        <a class="btn btn-default dropdown-toggle lv1" href="home.php" data-toggle="dropdown" data-hover="dropdown">
                            Home
                        </a>
                        <ul class="dropdown-menu level1">
                            
                                <li><a href="home.php">Home</a></li>

                            </ul>
                    </li>
                        <li class="dropdown first">
                            <a class="btn btn-default dropdown-toggle lv1" data-toggle="dropdown" data-hover="dropdown">
                                movie list <i class="fa fa-angle-down" aria-hidden="true"></i>
                            </a>
                            <ul class="dropdown-menu level1">
                            
                                <li><a href="movielist.php">Movie list</a></li>

                            </ul>
                        </li>
                        
                        <!-- <li class="dropdown first">
                            <a class="btn btn-default dropdown-toggle lv1" data-toggle="dropdown" data-hover="dropdown">
                                community <i class="fa fa-angle-down" aria-hidden="true"></i>
                            </a>
                            <ul class="dropdown-menu level1">
                                <li><a href="userfavoritegrid.php">User favorite grid</a></li>
                                <li><a href="userprofile.php">User profile</a></li>
                            </ul>
                        </li> -->
                    </ul>
                   
                    <ul class="nav navbar-nav flex-child-menu menu-right">
                        <?php if (isset($_SESSION['username'])): ?>
                            <!-- Kullanıcı oturum açmış -->
                            <li><span class="navbar-text">Hello <?= htmlspecialchars($_SESSION['username']); ?>!</span></li>
                            
                            <?php if (isset($_SESSION['role'])): ?>
                                <?php if ($_SESSION['role'] === 'admin'): ?>
                                    <li><a href="admin_dashboard.php">Admin Dashboard</a></li>
                                <?php elseif ($_SESSION['role'] === 'content_creator'): ?>
                                    <li><a href="content_dashboard.php">Content Dashboard</a></li>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <li><a href="?oppType=logout">Log Out</a></li>
                        <?php else: ?>
                            <!-- Kullanıcı oturum açmamış -->
                            <li class="loginLink"><a href="#login-content">Log In</a></li>
                            <li class="btn signupLink"><a href="#signup-content">Sign Up</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </nav>
            <!-- <div class="top-search">
                <input type="text" id="search-input" placeholder="Search for a movie 🍿">
                <ul id="suggestions-list" style="display: none; border: 1px solid #ccc; max-height: 200px; overflow-y: auto; position: absolute; width: 100%; background: white; list-style-type: none; padding: 0;">
                </ul>
                
            </div> -->
            <form>
                <input type="text" id="search-bar" placeholder="Search for a movie ✾">
                <div id="search-results"></div>
            </form>
            
	    </div>
        
    </header>
    <!-- END | Header -->

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    $(document).ready(function () {
        $('#search-bar').on('input', function () {
            var query = $(this).val();

            if (query.length > 0) {
                $.ajax({
                    url: 'search_suggestions2.php',
                    method: 'GET',
                    data: { query: query },
                    dataType: 'json', // JSON olarak yanıt bekliyoruz
                    success: function (response) {
                        console.log("Cevap alındı:", response);

                        var resultsDiv = $('#search-results');
                        resultsDiv.empty();

                        if (response.length > 0) {
                            var ul = $('<ul></ul>');
                            response.forEach(function (movie) {
                                var li = $('<li></li>');
                                var link = $('<a></a>');
                                
                                link.attr('href', 'moviesingle.php?id=' + movie.id);
                                link.text(movie.title);

                                li.append(link);
                                li.addClass('search-result-item');
                                ul.append(li);
                            });
                            resultsDiv.append(ul);
                        } else {
                            resultsDiv.append('<p>No results found</p>');
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('Error:', error);
                    }
                });
            } else {
                $('#search-results').empty();
            }
        });
    });
</script>

    <!-- Diğer içerikler ... -->

    <!-- JavaScript dosyalarınız -->
    <script src="js/jquery.min.js"></script>
    <script src="js/plugins.js"></script>
    <script src="js/main.js"></script>

    <!-- Popup İşlemleri için Eklenen JavaScript -->
    <!-- <script>
    $(document).ready(function(){
        $('.loginLink, .signupLink').on('click', function(e){
            e.preventDefault();
            var target = $(this).attr('href');
            $('.login-wrapper').hide(); // Tüm popup'ları gizle
            $(target).fadeIn(); // Hedef popup'ı göster
        });

        $('.close').on('click', function(e){
            e.preventDefault();
            $(this).closest('.login-wrapper').fadeOut();
        });
    });
    </script> -->

</body>
</html>
