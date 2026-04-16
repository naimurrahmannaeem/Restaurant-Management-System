# 🔥 BiteBurst Restaurant Management System
**NSU CSE311L – Group 08 | Section 06**
Md. Naeem Mia · Md. Rowshon Jamil Rifat · S M Mahamud Iqbal

---

## 📁 Project Structure

```
restaurant/
├── index.php                  ← Homepage
├── database.sql               ← All 11 tables + sample data
├── config/
│   └── db.php                 ← DB connection + helpers
├── includes/
│   ├── header.php             ← Customer nav header
│   └── footer.php             ← Footer
├── customer/
│   ├── menu.php               ← Full menu with category filter
│   ├── cart.php               ← Shopping cart
│   ├── cart_action.php        ← AJAX: add/remove/update cart
│   ├── checkout.php           ← Place order + payment method
│   ├── order_success.php      ← Confirmation page
│   ├── orders.php             ← Customer order history
│   ├── order_detail.php       ← Order detail + leave review
│   ├── login.php              ← Customer login
│   ├── register.php           ← Customer registration
│   └── logout.php
├── admin/
│   ├── login.php              ← Admin login
│   ├── dashboard.php          ← Stats + recent orders
│   ├── orders.php             ← Manage + update order status
│   ├── menu_items.php         ← Add / edit / delete menu items
│   ├── categories.php         ← Manage categories
│   ├── customers.php          ← View all customers
│   ├── reviews.php            ← View / delete reviews
│   ├── reports.php            ← Revenue reports by date range
│   ├── admins.php             ← Manage admin accounts
│   ├── logout.php
│   └── includes/
│       ├── admin_header.php
│       └── admin_footer.php
└── assets/
    ├── css/style.css
    └── js/main.js
```

---

## 🗄️ Database Tables (11 tables)

| Table        | Purpose                                    |
|--------------|--------------------------------------------|
| Restaurant   | Restaurant info (name, address, hours)     |
| Category     | Menu categories (Burgers, Pizza, etc.)     |
| MenuItem     | All food items with price, tags            |
| Customer     | Registered customers                       |
| Admin        | Admin/staff accounts with roles            |
| Cart         | One active cart per customer               |
| CartItem     | Items inside a cart (quantity)             |
| Order        | Customer orders with status & type         |
| OrderItem    | Line items of each order (price snapshot)  |
| Payment      | Payment record per order                   |
| Review       | Customer ratings & comments per item       |

---

## ⚙️ Setup Instructions

### Step 1 – Requirements
- PHP 7.4 or higher
- MySQL 5.7 or higher
- XAMPP / WAMP / Laragon (any local server)

### Step 2 – Place project files
Copy the `restaurant/` folder into your web server root:
- XAMPP → `C:/xampp/htdocs/restaurant/`
- WAMP  → `C:/wamp64/www/restaurant/`

### Step 3 – Create the database
1. Open **phpMyAdmin** → http://localhost/phpmyadmin
2. Click **"New"** → create database named `restaurant_db`
3. Click **Import** → choose `restaurant/database.sql` → click **Go**

### Step 4 – Configure DB connection
Open `config/db.php` and update if needed:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');   // your MySQL username
define('DB_PASS', '');       // your MySQL password (blank for XAMPP default)
define('DB_NAME', 'restaurant_db');
```

### Step 5 – Set admin password
The default admin in `database.sql` uses a placeholder hash.
Run this once in your browser or CLI to get a real hash:

**Option A – Quick fix via phpMyAdmin:**
1. Go to phpMyAdmin → restaurant_db → Admin table
2. Edit the row → password field → set type to **function** → type:
   `MD5('admin123')` ← but bcrypt is better, see Option B

**Option B – Correct bcrypt (recommended):**
Create a file `restaurant/set_admin_pass.php`:
```php
<?php
require_once 'config/db.php';
$hash = password_hash('admin123', PASSWORD_BCRYPT);
$conn->query("UPDATE Admin SET password='$hash' WHERE username='admin'");
echo "Done! Hash: $hash";
```
Visit: http://localhost/restaurant/set_admin_pass.php
Then **delete the file**.

### Step 6 – Open the website
- **Customer site:** http://localhost/restaurant/index.php
- **Admin panel:**  http://localhost/restaurant/admin/login.php

**Default admin credentials:**
- Username: `admin`
- Password: `admin123` (after running Step 5)

---

## 🌟 Features Summary

### Customer Side
- **Interactive Homepage:** Features an immersive animated bonfire hero section.
- **Optimized Menu:** Fast, GPU-accelerated smooth scrolling through the full menu with category filters.
- **Cart & Checkout:** Add items to cart (AJAX, no page reload), adjust quantities, and checkout with delivery address, order type, and payment method.
- **Order Management:** View order history with live status updates, and **cancel pending orders**.
- **Promotions:** Free delivery on orders ৳500+.
- **Feedback:** Leave star ratings & comments after delivery.

### Admin Panel
- **Comprehensive Dashboard:** 8 live stat cards for quick business insights.
- **Advanced Order Management:** Full order CRUD. Update statuses (pending → confirmed → preparing → out for delivery → delivered), edit order items (bill updates), cancel orders, and delete orders.
- **Menu Management:** Full menu CRUD (add/edit/delete/toggle availability smoothly) with a **robust drag-and-drop Image Upload** interface.
- **Category & Customer Management:** Full category control and customer directory with search and **account deletion capabilities**.
- **Review Moderation:** View and moderate customer feedback.
- **Analytics:** Revenue reports with date range filters (daily breakdown, by category, by payment method).
- **Security:** Multi-admin management with roles (superadmin / manager / staff) and properly hashed passwords.

---

## 🔑 Order Flow
```
Customer registers → Browses menu → Adds to cart
→ Checkout (address + payment) → Order placed (pending)
→ Admin confirms → Admin marks preparing
→ Admin marks out for delivery → Delivered
→ Customer can leave a review
```

---

## 📊 ER Diagram Summary
```
Restaurant ─── MenuItem ─── Category
    │               │
   Order ──── OrderItem
    │
 Customer ──── Cart ──── CartItem
    │
 Payment
    │
  Review ──── MenuItem
```

---

*North South University | Dept. of ECE | CSE311L Database Systems Lab*
