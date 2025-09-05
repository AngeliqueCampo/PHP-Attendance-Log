<?php
// session management for auth
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class Session {
    
    // start user session after successful login
    public static function login($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['student_id'] = $user['student_id'] ?? null;
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
    }
    
    // destroy user session
    public static function logout() {
        session_unset();
        session_destroy();
    }
    
    // check if user is logged in
    public static function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    // check if user is admin
    public static function isAdmin() {
        return self::isLoggedIn() && $_SESSION['role'] === 'admin';
    }
    
    // check if user is student
    public static function isStudent() {
        return self::isLoggedIn() && $_SESSION['role'] === 'student';
    }
    
    // get current user data
    public static function getCurrentUser() {
        if (!self::isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'role' => $_SESSION['role'],
            'student_id' => $_SESSION['student_id'] ?? null,
            'login_time' => $_SESSION['login_time']
        ];
    }
    
    // get current user's student ID (for student users)
    public static function getCurrentStudentId() {
        return $_SESSION['student_id'] ?? null;
    }
    
    // redirect if not logged in
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header('Location: index.php');
            exit();
        }
    }
    
    // redirect if not admin
    public static function requireAdmin() {
        self::requireLogin();
        if (!self::isAdmin()) {
            header('Location: student/dashboard.php');
            exit();
        }
    }
    
    // redirect if not student
    public static function requireStudent() {
        self::requireLogin();
        if (!self::isStudent()) {
            header('Location: admin/dashboard.php');
            exit();
        }
    }
    
    // redirect logged in users away from login page
    public static function redirectIfLoggedIn() {
        if (self::isLoggedIn()) {
            if (self::isAdmin()) {
                header('Location: admin/dashboard.php');
            } else {
                header('Location: student/dashboard.php');
            }
            exit();
        }
    }
    
    // check session timeout (optional - 8 hours)
    public static function checkTimeout($timeout_duration = 28800) {
        if (self::isLoggedIn()) {
            $login_time = $_SESSION['login_time'] ?? 0;
            if ((time() - $login_time) > $timeout_duration) {
                self::logout();
                return true; // session expired
            }
        }
        return false; // session still valid
    }
    
    // refresh session timestamp
    public static function refreshSession() {
        if (self::isLoggedIn()) {
            $_SESSION['login_time'] = time();
        }
    }
}
?>