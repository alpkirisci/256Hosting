

ALTER TABLE users
  ADD COLUMN role ENUM('admin','content_creator')
  DEFAULT 'content_creator';

-- ============================
-- 2) movies tablosuna "created_by" ve "approved" ekle
-- ============================
ALTER TABLE movies
  ADD COLUMN created_by VARCHAR(150),
  ADD COLUMN approved BOOLEAN DEFAULT FALSE;

-- (isteğe bağlı) Dış anahtar (created_by -> users.username)
ALTER TABLE movies
  ADD CONSTRAINT fk_movies_created_by
  FOREIGN KEY (created_by) REFERENCES users(username)
  ON DELETE SET NULL; 
-- [YENI] "ON DELETE SET NULL" diyorum ki eğer user silinirse "created_by" NULL olsun (isteğe bağlı)

-- bu kısmı default content creator vermesin diye onradan ekledim. --
ALTER TABLE users 
   MODIFY COLUMN role ENUM('admin','content_creator','user') DEFAULT 'user';

-- Admin Kullanıcısı Ekleme
INSERT INTO users (username, first_name, last_name, email, password, role, avatar) 
VALUES (
    'AdminUser', 
    'Admin', 
    'User', 
    'adminuser@example.com', 
    '$2y$10$e0NRfU1G5f3QwzL9y8yQ..Z1e7k8E2rR6H3F6JjkG8hKZb3ZlXaiG', -- 'Admin+123' için hash
    'admin',
    'avatars/logo.png'
);

-- Content Creator Kullanıcısı Ekleme
INSERT INTO users (username, first_name, last_name, email, password, role, avatar) 
VALUES (
    'CreatorUser', 
    'Creator', 
    'User', 
    'creatoruser@example.com', 
    '$2y$10$2dJ0qYk3J9L5sH8U5P1dCeYb2E6F4G7H5Jk3L2M1N0O9P8Q7R6S6i', -- 'Creator+123' için hash
    'content_creator',
    'avatars/logo.png'
);

--
ALTER TABLE users
ADD COLUMN is_verified BOOLEAN DEFAULT FALSE,
ADD COLUMN verification_code VARCHAR(255) NULL;

/*

CREATE TABLE movies (
    id INT AUTO_INCREMENT PRIMARY KEY, -- Otomatik artan birincil anahtar
    image VARCHAR(255) NOT NULL,       -- Resim dosyasının yolu
    title VARCHAR(100) NOT NULL,       -- Film başlığı
    year INT NOT NULL,                 -- Yayın yılı
    rate FLOAT DEFAULT 0,              -- Puan
    overview TEXT,                     -- Genel açıklama
    category TEXT DEFAULT 'Other',     -- Kategori
    trailer VARCHAR(2083),             -- Fragman URL'si (URL'ler genelde 2083 karakteri geçmez)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Ekstra: Oluşturulma tarihi
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP -- Ekstra: Güncellenme tarihi
);

CREATE TABLE users (
    username VARCHAR(150) NOT NULL PRIMARY KEY,    -- Kullanıcı adı birincil anahtar
    first_name VARCHAR(30) NOT NULL,                -- Kullanıcının adı
    last_name VARCHAR(30) NOT NULL,                 -- Kullanıcının soyadı
    email VARCHAR(254) NOT NULL UNIQUE,             -- E-posta adresi
    password VARCHAR(128) NOT NULL,                 -- Şifre
    avatar VARCHAR(255) DEFAULT 'avatars/logo.png', -- Avatar alanı (default logo)
    date_joined TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Kullanıcının kayıt tarihi
    last_login TIMESTAMP NULL,                      -- Son giriş zamanı
    is_active BOOLEAN DEFAULT TRUE,                 -- Hesap aktif mi?
    is_staff BOOLEAN DEFAULT FALSE,                 -- Yönetici mi?
    is_superuser BOOLEAN DEFAULT FALSE              -- Süper kullanıcı mı?
);


CREATE TABLE user_favorites (
    user VARCHAR(150) ,                     -- Kullanıcı adı
    movie_id INT ,                 -- Film ID'si
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Favoriye eklenme zamanı
    PRIMARY KEY (user, movie_id),                   -- Kullanıcı ve film ikilisi, birleşik birincil anahtar
    FOREIGN KEY (user) REFERENCES users(username) ON DELETE CASCADE,  -- Kullanıcı adı üzerinden dış anahtar
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE   -- Film ID'si üzerinden dış anahtar
);

CREATE TABLE movie_review_ratings (
    user VARCHAR(150) ,                  -- Kullanıcı adı, kullanıcı adı üzerinden ilişki kurulacak
    movie_id INT ,              -- Film ID'si
    subject VARCHAR(100),                        -- İnceleme konusu
    review TEXT,                                 -- İnceleme metni
    rating FLOAT DEFAULT 0,                      -- Puanlama
    ip VARCHAR(120),                             -- Kullanıcının IP adresi
    status BOOLEAN DEFAULT TRUE,                 -- İncelemenin aktif/pasif durumu
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- İncelemenin oluşturulma zamanı
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- Güncellenme zamanı
    FOREIGN KEY (user) REFERENCES users(username) ON DELETE CASCADE, -- Kullanıcı adı üzerinden dış anahtar
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE  -- Film ID'si üzerinden dış anahtar
);

*/
