<?php
// clear_direct_order.php
session_start();
if(isset($_SESSION['direct_order'])) {
    unset($_SESSION['direct_order']);
}
header("Location: index.php");
exit();
?>