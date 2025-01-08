<?php
include_once "db.php";

session_start();


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}


if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


$errors = [];
$success = "";


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_user'])) {
    
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Invalid request!");
    }

    
    $username = trim($_POST['username'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $repassword = trim($_POST['repassword'] ?? '');
    $role = $_POST['role'] ?? 'user'; 

    
    if (!preg_match("/^[a-zA-Z][a-zA-Z0-9-_\.]{8,20}$/", $username)) {
        $errors[] = "Username must be between 8-20 characters and can only contain letters, numbers, hyphens, and underscores.";
    }

    // First and last name validation
    if (empty($first_name) || empty($last_name)) {
        $errors[] = "First name and last name fields cannot be empty.";
    }

    // Email validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    // Role validation
    $allowed_roles = ['user', 'content_creator'];
    if (!in_array($role, $allowed_roles)) {
        $errors[] = "Invalid role selected.";
    }

    // Password validation
    if (!preg_match("/(?=^.{8,}$)((?=.*\d)|(?=.*\W+))(?![.\n])(?=.*[A-Z])(?=.*[a-z]).*$/", $password)) {
        $errors[] = "Password must be at least 8 characters long and include uppercase letters, lowercase letters, and numbers.";
    }

    // Password match check
    if ($password !== $repassword) {
        $errors[] = "Passwords do not match.";
    }

    // If no errors, add the user
    if (empty($errors)) {
        // Hash the password for security
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        try {
            
            $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = :username");
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            if ($stmt->fetchColumn() > 0) {
                $errors[] = "This username is already taken.";
            } else {
                
                $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
                $stmt->bindParam(':email', $email);
                $stmt->execute();
                if ($stmt->fetchColumn() > 0) {
                    $errors[] = "This email address is already in use.";
                } else {
                    
                    $stmt = $db->prepare("INSERT INTO users (username, first_name, last_name, email, password, role, avatar) 
                                          VALUES (:username, :first_name, :last_name, :email, :password, :role, :avatar)");
                    $stmt->bindParam(':username', $username);
                    $stmt->bindParam(':first_name', $first_name);
                    $stmt->bindParam(':last_name', $last_name);
                    $stmt->bindParam(':email', $email);
                    $stmt->bindParam(':password', $hashedPassword);
                    $stmt->bindParam(':role', $role);
                    $stmt->bindValue(':avatar', "avatars/logo.png"); 

                    if ($stmt->execute()) {
                        $success = "User created successfully.";
                    } else {
                        $errors[] = "An error occurred, please try again.";
                    }
                }
            }
        } catch (PDOException $e) {
            $errors[] = "Error: " . $e->getMessage();
        }
    }
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_movie'])) {
    
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Invalid request!");
    }

    $movie_id = $_POST['movie_id'] ?? '';

    if (empty($movie_id) || !filter_var($movie_id, FILTER_VALIDATE_INT)) {
        $errors[] = "Invalid movie ID.";
    }

    if (empty($errors)) {
        try {
            
            $stmt = $db->prepare("SELECT image FROM movies WHERE id = :id");
            $stmt->bindParam(':id', $movie_id, PDO::PARAM_INT);
            $stmt->execute();
            $movie = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($movie) {
                // Delete the image file
                if (file_exists($movie['image'])) {
                    unlink($movie['image']);
                }

                // Delete the movie from the database
                $stmt = $db->prepare("DELETE FROM movies WHERE id = :id");
                $stmt->bindParam(':id', $movie_id, PDO::PARAM_INT);
                if ($stmt->execute()) {
                    $success = "Movie deleted successfully.";
                } else {
                    $errors[] = "An error occurred while deleting the movie.";
                }
            } else {
                $errors[] = "Movie does not exist.";
            }
        } catch (PDOException $e) {
            $errors[] = "Error: " . $e->getMessage();
        }
    }
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['approve_movie'])) {
    
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Invalid request!");
    }

    $movie_id = $_POST['movie_id'] ?? '';

    if (empty($movie_id) || !filter_var($movie_id, FILTER_VALIDATE_INT)) {
        $errors[] = "Invalid movie ID.";
    }

    if (empty($errors)) {
        try {
            
            $stmt = $db->prepare("UPDATE movies SET approved = TRUE WHERE id = :id");
            $stmt->bindParam(':id', $movie_id, PDO::PARAM_INT);
            if ($stmt->execute()) {
                $success = "Movie approved successfully.";
            } else {
                $errors[] = "An error occurred while approving the movie.";
            }
        } catch (PDOException $e) {
            $errors[] = "Error: " . $e->getMessage();
        }
    }
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_movie'])) {
    
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Invalid request!");
    }

    
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
        $max_size = 2 * 1024 * 1024; 

        $file_type = mime_content_type($_FILES['image']['tmp_name']);
        $file_size = $_FILES['image']['size'];

        if (!in_array($file_type, $allowed_types)) {
            $errors[] = "You can only upload images in JPEG, PNG, or GIF formats.";
        }

        if ($file_size > $max_size) {
            $errors[] = "Image size must not exceed 2MB.";
        }

        if (empty($errors)) {
            $upload_dir = 'images/movies/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            // Benzersiz dosya adı oluştur
            $file_ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $unique_name = uniqid('movie_', true) . '.' . $file_ext;
            $destination = $upload_dir . $unique_name;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                $image_path = $destination;
            } else {
                $errors[] = "An error occurred while uploading the image.";
            }
        }
    }

    // Hatalar yoksa veritabanını güncelle
    if (empty($errors)) {
        try {
            // Filmi güncellemeden önce, mevcut resim yolunu al
            $stmt = $db->prepare("SELECT image FROM movies WHERE id = :id");
            $stmt->bindParam(':id', $movie_id, PDO::PARAM_INT);
            $stmt->execute();
            $movie = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($movie) {
                // Yeni resim yüklendiyse, eski resmi sil
                if ($image_path && file_exists($movie['image'])) {
                    unlink($movie['image']);
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
                $errors[] = "Movie does not exist.";
            }
        } catch (PDOException $e) {
            $errors[] = "Error: " . $e->getMessage();
        }
    }
}


