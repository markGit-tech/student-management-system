<?php
require_once __DIR__ . '/includes/functions.php';
redirect(user() ? 'dashboard.php' : 'login.php');
