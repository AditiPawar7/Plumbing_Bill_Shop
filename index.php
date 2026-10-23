<?php
require_once __DIR__ . '/includes/init.php';
redirect(is_logged_in() ? 'dashboard.php' : 'auth/login.php');
