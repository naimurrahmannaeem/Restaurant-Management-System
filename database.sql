-- ============================================================
--  RESTAURANT MANAGEMENT SYSTEM — DATABASE SCHEMA
--  North South University | CSE311L | Group 08
--  Tables: 11 (Restaurant, Category, MenuItem, Customer,
--           Admin, Cart, CartItem, Order, OrderItem,
--           Payment, Review)
-- ============================================================

CREATE DATABASE IF NOT EXISTS restaurant_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE restaurant_db;

-- ------------------------------------------------------------
-- 1. RESTAURANT
-- ------------------------------------------------------------
CREATE TABLE Restaurant (
    restaurant_id   INT AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100)  NOT NULL,
    address         VARCHAR(255)  NOT NULL,
    phone           VARCHAR(20)   NOT NULL,
    email           VARCHAR(100),
    opening_hours   VARCHAR(100)  DEFAULT '10:00 AM – 11:00 PM',
    logo_url        VARCHAR(255),
    cover_url       VARCHAR(255),
    description     TEXT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- 2. CATEGORY  (e.g. Burgers, Pizza, Drinks, Desserts)
-- ------------------------------------------------------------
CREATE TABLE Category (
    category_id   INT AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(80)  NOT NULL,
    icon          VARCHAR(10)  DEFAULT '🍽️',
    display_order INT          DEFAULT 0
);

-- ------------------------------------------------------------
-- 3. MENU_ITEM
-- ------------------------------------------------------------
CREATE TABLE MenuItem (
    item_id        INT AUTO_INCREMENT PRIMARY KEY,
    restaurant_id  INT          NOT NULL,
    category_id    INT          NOT NULL,
    name           VARCHAR(120) NOT NULL,
    description    TEXT,
    price          DECIMAL(8,2) NOT NULL,
    image_url      VARCHAR(255),
    ingredients    TEXT,
    is_non_veg     TINYINT(1)   DEFAULT 0,
    is_spicy       TINYINT(1)   DEFAULT 0,
    is_featured    TINYINT(1)   DEFAULT 0,
    available      TINYINT(1)   DEFAULT 1,
    created_at     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (restaurant_id) REFERENCES Restaurant(restaurant_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id)   REFERENCES Category(category_id)    ON DELETE RESTRICT
);

