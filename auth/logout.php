<?php
require_once __DIR__ . '/../includes/init.php';
$_SESSION = [];
session_destroy();
header('Location: ' . url('auth/login.php'));
exit;
