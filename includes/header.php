<?php
require_once __DIR__ . '/init.php';
require_login();
$pageTitle = $pageTitle ?? 'Plumbing Bill Shop';
$path = $_SERVER['SCRIPT_NAME'];
$active = fn(string $key) => str_contains($path, $key) ? 'active' : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> | <?= e(SHOP_NAME) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?= url('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . '/sidebar.php'; ?>
<div class="main">
<?php include __DIR__ . '/navbar.php'; ?>
<div class="content p-3 p-md-4">
<?php if ($f = flash()): ?>
  <div class="alert alert-<?= e($f[0]) ?> alert-dismissible fade show"><?= e($f[1]) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
