<?php
// Admin authentication check
function checkAdminAccess() {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        redirect('admin_login.php');
    }
}
?>