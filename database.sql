-- 데이터베이스 생성
CREATE DATABASE IF NOT EXISTS board_system;
USE board_system;

DROP TABLE IF EXISTS cart;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS comments;
DROP TABLE IF EXISTS files;
DROP TABLE IF EXISTS posts;
DROP TABLE IF EXISTS users;
 

-- 사용자 테이블
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    profile VARCHAR(255) DEFAULT NULL,
    balance INT NOT NULL DEFAULT 10000,
    role ENUM('user','admin') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 게시글 테이블
CREATE TABLE posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    user_id INT NOT NULL,
    filename VARCHAR(255),
    role ENUM('user','admin') NOT NULL DEFAULT 'user',
    is_secret BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);


-- 비밀번호 초기화
CREATE TABLE password_resets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token_hash VARCHAR(255) NOT NULL,
  expires_at DATETIME NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 재고 관리 없음
CREATE TABLE products(
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price INT NOT NULL,
    category VARCHAR(50),
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 장바구니
CREATE TABLE cart(
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_product (user_id, product_id),
    INDEX idx_user_id (user_id)
);

INSERT INTO users (username, email, password, role)
VALUES
('alice', 'alice@example.com', '$2a$12$z48Ha4BLyQFUm/ecFLiE1Ot7T4QOPW5GKRt8nEU8X32J6txRrirH.', 'user'), 
('bob', 'bob@example.com', '$2a$12$z48Ha4BLyQFUm/ecFLiE1Ot7T4QOPW5GKRt8nEU8X32J6txRrirH.', 'admin');


-- posts 더미 데이터 2개
INSERT INTO posts (title, content, user_id, filename,role)
VALUES
('Hello World ? ', "What's the tea today? ", 1, 'file1.txt','user'),
('[important] Admin Note! ', "this is absolutely tiring, we are running out of stocks right now. normal users must not see this content", 2, 'file2.txt','admin');

INSERT INTO comments (post_id, user_id, content)
VALUES
(1, 1, 'who cares?'),
(1, 2, 'I am an admin btw'),
(2, 1, 'Whats up');

INSERT INTO products (name, description, price, category, image_url)
VALUES
('Americano', 'Classic Starbucks Americano', 4500, 'coffee', '../assets/images/americano.jpg'),
('Cafe Latte', 'Smooth blend of milk and espresso', 5000, 'coffee', '../assets/images/latte.jpg'),
('Caramel Macchiato', 'Sweet caramel with espresso', 5500, 'coffee', '../assets/images/macchiato.jpg'),
('Frappuccino', 'Refreshing blended beverage', 6000, 'coffee', '../assets/images/frappuccino.jpg'),
('Croissant', 'Crispy butter croissant', 3000, 'bakery', '../assets/images/croissant.jpg'),
('Muffin', 'Sweet blueberry muffin', 3500, 'bakery', '../assets/images/muffin.jpg'),
('Gift Card', 'Premium Starbucks Gift Card', 5000, 'giftCard', '../assets/images/giftcard.jpg'),
('Expensive Gift', 'Really expensive premium gift', 20000, 'giftCard', '../assets/images/giftcard.jpg');
