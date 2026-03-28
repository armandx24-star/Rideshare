# RideShare — Uber/Ola Clone

A complete ride-booking web application built with **Core PHP**, **MySQL**, **Bootstrap 5**, and **Leaflet.js (OpenStreetMap)**.

---

## 🚀 Quick Setup (WAMP/XAMPP)

### 1. Place Project
Copy the `arman-new` folder to your web root:
- **WAMP**: `C:\wamp64\www\arman-new\`
- **XAMPP**: `C:\xampp\htdocs\arman-new\`

### 2. Import Database
- Open **phpMyAdmin** → `http://localhost/phpmyadmin`
- Click **Import** tab
- Select file: `arman-new/rideshare.sql`
- Click **Go**

### 3. Configure (if needed)
Open `includes/config.php` and verify:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');          // ← change if you have a MySQL password
define('DB_NAME', 'rideshare');
define('BASE_URL', 'http://localhost/arman-new');
```

### 4. Create Uploads Folder Permissions
The `uploads/` folder is auto-created. If document uploads fail, right-click it and give write permissions.

### 5. Visit the App
Open your browser: **http://localhost/arman-new**

---

## 🔑 Demo Credentials

| Role   | Login               | Password   |
|--------|---------------------|------------|
| User   | user@demo.com       | password   |
| Driver | driver@demo.com     | password   |
| Admin  | admin (username)    | admin123   |

> **Admin URL**: http://localhost/arman-new/admin/login.php

---

## 📁 Project Structure

```
arman-new/
├── rideshare.sql              ← Database schema + seed data
├── index.php                  ← Landing page
├── login.php                  ← Login (all roles)
├── register.php               ← User & Driver registration
├── logout.php
│
├── includes/
│   ├── config.php             ← DB credentials & constants
│   ├── db.php                 ← PDO singleton
│   ├── auth.php               ← Session & role helpers
│   ├── functions.php          ← Haversine, fare calc, helpers
│   ├── header.php / footer.php
│
├── assets/
│   ├── css/style.css          ← Dark modern UI
│   ├── js/map.js              ← Leaflet + Nominatim geocoding
│   └── js/app.js              ← AJAX helpers, toasts, polling
│
├── user/                      ← Customer Panel
│   ├── index.php              ← Dashboard (map + booking)
│   ├── book_ride.php          ← AJAX booking endpoint
│   ├── get_fares.php          ← AJAX fare calculator
│   ├── ride_status.php        ← AJAX status polling
│   ├── cancel_ride.php        ← AJAX cancel
│   ├── rate_ride.php          ← AJAX rating submission
│   ├── history.php            ← Ride history
│   └── profile.php            ← Edit profile + password
│
├── driver/                    ← Driver Panel
│   ├── index.php              ← Dashboard (toggle + requests)
│   ├── toggle_status.php      ← AJAX online/offline
│   ├── accept_ride.php        ← AJAX accept
│   ├── reject_ride.php        ← AJAX reject (re-assigns ride)
│   ├── start_ride.php         ← AJAX start
│   ├── end_ride.php           ← AJAX complete
│   ├── check_requests.php     ← AJAX poll for new rides
│   ├── history.php
│   ├── earnings.php           ← Chart.js earnings graph
│   └── profile.php
│
├── admin/                     ← Admin Panel
│   ├── login.php
│   ├── index.php              ← Dashboard + charts
│   ├── users.php              ← Manage users
│   ├── drivers.php            ← Approve/reject/deactivate
│   ├── rides.php              ← View all rides (filterable)
│   ├── fare_settings.php      ← Edit pricing per vehicle type
│   ├── reports.php            ← Daily revenue report (printable)
│   ├── logout.php
│   └── includes/
│       ├── sidebar.php
│       └── footer.php
│
└── uploads/                   ← Driver documents (auto-created)
    └── .htaccess              ← Blocks PHP execution in uploads
```

---

## 🔄 Full Ride Flow

```
1. USER registers → logs in → /user/
2. USER enters pickup + drop on map (Nominatim autocomplete)
3. USER sees fare for Bike/Mini/Sedan (Haversine distance)
4. USER clicks "Book Ride" → ride created as "pending"
5. SYSTEM assigns nearest available online+approved driver
6. DRIVER sees request on dashboard (polls every 5s)
7. DRIVER clicks "Accept" → ride becomes "accepted"
8. USER dashboard updates to show driver info (polls every 4s)
9. DRIVER clicks "Start Ride" → "ongoing"
10. DRIVER clicks "End Ride" → "completed"
11. USER can rate the driver (1–5 stars)
12. ADMIN sees all rides, revenue, and can manage everything
```

---

## 🔒 Security Features

- `password_hash()` / `password_verify()` for all passwords
- PDO prepared statements (no SQL injection)
- Session-based role enforcement (`requireRole()`)
- Uploads folder blocked from PHP execution (`.htaccess`)
- Input sanitization via `sanitize()` helper

---

## 💡 Tech Stack

| Layer        | Technology                |
|--------------|---------------------------|
| Backend      | Core PHP 8+, PDO          |
| Database     | MySQL 5.7+ / MariaDB      |
| Frontend     | Bootstrap 5, Vanilla JS   |
| Maps         | Leaflet.js + OpenStreetMap|
| Geocoding    | Nominatim (free, no API key) |
| Charts       | Chart.js                  |
| Icons        | Bootstrap Icons           |
| Fonts        | Google Fonts (Inter)      |
