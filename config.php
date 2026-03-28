<?php

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'rideshare');

define('BASE_URL', 'http://localhost/arman-new');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('APP_NAME', 'RideShare');

date_default_timezone_set('Asia/Kolkata');

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
