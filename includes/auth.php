<?php
session_start();
require 'db.php';

// 检查是否登录
function isLoggedIn() {
    return isset($_SESSION['admin_id']);
}

// 登录功能
function login($username, $password) {
    global $db;
    $stmt = $db->prepare('SELECT * FROM admins WHERE username = :username');
    $stmt->execute(['username' => $username]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['id'];
        return true;
    }
    return false;
}

// 注销功能
function logout() {
    session_destroy();
    header('Location: index.php');
    exit();
}
?>