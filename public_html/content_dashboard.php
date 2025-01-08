<?php
// content_dashboard.php

include_once "db.php";

session_start();

// Hata raporlamayı etkinleştir (Geliştirme aşamasında)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Kullanıcı oturum açmış mı ve content_creator mı kontrol et
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'content_creator') {
    header("Location: index.php");
    exit;
}

// CSRF Token Oluşturma (Opsiyonel Güvenlik Önlemi)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Hata ve başarı mesajları için değişkenler
$errors = [];
$success = "";

// Yeni film oluşturma işlemi
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_movie'])) {
    // CSRF Token Doğrulama
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Invalid request!");
    }

    // Form verilerini al
    $title = trim($_POST['title'] ?? '');
    $year = trim($_POST['year'] ?? '');
    $rate = trim($_POST['rate'] ?? 0);
    $overview = trim($_POST['overview'] ?? '');
    $category = trim($_POST['category'] ?? 'Other');
    $trailer = trim($_POST['trailer'] ?? '');
    $created_by = $_SESSION['username'];

    // Dosya yükleme işlemleri
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB

        // Dosya türünü ve boyutunu kontrol et
        $file_type = mime_content_type($_FILES['image']['tmp_name']);
        $file_size = $_FILES['image']['size'];

        if (!in_array($file_type, $allowed_types)) {
            $errors[] = "You can only upload images in JPEG, PNG, or GIF formats.";
        }

        if ($file_size > $max_size) {
            $errors[] = "Image size must not exceed 2MB.";
        }

        if (empty($errors)) {
            // Dosya sistemi yolu (server filesystem path)
            $upload_dir = __DIR__ . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'movies' . DIRECTORY_SEPARATOR;

            // Dizin mevcut değilse oluştur
            if (!is_dir($upload_dir)) {
                if (!mkdir($upload_dir, 0755, true)) {
                    $errors[] = "Failed to create upload directory.";
                }
            }

            if (empty($errors)) {
                // Benzersiz dosya adı oluştur
                $file_ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $unique_name = uniqid('movie_', true) . '.' . $file_ext;
                $destination = '/Applications/XAMPP/xamppfiles/htdocs/256_project/images/movies/' . $unique_name;

                echo $destination;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                    // Web erişilebilir yol (web-accessible path)
                    $image_path = '/images/movies/' . $unique_name;
                } else {
                    $errors[] = "An error occurred while uploading the image.";
                }
            }
        }
    } else {
        $errors[] = "Image upload is required.";
    }

    // Form verilerini doğrulama
    if (empty($title)) {
        $errors[] = "The title field cannot be empty.";
    }

    if (empty($year) || !filter_var($year, FILTER_VALIDATE_INT)) {
        $errors[] = "Please enter a valid release year.";
    }

    if (!is_numeric($rate) || $rate < 0 || $rate > 10) {
        $errors[] = "Rating must be between 0 and 10.";
    }

    if (empty($overview)) {
        $errors[] = "The overview field cannot be empty.";
    }

    if (empty($category)) {
        $category = 'Other';
    }

    if (!empty($trailer) && !filter_var($trailer, FILTER_VALIDATE_URL)) {
        $errors[] = "Please enter a valid trailer URL.";
    }

    // Hatalar yoksa veritabanına kaydet
    if (empty($errors)) {
        try {
            $stmt = $db->prepare("INSERT INTO movies (image, title, year, rate, overview, category, trailer, created_by, approved) 
                                  VALUES (:image, :title, :year, :rate, :overview, :category, :trailer, :created_by, FALSE)");
            $stmt->bindParam(':image', $image_path);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':year', $year, PDO::PARAM_INT);
            $stmt->bindParam(':rate', $rate);
            $stmt->bindParam(':overview', $overview);
            $stmt->bindParam(':category', $category);
            $stmt->bindParam(':trailer', $trailer);
            $stmt->bindParam(':created_by', $created_by);

            if ($stmt->execute()) {
                $success = "Movie added successfully and is pending approval.";
            } else {
                $errors[] = "An error occurred while adding the movie.";
            }
        } catch (PDOException $e) {
            $errors[] = "Error: " . $e->getMessage();
        }
    }
}

