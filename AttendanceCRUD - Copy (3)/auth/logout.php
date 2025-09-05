<?php
require_once __DIR__ . '/session.php';

Session::logout();

// redirect to login page
header('Location: ../index.php?message=logged_out');
exit();
?>