<?php
session_start();
session_destroy();
header("Location: ../login.php"); // go back to root login.php
exit;
?>