// Film silme işlemi
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_movie'])) {
    // CSRF Token Doğrulama
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Invalid request!");
    }

    $movie_id = $_POST['movie_id'] ?? '';

    if (empty($movie_id) || !filter_var($movie_id, FILTER_VALIDATE_INT)) {
        $errors[] = "Invalid movie ID.";
    }

    if (empty($errors)) {
        try {
            // Filmin sahibi mi kontrol et
            $stmt = $db->prepare("SELECT image FROM movies WHERE id = :id AND created_by = :created_by");
            $stmt->bindParam(':id', $movie_id, PDO::PARAM_INT);
            $stmt->bindParam(':created_by', $_SESSION['username']);
            $stmt->execute();
            $movie = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($movie) {
                // Resmi sil
                $image_full_path = __DIR__ . DIRECTORY_SEPARATOR . $movie['image'];
                if (file_exists($image_full_path)) {
                    unlink($image_full_path);
                }

                // Filmi sil
                $stmt = $db->prepare("DELETE FROM movies WHERE id = :id");
                $stmt->bindParam(':id', $movie_id, PDO::PARAM_INT);
                if ($stmt->execute()) {
                    $success = "Movie deleted successfully.";
                } else {
                    $errors[] = "An error occurred while deleting the movie.";
                }
            } else {
                $errors[] = "This movie does not belong to you or does not exist.";
            }
        } catch (PDOException $e) {
            $errors[] = "Error: " . $e->getMessage();
        }
    }
}

// Film düzenleme işlemi
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_movie'])) {
    // CSRF Token Doğrulama
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Invalid request!");
    }

    // Form verilerini al
    $movie_id = $_POST['movie_id'] ?? '';
    $title = trim($_POST['title'] ?? '');
    $year = trim($_POST['year'] ?? '');
    $rate = trim($_POST['rate'] ?? 0);
    $overview = trim($_POST['overview'] ?? '');
    $category = trim($_POST['category'] ?? 'Other');
    $trailer = trim($_POST['trailer'] ?? '');

    // Form verilerini doğrulama
    if (empty($movie_id) || !filter_var($movie_id, FILTER_VALIDATE_INT)) {
        $errors[] = "Invalid movie ID.";
    }

    if (empty($title)) {
        $errors[] = "The title field cannot be empty.";
    }

    if (empty($year) || !filter_var($year, FILTER_VALIDATE_INT)) {
        $errors[] = "Please enter a valid release year.";
    }

    if (!is_numeric($rate) || $rate < 0 || $rate > 10) {
        $errors[] = "Rating must be between 0 and 10.";
    }

    if (empty($overview)) {
        $errors[] = "The overview field cannot be empty.";
    }

    if (empty($category)) {
        $category = 'Other';
    }

    if (!empty($trailer) && !filter_var($trailer, FILTER_VALIDATE_URL)) {
        $errors[] = "Please enter a valid trailer URL.";
    }

    // Yeni resim yükleme (isteğe bağlı)
    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB

        $file_type = mime_content_type($_FILES['image']['tmp_name']);
        $file_size = $_FILES['image']['size'];

        if (!in_array($file_type, $allowed_types)) {
            $errors[] = "You can only upload images in JPEG, PNG, or GIF formats.";
        }

        if ($file_size > $max_size) {
            $errors[] = "Image size must not exceed 2MB.";
        }

        if (empty($errors)) {
            // Dosya sistemi yolu (server filesystem path)
            $upload_dir = __DIR__ . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'movies' . DIRECTORY_SEPARATOR;

            // Dizin mevcut değilse oluştur
            if (!is_dir($upload_dir)) {
                if (!mkdir($upload_dir, 0755, true)) {
                    $errors[] = "Failed to create upload directory.";
                }
            }

            if (empty($errors)) {
                // Benzersiz dosya adı oluştur
                $file_ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $unique_name = uniqid('movie_', true) . '.' . $file_ext;
                $destination = $upload_dir . $unique_name;

                if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                    // Web erişilebilir yol (web-accessible path)
                    $image_path = 'images/movies/' . $unique_name;
                } else {
                    $errors[] = "An error occurred while uploading the image.";
                }
            }
        }
    }

    // Hatalar yoksa veritabanını güncelle
    if (empty($errors)) {
        try {
            // Filmi güncellemeden önce, kullanıcıya ait mi kontrol et
            $stmt = $db->prepare("SELECT image FROM movies WHERE id = :id AND created_by = :created_by");
            $stmt->bindParam(':id', $movie_id, PDO::PARAM_INT);
            $stmt->bindParam(':created_by', $_SESSION['username']);
            $stmt->execute();
            $movie = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($movie) {
                // Yeni resim yüklendiyse, eski resmi sil
                if ($image_path) {
                    $old_image_full_path = __DIR__ . DIRECTORY_SEPARATOR . $movie['image'];
                    if (file_exists($old_image_full_path)) {
                        unlink($old_image_full_path);
                    }
                }

                // Filmi güncelle
                if ($image_path) {
                    $stmt = $db->prepare("UPDATE movies SET title = :title, year = :year, rate = :rate, overview = :overview, category = :category, trailer = :trailer, image = :image WHERE id = :id");
                    $stmt->bindParam(':image', $image_path);
                } else {
                    $stmt = $db->prepare("UPDATE movies SET title = :title, year = :year, rate = :rate, overview = :overview, category = :category, trailer = :trailer WHERE id = :id");
                }

                $stmt->bindParam(':title', $title);
                $stmt->bindParam(':year', $year, PDO::PARAM_INT);
                $stmt->bindParam(':rate', $rate);
                $stmt->bindParam(':overview', $overview);
                $stmt->bindParam(':category', $category);
                $stmt->bindParam(':trailer', $trailer);
                $stmt->bindParam(':id', $movie_id, PDO::PARAM_INT);

                if ($stmt->execute()) {
                    $success = "Movie updated successfully.";
                } else {
                    $errors[] = "An error occurred while updating the movie.";
                }
            } else {
                $errors[] = "This movie does not belong to you or does not exist.";
            }
        } catch (PDOException $e) {
            $errors[] = "Error: " . $e->getMessage();
        }
    }
}

