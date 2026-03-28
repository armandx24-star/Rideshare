<?php
require_once 'includes/config.php';
session_start();
session_unset();
session_destroy();
header('Location: ' . BASE_URL . '/login.php');
exit();
