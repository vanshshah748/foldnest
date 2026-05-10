<?php
// backend/middleware/admin_auth.php
// Admin Authentication Middleware
// Include this file at the top of every admin API endpoint
// It verifies the admin is logged in via PHP sessions

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    // Secure session configuration
    ini_set('session.cookie_httponly', 1);  // Prevent JS access to session cookie
    ini_set('session.use_strict_mode', 1); // Prevent session fixation
    session_start();
}

/**
 * Check if admin is authenticated
 * Returns admin data if authenticated, sends 401 response if not
 */
function requireAdminAuth() {
    // Check if admin session exists
    if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_role'])) {
        http_response_code(401);
        echo json_encode(array(
            "message" => "Unauthorized. Admin login required.",
            "authenticated" => false
        ));
        exit();
    }

    // Return admin data for use in the API
    return array(
        'id' => $_SESSION['admin_id'],
        'username' => $_SESSION['admin_username'],
        'role' => $_SESSION['admin_role'],
        'email' => $_SESSION['admin_email']
    );
}

/**
 * Check if admin has a specific role
 * @param string|array $allowedRoles Single role or array of allowed roles
 */
function requireRole($allowedRoles) {
    $admin = requireAdminAuth();

    if (is_string($allowedRoles)) {
        $allowedRoles = array($allowedRoles);
    }

    if (!in_array($admin['role'], $allowedRoles)) {
        http_response_code(403);
        echo json_encode(array(
            "message" => "Forbidden. Insufficient permissions.",
            "required_role" => $allowedRoles
        ));
        exit();
    }

    return $admin;
}
?>
