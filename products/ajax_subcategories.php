<?php
// Returns the subcategories of one category as JSON (used by the product form)
require_once __DIR__ . '/../includes/init.php';
require_login();
header('Content-Type: application/json');
$st = $pdo->prepare('SELECT id, name FROM subcategories WHERE category_id = ? AND is_deleted = 0 ORDER BY name');
$st->execute([(int)($_GET['category_id'] ?? 0)]);
echo json_encode($st->fetchAll());
