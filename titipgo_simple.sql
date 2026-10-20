-- =========================================================
-- TITIPGO SIMPLE DATABASE - CLEAN VERSION
-- =========================================================

CREATE DATABASE IF NOT EXISTS titipgo_simple
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE titipgo_simple;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS traveler_subscriptions;
DROP TABLE IF EXISTS withdrawal_requests;
DROP TABLE IF EXISTS local_broadcasts;
DROP TABLE IF EXISTS sop_audit_logs;
DROP TABLE IF EXISTS subscriptions;
DROP TABLE IF EXISTS traveler_kyc;
DROP TABLE IF EXISTS traveler_reviews;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS qc_messages;
DROP TABLE IF EXISTS escrow_transactions;
DROP TABLE IF EXISTS manual_requests;
DROP TABLE IF EXISTS request_manuals;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS trips;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS country_cities;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- =========================================================
-- USERS
-- =========================================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('buyer','traveler') NOT NULL DEFAULT 'buyer',
    phone VARCHAR(30) NOT NULL DEFAULT '080000000000',
    birth_date VARCHAR(100) NOT NULL DEFAULT '01 Januari 2000',
    gender VARCHAR(50) NOT NULL DEFAULT 'Belum Diisi',
    bio TEXT NOT NULL,
    address TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- COUNTRY & CITY MASTER
-- =========================================================
CREATE TABLE country_cities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    country VARCHAR(80) NOT NULL,
    city VARCHAR(80) NOT NULL,
    emoji VARCHAR(10) NOT NULL DEFAULT '📍',
    active_travelers INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- PRODUCTS / DISCOVERY PRODUK
