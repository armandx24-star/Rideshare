<?php

require_once __DIR__ . '/config.php';


function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) || isset($_SESSION['driver_id']) || isset($_SESSION['admin_id']);
}


function requireRole(string $role): void {
    switch ($role) {
        case 'user':
            if (!isset($_SESSION['user_id'])) {
                header('Location: ' . BASE_URL . '/login.php?role=user');
                exit();
            }
            break;
        case 'driver':
            if (!isset($_SESSION['driver_id'])) {
                header('Location: ' . BASE_URL . '/login.php?role=driver');
                exit();
            }
            break;
        case 'admin':
            if (!isset($_SESSION['admin_id'])) {
                header('Location: ' . BASE_URL . '/admin/login.php');
                exit();
            }
            break;
    }
}


function redirectIfLoggedIn(): void {
    if (isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/user/');
        exit();
    }
    if (isset($_SESSION['driver_id'])) {
        header('Location: ' . BASE_URL . '/driver/');
        exit();
    }
    if (isset($_SESSION['admin_id'])) {
        header('Location: ' . BASE_URL . '/admin/');
        exit();
    }
}


function getCurrentUser(): array {
    return [
        'id'   => $_SESSION['user_id'] ?? null,
        'name' => $_SESSION['user_name'] ?? '',
        'role' => 'user',
    ];
}

function getCurrentDriver(): array {
    return [
        'id'   => $_SESSION['driver_id'] ?? null,
        'name' => $_SESSION['driver_name'] ?? '',
        'role' => 'driver',
    ];
}

function getCurrentAdmin(): array {
    return [
        'id'       => $_SESSION['admin_id'] ?? null,
        'username' => $_SESSION['admin_username'] ?? '',
        'role'     => 'admin',
    ];
}
