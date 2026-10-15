<?php
// AJAX endpoint: tells the register form whether an email is still available.
require_once __DIR__ . '/includes/functions.php';
header('Content-Type: application/json');
$email = strtolower(trim($_GET['email'] ?? ''));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { echo json_encode(['available' => false, 'message' => 'Invalid email']); exit; }
$st = db()->prepare('SELECT 1 FROM users WHERE email = ? UNION SELECT 1 FROM students WHERE email = ?');
$st->execute([$email, $email]);
echo json_encode(['available' => !$st->fetch()]);
