<?php
session_start();

class AdminAuth {
    private static $adminCredentials = [
        'admin' => 'admin123',
        'manager' => 'manager456'
    ];
    
    public static function login($username, $password) {
        if (isset(self::$adminCredentials[$username]) && 
            self::$adminCredentials[$username] === $password) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $username;
            $_SESSION['admin_login_time'] = time();
            return true;
        }
        return false;
    }
    
    public static function logout() {
        $_SESSION['admin_logged_in'] = false;
        unset($_SESSION['admin_username']);
        unset($_SESSION['admin_login_time']);
        session_destroy();
    }
    
    public static function isLoggedIn() {
        return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
    }
    
    public static function requireAuth() {
        if (!self::isLoggedIn()) {
            header('Location: admin_login.php');
            exit;
        }
    }
    
    public static function getUsername() {
        return $_SESSION['admin_username'] ?? 'Unknown';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (AdminAuth::login($username, $password)) {
        header('Location: admin_dashboard.php');
        exit;
    } else {
        $loginError = 'Invalid username or password';
    }
}

if (isset($_GET['logout'])) {
    AdminAuth::logout();
    header('Location: admin_login.php');
    exit;
}
?>