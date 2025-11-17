<?php
// Include this at the top of any page that needs protection
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Optional helper: check role
function require_role($role) {
    if (empty($_SESSION['role']) || $_SESSION['role'] !== $role) {
        header('HTTP/1.1 403 Forbidden');
        echo 'Forbidden';
        exit;
    }
}