-- ------------------------------------------------------------
-- 4. CUSTOMER
-- ------------------------------------------------------------
CREATE TABLE Customer (
    customer_id   INT AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(50)  NOT NULL UNIQUE,
    password      VARCHAR(255) NOT NULL,          -- store hashed
    full_name     VARCHAR(100) NOT NULL,
    email         VARCHAR(100) NOT NULL UNIQUE,
    phone         VARCHAR(20),
    address       TEXT,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- 5. ADMIN
-- ------------------------------------------------------------
CREATE TABLE Admin (
    admin_id   INT AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(50)  NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,             -- store hashed
    full_name  VARCHAR(100) NOT NULL,
    email      VARCHAR(100) NOT NULL UNIQUE,
    role       ENUM('superadmin','manager','staff') DEFAULT 'staff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- 6. CART  (one active cart per customer)
-- ------------------------------------------------------------
CREATE TABLE Cart (
    cart_id       INT AUTO_INCREMENT PRIMARY KEY,
    customer_id   INT NOT NULL UNIQUE,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES Customer(customer_id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- 7. CART_ITEM
-- ------------------------------------------------------------
CREATE TABLE CartItem (
    cart_item_id  INT AUTO_INCREMENT PRIMARY KEY,
    cart_id       INT            NOT NULL,
    item_id       INT            NOT NULL,
    quantity      INT            NOT NULL DEFAULT 1,
    added_at      TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cart_id) REFERENCES Cart(cart_id)     ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES MenuItem(item_id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- 8. ORDER
-- ------------------------------------------------------------
CREATE TABLE `Order` (
    order_id        INT AUTO_INCREMENT PRIMARY KEY,
    customer_id     INT            NOT NULL,
    restaurant_id   INT            NOT NULL,
    order_date      TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
    delivery_address TEXT          NOT NULL,
    status          ENUM('pending','confirmed','preparing',
                         'out_for_delivery','delivered','cancelled')
                    DEFAULT 'pending',
    order_type      ENUM('delivery','dine_in','takeaway') DEFAULT 'delivery',
    total_amount    DECIMAL(10,2)  NOT NULL,
    special_notes   TEXT,
    FOREIGN KEY (customer_id)   REFERENCES Customer(customer_id)     ON DELETE RESTRICT,
    FOREIGN KEY (restaurant_id) REFERENCES Restaurant(restaurant_id) ON DELETE RESTRICT
);

-- ------------------------------------------------------------
-- 9. ORDER_ITEM
-- ------------------------------------------------------------
CREATE TABLE OrderItem (
    order_item_id  INT AUTO_INCREMENT PRIMARY KEY,
    order_id       INT            NOT NULL,
    item_id        INT            NOT NULL,
    quantity       INT            NOT NULL,
    unit_price     DECIMAL(8,2)   NOT NULL,        -- price at time of order
    FOREIGN KEY (order_id) REFERENCES `Order`(order_id)   ON DELETE CASCADE,
    FOREIGN KEY (item_id)  REFERENCES MenuItem(item_id)   ON DELETE RESTRICT
);

-- ------------------------------------------------------------
-- 10. PAYMENT
-- ------------------------------------------------------------
CREATE TABLE Payment (
    payment_id      INT AUTO_INCREMENT PRIMARY KEY,
    order_id        INT            NOT NULL UNIQUE,
    amount          DECIMAL(10,2)  NOT NULL,
    payment_method  ENUM('cash','card','mobile_banking') DEFAULT 'cash',
    payment_status  ENUM('pending','paid','failed','refunded')  DEFAULT 'pending',
    transaction_id  VARCHAR(100),
    payment_date    TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES `Order`(order_id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- 11. REVIEW
-- ------------------------------------------------------------
CREATE TABLE Review (
    review_id    INT AUTO_INCREMENT PRIMARY KEY,
    customer_id  INT  NOT NULL,
    item_id      INT  NOT NULL,
    order_id     INT  NOT NULL,
    rating       TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment      TEXT,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES Customer(customer_id) ON DELETE CASCADE,
    FOREIGN KEY (item_id)     REFERENCES MenuItem(item_id)     ON DELETE CASCADE,
    FOREIGN KEY (order_id)    REFERENCES `Order`(order_id)    ON DELETE CASCADE
);

-- ============================================================
--  SAMPLE DATA
-- ============================================================

-- Restaurant
INSERT INTO Restaurant (name, address, phone, email, opening_hours, description) VALUES
('BiteBurst', 'Bashundhara, Dhaka-1229', '+880-1700-000001',
 'info@biteburst.com', '10:00 AM – 11:00 PM',
 'Flame-grilled burgers, crispy fried chicken, wood-fired pizza & more.');

-- Categories
INSERT INTO Category (name, icon, display_order) VALUES
('Burgers',  '🍔', 1),
('Pizza',    '🍕', 2),
('Chicken',  '🍗', 3),
('Pasta',    '🍝', 4),
('Sides',    '🍟', 5),
('Drinks',   '🥤', 6),
('Desserts', '🍰', 7);

-- Menu Items (1=Burgers,2=Pizza,3=Chicken,4=Pasta,5=Sides,6=Drinks,7=Desserts)
INSERT INTO MenuItem (restaurant_id, category_id, name, description, price, is_non_veg, is_spicy, is_featured, available) VALUES
(1,1,'Classic Smash Burger','Double smash patty, cheddar, pickles, special sauce',320,1,0,1,1),
(1,1,'BBQ Bacon Burger','Crispy bacon, BBQ sauce, caramelized onion, smoky patty',380,1,0,1,1),
(1,1,'Spicy Fiesta Burger','Jalapeño, chipotle mayo, pepper jack, fiery patty',360,1,1,0,1),
(1,1,'Mushroom Swiss Burger','Sautéed mushrooms, Swiss cheese, garlic aioli',340,1,0,0,1),
(1,2,'Pepperoni Feast Pizza','Loaded pepperoni, mozzarella, tomato base',450,1,0,1,1),
(1,2,'BBQ Chicken Pizza','Grilled chicken, BBQ sauce, red onion, cilantro',430,1,0,0,1),
(1,2,'Veggie Supreme Pizza','Bell peppers, olives, mushrooms, onion, tomato',390,0,0,0,1),
(1,2,'Margherita Classic','Fresh basil, mozzarella, San Marzano tomato',360,0,0,0,1),
(1,3,'Crispy Fried Chicken (4 pc)','Southern-spiced, double-battered, golden fried',350,1,0,1,1),
(1,3,'Spicy Wings (6 pc)','Buffalo-style tossed in fiery hot sauce',280,1,1,0,1),
(1,3,'Chicken Strips (5 pc)','Tender strips with honey mustard dip',260,1,0,0,1),
(1,4,'Spaghetti Bolognese','Slow-cooked meat sauce, parmesan, fresh herbs',320,1,0,0,1),
(1,4,'Penne Arrabbiata','Spicy tomato sauce, garlic, basil, olive oil',280,0,1,0,1),
(1,5,'Loaded Cheese Fries','Crispy fries, cheddar sauce, jalapeños',180,0,0,1,1),
(1,5,'Onion Rings (8 pc)','Beer-battered, golden crispy, ranch dip',150,0,0,0,1),
(1,5,'Coleslaw','Creamy house-made coleslaw',80,0,0,0,1),
(1,6,'Coca-Cola (500ml)','Ice-cold Coca-Cola',60,0,0,0,1),
(1,6,'Mango Lassi','Fresh mango, yogurt, cardamom',120,0,0,0,1),
(1,6,'Lemonade (Fresh)','Freshly squeezed with mint',90,0,0,0,1),
(1,7,'Chocolate Lava Cake','Warm molten chocolate cake, vanilla ice cream',220,0,0,1,1),
(1,7,'Cheesecake Slice','New York-style, berry compote',180,0,0,0,1);

-- Default admin  (password = "admin123"  — SHA-256 hash stored; in PHP use password_hash)
INSERT INTO Admin (username, password, full_name, email, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'Restaurant Admin', 'admin@biteburst.com', 'superadmin');
-- NOTE: The hash above is the bcrypt of "password" (Laravel default).
--       Run:  php -r "echo password_hash('admin123', PASSWORD_BCRYPT);"
--       and replace the hash before going live.
