<?php
// Redirect root to login (or inventory if already authenticated)
session_start();
if (!empty($_SESSION['user_id'])) {
    header('Location: inventory.php');
    exit;
}
header('Location: login.php');
exit;