try {
    $stmt = $db->prepare("SELECT username, first_name, last_name, email, role FROM users");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "An error occurred while fetching users: " . $e->getMessage();
}


try {
    $stmt = $db->prepare("SELECT m.*, u.username AS creator_username FROM movies m JOIN users u ON m.created_by = u.username ORDER BY m.created_at DESC");
    $stmt->execute();
    $all_movies = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "An error occurred while fetching movies: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-inverse">
        <div class="container-fluid">
            <!-- Navbar Header -->
            <div class="navbar-header">
                <a class="navbar-brand" href="#">MovieDB Admin</a>
            </div>
            <!-- Navbar Links -->
            <ul class="nav navbar-nav">
                <li class="active"><a href="admin_dashboard.php">Dashboard</a></li>
                <li><a href="index.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <!-- Ana İçerik -->
    <div class="container" style="margin-top: 70px;">
        <h1>Welcome to Admin Dashboard</h1>

        <!-- Hata ve Başarı Mesajları -->
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

        <!-- Yeni Kullanıcı Oluşturma Formu -->
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title">Create New User</h3>
            </div>
            <div class="panel-body">
                <form method="post" action="admin_dashboard.php">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <input type="hidden" name="create_user" value="1">

                    <div class="form-group">
                        <label for="username">Username:</label>
                        <input type="text" name="username" id="username" class="form-control" placeholder="HughJackman" pattern="^[a-zA-Z][a-zA-Z0-9-_\.]{8,20}$" required>
                        <small class="text-muted">8-20 characters, letters, numbers, hyphens, and underscores.</small>
                    </div>

                    <div class="form-group">
                        <label for="first_name">First Name:</label>
                        <input type="text" name="first_name" id="first_name" class="form-control" placeholder="Hugh" required>
                    </div>

                    <div class="form-group">
                        <label for="last_name">Last Name:</label>
                        <input type="text" name="last_name" id="last_name" class="form-control" placeholder="Jackman" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Your Email:</label>
                        <input type="email" name="email" id="email" class="form-control" placeholder="example@mail.com" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password:</label>
                        <input type="password" name="password" id="password" class="form-control" placeholder="******" pattern="(?=^.{8,}$)((?=.*\d)|(?=.*\W+))(?![.\n])(?=.*[A-Z])(?=.*[a-z]).*$" required>
                        <small class="text-muted">At least 8 characters, must include uppercase, lowercase letters, and numbers.</small>
                    </div>

                    <div class="form-group">
                        <label for="repassword">Re-type Password:</label>
                        <input type="password" name="repassword" id="repassword" class="form-control" placeholder="******" required>
                    </div>

                    <div class="form-group">
                        <label for="role">Role:</label>
                        <select name="role" id="role" class="form-control" required>
                            <option value="user">User</option>
                            <option value="content_creator">Content Creator</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary">Create User</button>
                </form>
            </div>
        </div>

        <div class="panel panel-info">
            <div class="panel-heading">
                <h3 class="panel-title">Existing Users</h3>
            </div>
            <div class="panel-body">
                <?php if (!empty($users)): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>First Name</th>
                                    <th>Last Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($user['username']); ?></td>
                                        <td><?= htmlspecialchars($user['first_name']); ?></td>
                                        <td><?= htmlspecialchars($user['last_name']); ?></td>
                                        <td><?= htmlspecialchars($user['email']); ?></td>
                                        <td><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $user['role']))); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>No users found.</p>
                <?php endif; ?>
            </div>
        </div>

        
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">Manage Movies</h3>
            </div>
            <div class="panel-body">
                <?php if (!empty($all_movies)): ?>
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
                                    <th>Approval Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($all_movies as $movie): ?>
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
                                        <td>
                                            <?php if ($movie['approved']): ?>
                                                <span class="label label-success">Approved</span>
                                            <?php else: ?>
                                                <span class="label label-warning">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!$movie['approved']): ?>
                                                <!-- Approve Button -->
                                                <form method="post" action="admin_dashboard.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to approve this movie?');">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                    <input type="hidden" name="approve_movie" value="1">
                                                    <input type="hidden" name="movie_id" value="<?= htmlspecialchars($movie['id']); ?>">
                                                    <button type="submit" class="btn btn-success btn-sm">Approve</button>
                                                </form>
                                            <?php endif; ?>

                                            <!-- Edit Button -->
                                            <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#editMovieModal<?= $movie['id']; ?>">Edit</button>

                                            <!-- Delete Button -->
                                            <form method="post" action="admin_dashboard.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this movie?');">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                <input type="hidden" name="delete_movie" value="1">
                                                <input type="hidden" name="movie_id" value="<?= htmlspecialchars($movie['id']); ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                            </form>
                                        </td>
                                    </tr>

                                    
                                    <div id="editMovieModal<?= $movie['id']; ?>" class="modal fade" role="dialog">
                                        <div class="modal-dialog">
                                            
                                            <div class="modal-content">
                                                <form method="post" action="admin_dashboard.php" enctype="multipart/form-data">
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
                    <p>No movies found.</p>
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
