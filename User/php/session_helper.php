<?php
// Start or resume session with secure configuration
function init_session() {
    // Set secure session parameters
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS'])); // Enable secure cookie in HTTPS
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.gc_maxlifetime', 3600); // 1 hour
    ini_set('session.cookie_lifetime', 3600); // 1 hour

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Regenerate session ID periodically
    if (!isset($_SESSION['last_regeneration']) || time() - $_SESSION['last_regeneration'] >= 300) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

// Check if user is logged in
function is_logged_in() {
    return isset($_SESSION['email']) && isset($_SESSION['user_id']);
}

// Set user session data
function set_user_session($user_data) {
    $_SESSION['user_id'] = $user_data['user_id'];
    $_SESSION['email'] = $user_data['email'];
    $_SESSION['first_name'] = $user_data['first_name'];
    $_SESSION['last_name'] = $user_data['last_name'];
    $_SESSION['middle_name'] = $user_data['middle_name'] ?? null;
    $_SESSION['profile_pic'] = $user_data['profile_pic'] ?? null;
    $_SESSION['last_activity'] = time();
}

// Check session timeout
function check_session_timeout() {
    $timeout = 3600; // 1 hour
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
        session_unset();
        session_destroy();
        return true;
    }
    $_SESSION['last_activity'] = time();
    return false;
}

// Clear session
function clear_session() {
    session_unset();
    session_destroy();
    setcookie(session_name(), '', time() - 3600, '/');
}
?>
