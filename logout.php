<?php
require_once 'includes/auth.php';
session_destroy();
header('Location: /kost_mutmainah/index.php');
exit;
?>