-- Produk dapat berasal dari database utama maupun broadcast Local Finder.
-- =========================================================
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    traveler_id INT NOT NULL DEFAULT 2,
    name VARCHAR(150) NOT NULL,
    country VARCHAR(80) NOT NULL,
    city VARCHAR(80) NOT NULL,
    price DECIMAL(12,2) NOT NULL DEFAULT 0,
    commission DECIMAL(12,2) NOT NULL DEFAULT 25000,
    eta VARCHAR(100) NOT NULL DEFAULT '3-5 hari',
    category VARCHAR(100) NOT NULL DEFAULT 'Oleh-oleh',
    description TEXT NOT NULL,
    image VARCHAR(255) NOT NULL DEFAULT 'assets/default-product.png',
    image_path VARCHAR(255) NOT NULL DEFAULT 'assets/default-product.png',
    status VARCHAR(50) NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (traveler_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- TRIPS / MANAJEMEN RUTE
-- =========================================================
CREATE TABLE trips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    traveler_id INT NOT NULL,
    origin_country VARCHAR(80) NOT NULL,
    destination_city VARCHAR(80) NOT NULL,
    trip_date DATE NOT NULL,
    return_date DATE NOT NULL,
    capacity_note VARCHAR(150) NOT NULL DEFAULT 'Maksimal 10 kg atau 15 item',
    max_weight DECIMAL(8,2) NOT NULL DEFAULT 10.00,
    max_items INT NOT NULL DEFAULT 15,
    categories VARCHAR(255) NOT NULL DEFAULT 'Fashion, Elektronik, Makanan, Kosmetik',
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (traveler_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- ORDERS
-- =========================================================
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    buyer_id INT NOT NULL,
    traveler_id INT NOT NULL,
    product_id INT NOT NULL DEFAULT 1,
    product_name VARCHAR(150) NOT NULL,
    product_country VARCHAR(80) NOT NULL,
    product_city VARCHAR(80) NOT NULL DEFAULT 'Tokyo',
    product_price DECIMAL(12,2) NOT NULL DEFAULT 0,
    service_fee DECIMAL(12,2) NOT NULL DEFAULT 25000,
    admin_fee DECIMAL(12,2) NOT NULL DEFAULT 5000,
    courier_fee DECIMAL(12,2) NOT NULL DEFAULT 0,
    total_price DECIMAL(12,2) NOT NULL DEFAULT 0,
    delivery_option ENUM('standard','private_courier','hand_carry','domestic_courier') NOT NULL DEFAULT 'standard',
    status ENUM(
        'request_created',
        'searching_traveler',
        'traveler_found',
        'waiting_payment',
        'escrow_paid',
        'item_purchased',
        'item_verified',
        'qc_pending',
        'qc_in_progress',
        'qc_approved',
        'qc_rejected',
        'on_delivery',
        'completed',
        'cancelled',
        'refunded'
    ) NOT NULL DEFAULT 'request_created',
    note TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (traveler_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- REQUEST MANUAL
-- Table utama yang dipakai fitur request manual.
-- =========================================================
CREATE TABLE manual_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    buyer_id INT NOT NULL,
    traveler_id INT NOT NULL DEFAULT 2,
    product_name VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    reference_image VARCHAR(255) NOT NULL DEFAULT 'uploads/request/default-reference.png',
    country VARCHAR(100) NOT NULL,
    city VARCHAR(100) NOT NULL,
    handling_type ENUM('standard','fragile') NOT NULL DEFAULT 'fragile',
    estimated_price DECIMAL(12,2) NOT NULL DEFAULT 0,
    traveler_fee DECIMAL(12,2) NOT NULL DEFAULT 25000,
    total_price DECIMAL(12,2) NOT NULL DEFAULT 0,
    status VARCHAR(50) NOT NULL DEFAULT 'searching_traveler',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (traveler_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table kompatibilitas jika file lama masih memakai nama request_manuals.
CREATE TABLE request_manuals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    buyer_id INT NOT NULL,
    traveler_id INT NOT NULL DEFAULT 2,
    product_name VARCHAR(255) NOT NULL DEFAULT 'Produk Request Manual',
    description TEXT NOT NULL,
    reference_image VARCHAR(255) NOT NULL DEFAULT 'uploads/request/default-reference.png',
    country VARCHAR(100) NOT NULL DEFAULT 'Japan',
    city VARCHAR(100) NOT NULL DEFAULT 'Tokyo',
    handling_type ENUM('standard','fragile') NOT NULL DEFAULT 'fragile',
    item_price DECIMAL(12,2) NOT NULL DEFAULT 450000,
    traveler_fee DECIMAL(12,2) NOT NULL DEFAULT 25000,
    total_price DECIMAL(12,2) NOT NULL DEFAULT 475000,
    status VARCHAR(50) NOT NULL DEFAULT 'waiting_traveler',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (traveler_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- ESCROW
-- =========================================================
CREATE TABLE escrow_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    buyer_id INT NOT NULL,
    traveler_id INT NOT NULL,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    status ENUM('pending','locked','released','refunded') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (traveler_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- QC CHAT BUYER & TRAVELER
-- =========================================================
CREATE TABLE qc_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    sender_id INT NOT NULL,
    sender_role ENUM('buyer','traveler') NOT NULL,
    message TEXT NOT NULL,
    photo_path VARCHAR(255) NOT NULL DEFAULT 'uploads/qc/default-qc.png',
    photo VARCHAR(255) NOT NULL DEFAULT 'uploads/qc/default-qc.png',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- REVIEW & RATING
-- =========================================================
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    buyer_id INT NOT NULL,
    traveler_id INT NOT NULL,
    store_rating INT NOT NULL DEFAULT 5,
    traveler_rating INT NOT NULL DEFAULT 5,
    courier_rating INT NOT NULL DEFAULT 5,
    review_store TEXT NOT NULL,
    review_traveler TEXT NOT NULL,
    review_courier TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (traveler_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE traveler_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    traveler_id INT NOT NULL,
    buyer_id INT NOT NULL,
    rating INT NOT NULL DEFAULT 5,
    review TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (traveler_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- BUYER JASTIP PLUS PREMIUM
-- =========================================================
CREATE TABLE subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    package_type ENUM('monthly','yearly') NOT NULL DEFAULT 'monthly',
    package_name VARCHAR(50) NOT NULL DEFAULT 'Bulanan',
    amount DECIMAL(12,2) NOT NULL DEFAULT 49000,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('active','expired') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- TRAVELER KYC
-- =========================================================
CREATE TABLE traveler_kyc (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    ktp_number VARCHAR(50) NOT NULL,
    passport_number VARCHAR(50) NOT NULL DEFAULT 'PASPOR-DEMO-000',
    ktp_image VARCHAR(255) NOT NULL DEFAULT 'uploads/kyc/default-ktp.png',
    passport_image VARCHAR(255) NOT NULL DEFAULT 'uploads/kyc/default-passport.png',
    selfie_image VARCHAR(255) NOT NULL DEFAULT 'uploads/kyc/default-selfie.png',
    bank_name VARCHAR(100) NOT NULL,
    account_number VARCHAR(100) NOT NULL,
    account_holder VARCHAR(150) NOT NULL,
    deposit_amount DECIMAL(12,2) NOT NULL DEFAULT 200000,
    verification_status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    badge_status VARCHAR(50) NOT NULL DEFAULT 'Under Review',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- SOP AUDIT TRAVELER
-- =========================================================
CREATE TABLE sop_audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    traveler_id INT NOT NULL,
    step_code VARCHAR(50) NOT NULL,
    step_title VARCHAR(150) NOT NULL,
    note TEXT NOT NULL,
    photo_path VARCHAR(255) NOT NULL DEFAULT 'uploads/sop/default-sop.png',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (traveler_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- LOCAL FINDER / BROADCAST PRODUK
-- =========================================================
CREATE TABLE local_broadcasts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    traveler_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    country VARCHAR(100) NOT NULL,
    city VARCHAR(100) NOT NULL,
    price DECIMAL(12,2) NOT NULL,
    commission DECIMAL(12,2) NOT NULL DEFAULT 0,
    eta VARCHAR(100) NOT NULL DEFAULT '3-5 hari',
    label_tag VARCHAR(100) NOT NULL DEFAULT 'Langka',
    image_path VARCHAR(255) NOT NULL DEFAULT 'uploads/products/default-product.png',
    status VARCHAR(50) NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (traveler_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- DOMPET ESCROW / WITHDRAWAL
-- =========================================================
CREATE TABLE withdrawal_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    traveler_id INT NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    bank_name VARCHAR(100) NOT NULL,
    account_number VARCHAR(100) NOT NULL,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (traveler_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- TRAVELER PLUS PREMIUM
-- =========================================================
CREATE TABLE traveler_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    traveler_id INT NOT NULL,
    package_name VARCHAR(100) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('active','expired','cancelled') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (traveler_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS traveler_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    traveler_id INT NOT NULL,
    package_name VARCHAR(100) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('active', 'expired', 'cancelled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =========================================================
-- DUMMY DATA USERS
-- =========================================================
INSERT INTO users
(id, name, email, password, role, phone, birth_date, gender, bio, address)
VALUES
(1, 'Buyer Demo', 'buyer@titipgo.com', 'Buyer123', 'buyer', '0811111111', '12 Januari 2002', 'Perempuan', 'Suka menitip produk UMKM kuliner khas daerah dan koleksi terbatas.', 'Jl. Bougenville No. 45, Cileungsi, Bogor, Jawa Barat'),
(2, 'Traveler Demo', 'traveler@titipgo.com', 'Traveler123', 'traveler', '0822222222', '20 Maret 1998', 'Laki-laki', 'Traveler aktif rute Jepang dan Indonesia dengan spesialisasi makanan serta barang rapuh.', 'Jl. Melati No. 10, Jakarta Selatan'),
(3, 'Budi Santoso', 'budi@titipgo.com', 'Budi123', 'traveler', '0833333333', '05 Mei 1997', 'Laki-laki', 'Jastiper cepat untuk rute Jepang dan Jakarta.', 'Jl. Anggrek No. 21, Jakarta'),
(4, 'Sinta Lestari', 'sinta@titipgo.com', 'Sinta123', 'traveler', '0844444444', '18 Agustus 1999', 'Perempuan', 'Traveler rute domestik dan Singapura.', 'Jl. Kenanga No. 8, Tangerang'),
(5, 'Bayu Pratama', 'bayu@titipgo.com', 'Bayu123', 'traveler', '0855555555', '27 November 1996', 'Laki-laki', 'Traveler rute Singapura, Korea, dan Jepang.', 'Jl. Mawar No. 12, Bandung'),
(6, 'Farhan Hadil', 'farhanhadil03@gmail.com', 'Ffff123', 'buyer', '0800000006', '10 Oktober 2004', 'Laki-laki', 'Buyer yang sering mencari oleh-oleh dan produk internasional.', 'Jl. Kampus No. 7, Jakarta Barat'),
(7, 'Fardil', 'fardil@gmail.com', 'Bbbb123', 'traveler', '0800000007', '11 November 2003', 'Laki-laki', 'Traveler baru yang siap menerima request buyer.', 'Jl. Traveler Baru No. 3, Depok');

-- =========================================================
-- DUMMY DATA COUNTRY & CITIES
-- =========================================================
INSERT INTO country_cities (id, country, city, emoji, active_travelers) VALUES
(1, 'Japan', 'Tokyo', '🗼', 42),
(2, 'Japan', 'Osaka', '🏯', 18),
(3, 'Japan', 'Kyoto', '⛩️', 7),
(4, 'Japan', 'Hokkaido', '❄️', 6),
(5, 'Indonesia', 'Jakarta', '🏙️', 35),
(6, 'Indonesia', 'Yogyakarta', '🏛️', 24),
(7, 'Indonesia', 'Bali', '🌴', 29),
(8, 'Indonesia', 'Bandung', '⛰️', 17),
(9, 'Singapore', 'Orchard', '🛍️', 14),
(10, 'Singapore', 'Bugis', '🏬', 9),
(11, 'Singapore', 'Marina Bay', '🌉', 11),
(12, 'Thailand', 'Bangkok', '🏙️', 22),
(13, 'Thailand', 'Phuket', '🏖️', 10),
(14, 'Thailand', 'Chiang Mai', '⛰️', 8),
(15, 'South Korea', 'Seoul', '🏙️', 20),
(16, 'South Korea', 'Busan', '🌊', 8),
(17, 'South Korea', 'Jeju', '🏝️', 6);

-- =========================================================
-- DUMMY DATA PRODUCTS
-- =========================================================
INSERT INTO products
(id, traveler_id, name, country, city, price, commission, eta, category, description, image, image_path, status)
VALUES
(1, 2, 'Tokyo Banana', 'Japan', 'Tokyo', 120000, 25000, '3 hari', 'Makanan', 'Oleh-oleh populer dari Tokyo.', 'uploads/products/tokyo-banana.png', 'uploads/products/tokyo-banana.png', 'active'),
(2, 2, 'KitKat Matcha Tokyo', 'Japan', 'Tokyo', 95000, 20000, '3 hari', 'Makanan', 'Camilan matcha edisi Jepang.', 'uploads/products/kitkat-matcha.png', 'uploads/products/kitkat-matcha.png', 'active'),
(3, 3, 'Ramen Cup Ichiran', 'Japan', 'Osaka', 45000, 25000, '2 hari', 'Makanan', 'Ramen instan khas Jepang dari Osaka.', 'uploads/products/ramen-cup.png', 'uploads/products/ramen-cup.png', 'active'),
(4, 3, 'Takoyaki Mix', 'Japan', 'Osaka', 55000, 20000, '2 hari', 'Makanan', 'Bahan camilan khas Osaka.', 'uploads/products/takoyaki.png', 'uploads/products/takoyaki.png', 'active'),
(5, 2, 'Yatsuhashi Kyoto', 'Japan', 'Kyoto', 70000, 25000, '4 hari', 'Makanan', 'Kue tradisional khas Kyoto.', 'uploads/products/yatsuhashi.png', 'uploads/products/yatsuhashi.png', 'active'),
(6, 2, 'Matcha Premium', 'Japan', 'Kyoto', 60000, 25000, '4 hari', 'Makanan', 'Bubuk matcha khas Kyoto.', 'uploads/products/matcha.png', 'uploads/products/matcha.png', 'active'),
(7, 5, 'Royce Chocolate', 'Japan', 'Hokkaido', 180000, 35000, '5 hari', 'Makanan', 'Cokelat premium dari Hokkaido.', 'uploads/products/royce.png', 'uploads/products/royce.png', 'active'),
(8, 5, 'Hokkaido Milk Candy', 'Japan', 'Hokkaido', 65000, 20000, '5 hari', 'Makanan', 'Permen susu khas Hokkaido.', 'uploads/products/milk-candy.png', 'uploads/products/milk-candy.png', 'active'),

(9, 4, 'Keripik Tempe', 'Indonesia', 'Jakarta', 35000, 15000, '1 hari', 'Makanan', 'Camilan lokal Indonesia.', 'uploads/products/keripik-tempe.png', 'uploads/products/keripik-tempe.png', 'active'),
(10, 4, 'Kopi Nusantara', 'Indonesia', 'Jakarta', 85000, 20000, '1 hari', 'Makanan', 'Kopi lokal pilihan.', 'uploads/products/kopi-nusantara.png', 'uploads/products/kopi-nusantara.png', 'active'),
(11, 2, 'Bakpia Pathok 25', 'Indonesia', 'Yogyakarta', 45000, 25000, '2 hari', 'Makanan', 'Oleh-oleh khas Yogyakarta.', 'uploads/products/bakpia.png', 'uploads/products/bakpia.png', 'active'),
(12, 2, 'Gudeg Yu Djum Kaleng', 'Indonesia', 'Yogyakarta', 65000, 30000, '2 hari', 'Makanan', 'Makanan khas Yogyakarta.', 'uploads/products/gudeg.png', 'uploads/products/gudeg.png', 'active'),
(13, 4, 'Pie Susu Bali', 'Indonesia', 'Bali', 55000, 20000, '2 hari', 'Makanan', 'Oleh-oleh khas Bali.', 'uploads/products/pie-susu.png', 'uploads/products/pie-susu.png', 'active'),
(14, 4, 'Kopi Kintamani', 'Indonesia', 'Bali', 90000, 25000, '2 hari', 'Makanan', 'Kopi arabika khas Bali.', 'uploads/products/kopi-kintamani.png', 'uploads/products/kopi-kintamani.png', 'active'),
(15, 5, 'Brownies Kukus Bandung', 'Indonesia', 'Bandung', 60000, 20000, '2 hari', 'Makanan', 'Oleh-oleh khas Bandung.', 'uploads/products/brownies.png', 'uploads/products/brownies.png', 'active'),
(16, 5, 'Keripik Maicih', 'Indonesia', 'Bandung', 30000, 15000, '2 hari', 'Makanan', 'Camilan pedas dari Bandung.', 'uploads/products/maicih.png', 'uploads/products/maicih.png', 'active'),

(17, 5, 'Kaya Jam Premium', 'Singapore', 'Orchard', 80000, 25000, '3 hari', 'Makanan', 'Selai kaya khas Singapura.', 'uploads/products/kaya-jam.png', 'uploads/products/kaya-jam.png', 'active'),
(18, 5, 'Snack Kaya Toast', 'Singapore', 'Orchard', 55000, 20000, '3 hari', 'Makanan', 'Camilan populer Singapura.', 'uploads/products/kaya-toast.png', 'uploads/products/kaya-toast.png', 'active'),
(19, 4, 'Bugis Street Souvenir', 'Singapore', 'Bugis', 75000, 25000, '3 hari', 'Souvenir', 'Souvenir khas area Bugis.', 'uploads/products/bugis-souvenir.png', 'uploads/products/bugis-souvenir.png', 'active'),
(20, 4, 'Merlion Keychain', 'Singapore', 'Bugis', 35000, 15000, '3 hari', 'Souvenir', 'Gantungan kunci khas Singapura.', 'uploads/products/merlion-keychain.png', 'uploads/products/merlion-keychain.png', 'active'),
(21, 5, 'TWG Tea', 'Singapore', 'Marina Bay', 180000, 35000, '3 hari', 'Makanan', 'Teh premium Singapura.', 'uploads/products/twg-tea.png', 'uploads/products/twg-tea.png', 'active'),
(22, 5, 'Marina Bay Chocolate', 'Singapore', 'Marina Bay', 95000, 25000, '3 hari', 'Makanan', 'Cokelat oleh-oleh dari Marina Bay.', 'uploads/products/marina-chocolate.png', 'uploads/products/marina-chocolate.png', 'active'),

(23, 7, 'Thai Tea Original', 'Thailand', 'Bangkok', 50000, 20000, '4 hari', 'Makanan', 'Minuman khas Thailand.', 'uploads/products/thai-tea.png', 'uploads/products/thai-tea.png', 'active'),
(24, 7, 'Mango Sticky Rice Pack', 'Thailand', 'Bangkok', 65000, 25000, '4 hari', 'Makanan', 'Snack rasa mango sticky rice.', 'uploads/products/mango-sticky.png', 'uploads/products/mango-sticky.png', 'active'),
(25, 7, 'Dried Mango Phuket', 'Thailand', 'Phuket', 70000, 25000, '4 hari', 'Makanan', 'Mangga kering khas Phuket.', 'uploads/products/dried-mango.png', 'uploads/products/dried-mango.png', 'active'),
(26, 7, 'Coconut Chips', 'Thailand', 'Phuket', 45000, 20000, '4 hari', 'Makanan', 'Keripik kelapa khas Thailand.', 'uploads/products/coconut-chips.png', 'uploads/products/coconut-chips.png', 'active'),
(27, 7, 'Chiang Mai Herbal Balm', 'Thailand', 'Chiang Mai', 55000, 20000, '4 hari', 'Kosmetik', 'Balm herbal khas Thailand.', 'uploads/products/herbal-balm.png', 'uploads/products/herbal-balm.png', 'active'),
(28, 7, 'Elephant Pants', 'Thailand', 'Chiang Mai', 120000, 30000, '4 hari', 'Fashion', 'Celana motif Thailand.', 'uploads/products/elephant-pants.png', 'uploads/products/elephant-pants.png', 'active'),

(29, 5, 'Skincare Korea', 'South Korea', 'Seoul', 150000, 35000, '5 hari', 'Kosmetik', 'Produk perawatan wajah Korea.', 'uploads/products/skincare-korea.png', 'uploads/products/skincare-korea.png', 'active'),
(30, 5, 'Korean Sheet Mask', 'South Korea', 'Seoul', 45000, 15000, '5 hari', 'Kosmetik', 'Masker wajah populer Korea.', 'uploads/products/sheet-mask.png', 'uploads/products/sheet-mask.png', 'active'),
(31, 5, 'Busan Fish Cake Pack', 'South Korea', 'Busan', 85000, 25000, '5 hari', 'Makanan', 'Makanan khas Busan.', 'uploads/products/fish-cake.png', 'uploads/products/fish-cake.png', 'active'),
(32, 5, 'Korean Seaweed', 'South Korea', 'Busan', 40000, 15000, '5 hari', 'Makanan', 'Rumput laut Korea.', 'uploads/products/seaweed.png', 'uploads/products/seaweed.png', 'active'),
(33, 5, 'Jeju Orange Chocolate', 'South Korea', 'Jeju', 90000, 25000, '5 hari', 'Makanan', 'Cokelat jeruk khas Jeju.', 'uploads/products/jeju-chocolate.png', 'uploads/products/jeju-chocolate.png', 'active'),
(34, 5, 'Jeju Green Tea', 'South Korea', 'Jeju', 110000, 25000, '5 hari', 'Makanan', 'Teh hijau khas Jeju.', 'uploads/products/jeju-tea.png', 'uploads/products/jeju-tea.png', 'active');

-- =========================================================
-- DUMMY DATA TRIPS
-- =========================================================
INSERT INTO trips
(id, traveler_id, origin_country, destination_city, trip_date, return_date, capacity_note, max_weight, max_items, categories, status)
VALUES
(1, 2, 'Yogyakarta (YIA)', 'Jakarta (CGK)', DATE_ADD(CURDATE(), INTERVAL 1 DAY), DATE_ADD(CURDATE(), INTERVAL 5 DAY), 'Sisa Kapasitas: 5kg', 5, 10, 'Makanan, Barang Rapuh', 'active'),
(2, 3, 'Japan', 'Jakarta', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 7 DAY), 'ETA hari ini 16:00', 8, 12, 'Makanan, Fashion', 'active'),
(3, 4, 'Indonesia', 'Jakarta', DATE_ADD(CURDATE(), INTERVAL 1 DAY), DATE_ADD(CURDATE(), INTERVAL 3 DAY), 'ETA besok 09:30', 10, 15, 'Makanan, Kosmetik', 'active'),
(4, 5, 'Singapore', 'Jakarta', DATE_ADD(CURDATE(), INTERVAL 3 DAY), DATE_ADD(CURDATE(), INTERVAL 6 DAY), 'ETA 23:59', 6, 8, 'Elektronik, Souvenir', 'active'),
(5, 2, 'South Korea', 'Jakarta', DATE_ADD(CURDATE(), INTERVAL 5 DAY), DATE_ADD(CURDATE(), INTERVAL 10 DAY), 'Barang kecil maksimal 4 kg', 4, 8, 'Kosmetik, Fashion', 'active'),
(6, 7, 'Thailand', 'Jakarta', DATE_ADD(CURDATE(), INTERVAL 7 DAY), DATE_ADD(CURDATE(), INTERVAL 12 DAY), 'Barang ringan maksimal 5 kg', 5, 10, 'Makanan, Fashion', 'inactive');

-- =========================================================
-- DUMMY DATA ORDERS
-- =========================================================
INSERT INTO orders
(id, buyer_id, traveler_id, product_id, product_name, product_country, product_city, product_price, service_fee, admin_fee, courier_fee, total_price, delivery_option, status, note)
VALUES
(1, 1, 2, 11, 'Bakpia Pathok 25', 'Indonesia', 'Yogyakarta', 45000, 25000, 5000, 15000, 90000, 'domestic_courier', 'completed', 'Pesanan selesai dan dana escrow sudah dapat dicairkan.'),
(2, 1, 2, 12, 'Gudeg Yu Djum Kaleng', 'Indonesia', 'Yogyakarta', 65000, 30000, 5000, 0, 100000, 'hand_carry', 'traveler_found', 'Menunggu bukti beli dari traveler.'),
(3, 6, 3, 3, 'Ramen Cup Ichiran', 'Japan', 'Osaka', 45000, 25000, 5000, 15000, 90000, 'domestic_courier', 'qc_in_progress', 'Buyer meminta foto expired date.'),
(4, 1, 5, 29, 'Skincare Korea', 'South Korea', 'Seoul', 150000, 35000, 7500, 20000, 212500, 'domestic_courier', 'on_delivery', 'Kurir domestik sedang mengantar paket.'),
(5, 6, 5, 33, 'Jeju Orange Chocolate', 'South Korea', 'Jeju', 90000, 25000, 5000, 0, 120000, 'hand_carry', 'request_created', 'Request baru menunggu traveler.'),
(6, 1, 4, 17, 'Kaya Jam Premium', 'Singapore', 'Orchard', 80000, 25000, 5000, 10000, 120000, 'domestic_courier', 'completed', 'Pesanan selesai dan buyer memberikan review.');

-- =========================================================
-- DUMMY DATA MANUAL REQUEST
-- =========================================================
INSERT INTO manual_requests
(id, buyer_id, traveler_id, product_name, description, reference_image, country, city, handling_type, estimated_price, traveler_fee, total_price, status)
VALUES
(1, 1, 2, 'Kimono Yukata', 'Cari kimono yukata warna merah ukuran M dari Tokyo.', 'uploads/request/kimono-reference.png', 'Japan', 'Tokyo', 'fragile', 450000, 75000, 525000, 'searching_traveler'),
(2, 6, 5, 'Merchandise Anime Limited', 'Cari merchandise anime edisi terbatas dari Osaka.', 'uploads/request/anime-reference.png', 'Japan', 'Osaka', 'standard', 180000, 35000, 215000, 'traveler_found');

INSERT INTO request_manuals
(id, buyer_id, traveler_id, product_name, description, reference_image, country, city, handling_type, item_price, traveler_fee, total_price, status)
VALUES
(1, 1, 2, 'Kimono Yukata', 'Cari kimono yukata warna merah ukuran M dari Tokyo.', 'uploads/request/kimono-reference.png', 'Japan', 'Tokyo', 'fragile', 450000, 75000, 525000, 'waiting_traveler'),
(2, 6, 5, 'Merchandise Anime Limited', 'Cari merchandise anime edisi terbatas dari Osaka.', 'uploads/request/anime-reference.png', 'Japan', 'Osaka', 'standard', 180000, 35000, 215000, 'waiting_traveler');

-- =========================================================
-- DUMMY DATA ESCROW
-- =========================================================
INSERT INTO escrow_transactions
(id, order_id, buyer_id, traveler_id, amount, status)
VALUES
(1, 1, 1, 2, 90000, 'released'),
(2, 2, 1, 2, 100000, 'locked'),
(3, 3, 6, 3, 90000, 'locked'),
(4, 4, 1, 5, 212500, 'locked'),
(5, 5, 6, 5, 120000, 'pending'),
(6, 6, 1, 4, 120000, 'released');

-- =========================================================
-- DUMMY DATA QC CHAT
-- =========================================================
INSERT INTO qc_messages
(id, order_id, sender_id, sender_role, message, photo_path, photo)
VALUES
(1, 3, 6, 'buyer', 'Halo Mas Budi, tolong fotokan expired date ramennya ya.', 'uploads/qc/default-qc.png', 'uploads/qc/default-qc.png'),
(2, 3, 3, 'traveler', 'Siap Kak, expired date masih 2 minggu lagi.', 'uploads/qc/ramen-expired.png', 'uploads/qc/ramen-expired.png'),
(3, 2, 1, 'buyer', 'Halo Mas Andi, tolong foto barang setelah dibeli ya.', 'uploads/qc/default-qc.png', 'uploads/qc/default-qc.png'),
(4, 2, 2, 'traveler', 'Baik Kak, saya akan upload struk dan foto barang setelah pembelian.', 'uploads/qc/default-qc.png', 'uploads/qc/default-qc.png');

-- =========================================================
-- DUMMY DATA REVIEWS
-- =========================================================
INSERT INTO reviews
(id, order_id, buyer_id, traveler_id, store_rating, traveler_rating, courier_rating, review_store, review_traveler, review_courier)
VALUES
(1, 1, 1, 2, 5, 5, 5, 'Produk UMKM masih fresh dan sesuai pesanan.', 'Traveler komunikatif dan cepat mengirim bukti QC.', 'Kurir datang tepat waktu.'),
(2, 6, 1, 4, 5, 4, 5, 'Produk dikemas rapi.', 'Traveler cukup responsif dan ramah.', 'Paket sampai aman.');

INSERT INTO traveler_reviews
(id, traveler_id, buyer_id, rating, review)
VALUES
(1, 2, 1, 5, 'Traveler sangat komunikatif dan cepat mengirim bukti foto di QC Room.'),
(2, 2, 6, 5, 'Titip barang dari Tokyo sampai utuh dan sesuai request.'),
(3, 3, 6, 4, 'Barang sampai aman dan proses tracking cukup jelas.'),
(4, 4, 1, 5, 'Sangat membantu untuk pengiriman domestik.'),
(5, 5, 1, 5, 'Traveler terpercaya untuk produk Korea dan Singapura.');

-- =========================================================
-- DUMMY DATA SUBSCRIPTIONS BUYER
-- =========================================================
INSERT INTO subscriptions
(id, user_id, package_type, package_name, amount, start_date, end_date, status)
VALUES
(1, 1, 'monthly', 'Bulanan', 49000, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 MONTH), 'active'),
(2, 6, 'yearly', 'Tahunan', 399000, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR), 'active');

-- =========================================================
-- DUMMY DATA KYC TRAVELER
-- =========================================================
INSERT INTO traveler_kyc
(id, user_id, ktp_number, passport_number, ktp_image, passport_image, selfie_image, bank_name, account_number, account_holder, deposit_amount, verification_status, badge_status)
VALUES
(1, 2, '3276010101010001', 'A1234567', 'uploads/kyc/andi-ktp.png', 'uploads/kyc/andi-passport.png', 'uploads/kyc/andi-selfie.png', 'Bank BCA', '8829 1111 2222', 'Traveler Demo', 200000, 'approved', 'Verified'),
(2, 3, '3276010101010002', 'B1234567', 'uploads/kyc/budi-ktp.png', 'uploads/kyc/budi-passport.png', 'uploads/kyc/budi-selfie.png', 'Bank Mandiri', '1234 5555 6666', 'Budi Santoso', 200000, 'approved', 'Verified'),
(3, 4, '3276010101010003', 'C1234567', 'uploads/kyc/sinta-ktp.png', 'uploads/kyc/sinta-passport.png', 'uploads/kyc/sinta-selfie.png', 'Bank BNI', '7777 8888 9999', 'Sinta Lestari', 200000, 'pending', 'Under Review'),
(4, 5, '3276010101010004', 'D1234567', 'uploads/kyc/bayu-ktp.png', 'uploads/kyc/bayu-passport.png', 'uploads/kyc/bayu-selfie.png', 'Bank BRI', '4444 5555 6666', 'Bayu Pratama', 200000, 'approved', 'Verified'),
(5, 7, '3276010101010005', 'E1234567', 'uploads/kyc/fardil-ktp.png', 'uploads/kyc/fardil-passport.png', 'uploads/kyc/fardil-selfie.png', 'Bank BCA', '9999 0000 1111', 'Fardil', 200000, 'pending', 'Under Review');

-- =========================================================
-- DUMMY DATA SOP AUDIT
-- =========================================================
INSERT INTO sop_audit_logs
(id, order_id, traveler_id, step_code, step_title, note, photo_path)
VALUES
(1, 2, 2, 'buy_item', 'Tahap 1 – Membeli Barang', 'Traveler membeli Gudeg Yu Djum Kaleng sesuai pesanan buyer.', 'uploads/sop/gudeg-struk.png'),
(2, 2, 2, 'verify_item', 'Tahap 2 – Verifikasi Barang', 'Warna, ukuran, dan spesifikasi barang sesuai permintaan.', 'uploads/sop/gudeg-produk.png'),
(3, 3, 3, 'quality_control', 'Tahap 3 – Quality Control (QC)', 'Traveler mengunggah foto expired date ramen.', 'uploads/sop/ramen-qc.png');

-- =========================================================
-- DUMMY DATA LOCAL FINDER
-- =========================================================
INSERT INTO local_broadcasts
(id, traveler_id, product_id, product_name, country, city, price, commission, eta, label_tag, image_path, status)
VALUES
(1, 2, 1, 'Tokyo Banana', 'Japan', 'Tokyo', 120000, 25000, '3 hari', 'Langka', 'uploads/products/tokyo-banana.png', 'active'),
(2, 3, 3, 'Ramen Cup Ichiran', 'Japan', 'Osaka', 45000, 25000, '2 hari', 'Trending', 'uploads/products/ramen-cup.png', 'active'),
(3, 5, 29, 'Skincare Korea', 'South Korea', 'Seoul', 150000, 35000, '5 hari', 'Diskon Lokal', 'uploads/products/skincare-korea.png', 'active'),
(4, 7, 23, 'Thai Tea Original', 'Thailand', 'Bangkok', 50000, 20000, '4 hari', 'Langka', 'uploads/products/thai-tea.png', 'active');

-- =========================================================
-- DUMMY DATA WITHDRAWAL
-- =========================================================
INSERT INTO withdrawal_requests
(id, traveler_id, amount, bank_name, account_number, status)
VALUES
(1, 2, 25000, 'Bank BCA', '8829 1111 2222', 'approved'),
(2, 4, 25000, 'Bank BNI', '7777 8888 9999', 'pending'),
(3, 5, 35000, 'Bank BRI', '4444 5555 6666', 'approved');

-- =========================================================
-- DUMMY DATA TRAVELER PLUS
-- =========================================================
INSERT INTO traveler_subscriptions
(id, traveler_id, package_name, amount, start_date, end_date, status)
VALUES
(1, 2, 'Super Traveler 3 Bulan', 199000, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 3 MONTH), 'active'),
(2, 5, 'Super Traveler 3 Bulan', 199000, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 3 MONTH), 'active');