// Mevcut filmleri çek
try {
    // Kullanıcının kendi filmleri
    $stmt = $db->prepare("SELECT * FROM movies WHERE created_by = :created_by ORDER BY created_at DESC");
    $stmt->bindParam(':created_by', $_SESSION['username']);
    $stmt->execute();
    $my_movies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Diğer content creator'ların filmleri (sadece onaylanmış olanlar)
    $stmt = $db->prepare("SELECT m.*, u.username AS creator_username FROM movies m
                          JOIN users u ON m.created_by = u.username
                          WHERE m.created_by != :created_by AND m.approved = TRUE
                          ORDER BY m.created_at DESC");
    $stmt->bindParam(':created_by', $_SESSION['username']);
    $stmt->execute();
    $other_movies = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "An error occurred while fetching movies: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Content Creator Dashboard</title>
    <!-- Bootstrap CSS (CDN) -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-inverse navbar-fixed-top">
        <div class="container-fluid">
            <!-- Navbar Header -->
            <div class="navbar-header">
                <a class="navbar-brand" href="#">MovieDB Creator</a>
            </div>
            <!-- Navbar Links -->
            <ul class="nav navbar-nav">
                <li class="active"><a href="content_dashboard.php">Dashboard</a></li>
                <li><a href="index.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    
    <div class="container" style="margin-top: 70px;">
        <h1>Welcome to Content Creator Dashboard</h1>
        
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <p><?= htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <p><?= htmlspecialchars($success); ?></p>
            </div>
        <?php endif; ?>

     
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title">Create New Movie</h3>
            </div>
            <div class="panel-body">
                <form method="post" action="content_dashboard.php" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <input type="hidden" name="create_movie" value="1">
                    
                    <div class="form-group">
                        <label for="title">Title:</label>
                        <input type="text" name="title" id="title" class="form-control" placeholder="Movie Title" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="year">Release Year:</label>
                        <input type="number" name="year" id="year" class="form-control" placeholder="2025" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="rate">Rating:</label>
                        <input type="number" step="0.1" name="rate" id="rate" class="form-control" placeholder="8.5" min="0" max="10" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="overview">Overview:</label>
                        <textarea name="overview" id="overview" class="form-control" rows="4" placeholder="A brief description of the movie..." required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="category">Category:</label>
                        <input type="text" name="category" id="category" class="form-control" placeholder="Action, Drama, Comedy, etc." value="Other" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="trailer">Trailer URL:</label>
                        <input type="url" name="trailer" id="trailer" class="form-control" placeholder="https://youtube.com/..." >
                    </div>
                    
                    <div class="form-group">
                        <label for="image">Upload Image:</label>
                        <input type="file" name="image" id="image" class="form-control-file" accept="image/*" required>
                        <p class="help-block">Must be in JPEG, PNG, or GIF format and not exceed 2MB.</p>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Add Movie</button>
                </form>
            </div>
        </div>

        
        <div class="panel panel-info">
            <div class="panel-heading">
                <h3 class="panel-title">My Movies</h3>
            </div>
            <div class="panel-body">
                <?php if (!empty($my_movies)): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Title</th>
                                    <th>Release Year</th>
                                    <th>Rating</th>
                                    <th>Category</th>
                                    <th>Trailer</th>
                                    <th>Approval Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($my_movies as $movie): ?>
                                    <tr>
                                        <td><img src="<?= htmlspecialchars($movie['image']); ?>" alt="<?= htmlspecialchars($movie['title']); ?>" width="100"></td>
                                        <td><?= htmlspecialchars($movie['title']); ?></td>
                                        <td><?= htmlspecialchars($movie['year']); ?></td>
                                        <td><?= htmlspecialchars($movie['rate']); ?></td>
                                        <td><?= htmlspecialchars($movie['category']); ?></td>
                                        <td>
                                            <?php if (!empty($movie['trailer'])): ?>
                                                <a href="<?= htmlspecialchars($movie['trailer']); ?>" target="_blank">Trailer</a>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($movie['approved']): ?>
                                                <span class="label label-success">Approved</span>
                                            <?php else: ?>
                                                <span class="label label-warning">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <!-- Edit Button -->
                                            <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#editMyMovieModal<?= $movie['id']; ?>">Edit</button>

                                            <!-- Delete Button -->
                                            <form method="post" action="content_dashboard.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this movie?');">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                <input type="hidden" name="delete_movie" value="1">
                                                <input type="hidden" name="movie_id" value="<?= htmlspecialchars($movie['id']); ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                            </form>
                                        </td>
                                    </tr>

                                    <!-- Edit My Movie Modal -->
                                    <div id="editMyMovieModal<?= $movie['id']; ?>" class="modal fade" role="dialog">
                                        <div class="modal-dialog">
                                            
                                            <div class="modal-content">
                                                <form method="post" action="content_dashboard.php" enctype="multipart/form-data">
                                                    <div class="modal-header">
                                                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                        <h4 class="modal-title">Edit Movie</h4>
                                                    </div>
                                                    <div class="modal-body">
                                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                        <input type="hidden" name="edit_movie" value="1">
                                                        <input type="hidden" name="movie_id" value="<?= htmlspecialchars($movie['id']); ?>">

                                                        <div class="form-group">
                                                            <label for="title<?= $movie['id']; ?>">Title:</label>
                                                            <input type="text" name="title" id="title<?= $movie['id']; ?>" class="form-control" value="<?= htmlspecialchars($movie['title']); ?>" required>
                                                        </div>

                                                        <div class="form-group">
                                                            <label for="year<?= $movie['id']; ?>">Release Year:</label>
                                                            <input type="number" name="year" id="year<?= $movie['id']; ?>" class="form-control" value="<?= htmlspecialchars($movie['year']); ?>" required>
                                                        </div>

                                                        <div class="form-group">
                                                            <label for="rate<?= $movie['id']; ?>">Rating:</label>
                                                            <input type="number" step="0.1" name="rate" id="rate<?= $movie['id']; ?>" class="form-control" value="<?= htmlspecialchars($movie['rate']); ?>" min="0" max="10" required>
                                                        </div>

                                                        <div class="form-group">
                                                            <label for="overview<?= $movie['id']; ?>">Overview:</label>
                                                            <textarea name="overview" id="overview<?= $movie['id']; ?>" class="form-control" rows="4" required><?= htmlspecialchars($movie['overview']); ?></textarea>
                                                        </div>

                                                        <div class="form-group">
                                                            <label for="category<?= $movie['id']; ?>">Category:</label>
                                                            <input type="text" name="category" id="category<?= $movie['id']; ?>" class="form-control" value="<?= htmlspecialchars($movie['category']); ?>" required>
                                                        </div>

                                                        <div class="form-group">
                                                            <label for="trailer<?= $movie['id']; ?>">Trailer URL:</label>
                                                            <input type="url" name="trailer" id="trailer<?= $movie['id']; ?>" class="form-control" value="<?= htmlspecialchars($movie['trailer']); ?>">
                                                        </div>

                                                        <div class="form-group">
                                                            <label for="image<?= $movie['id']; ?>">Upload New Image (optional):</label>
                                                            <input type="file" name="image" id="image<?= $movie['id']; ?>" class="form-control-file" accept="image/*">
                                                            <p class="help-block">Must be in JPEG, PNG, or GIF format and not exceed 2MB.</p>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="submit" class="btn btn-success">Save Changes</button>
                                                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>You have no movies.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Diğer Content Creator'ların Filmleri -->
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">Other Content Creators' Movies</h3>
            </div>
            <div class="panel-body">
                <?php if (!empty($other_movies)): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Title</th>
                                    <th>Release Year</th>
                                    <th>Rating</th>
                                    <th>Category</th>
                                    <th>Trailer</th>
                                    <th>Creator</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($other_movies as $movie): ?>
                                    <tr>
                                        <td><img src="<?= htmlspecialchars($movie['image']); ?>" alt="<?= htmlspecialchars($movie['title']); ?>" width="100"></td>
                                        <td><?= htmlspecialchars($movie['title']); ?></td>
                                        <td><?= htmlspecialchars($movie['year']); ?></td>
                                        <td><?= htmlspecialchars($movie['rate']); ?></td>
                                        <td><?= htmlspecialchars($movie['category']); ?></td>
                                        <td>
                                            <?php if (!empty($movie['trailer'])): ?>
                                                <a href="<?= htmlspecialchars($movie['trailer']); ?>" target="_blank">Trailer</a>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($movie['creator_username']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>There are no movies from other content creators.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- jQuery (CDN) -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

    <!-- Bootstrap JS (CDN) -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>

    <!-- Custom JS -->
    <script src="js/main.js"></script>
</body>
</html>