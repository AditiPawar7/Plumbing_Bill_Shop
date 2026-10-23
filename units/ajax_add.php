<?php
require_once __DIR__ . '/../includes/init.php';
header('Content-Type: application/json');

if (!is_admin() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Not allowed.']);
    exit;
}
csrf_check();

$name = trim($_POST['name'] ?? '');
if ($name === '' || mb_strlen($name) > 50) {
    echo json_encode(['ok' => false, 'error' => 'Enter a unit name (max 50 characters).']);
    exit;
}
$dup = $pdo->prepare('SELECT id FROM units WHERE is_deleted = 0 AND name = ?');
$dup->execute([$name]);
if ($dup->fetch()) {
    echo json_encode(['ok' => false, 'error' => 'This unit already exists.']);
    exit;
}
$pdo->prepare('INSERT INTO units (name) VALUES (?)')->execute([$name]);
echo json_encode(['ok' => true, 'id' => (int)$pdo->lastInsertId(), 'name' => $name]);
