<?php
require_once __DIR__ . '/../includes/init.php';
if (is_logged_in()) redirect('dashboard.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Enter username and password.';
    } else {
        $st = $pdo->prepare('SELECT id, name, username, password, role FROM users WHERE username = ? AND is_deleted = 0');
        $st->execute([$username]);
        $u = $st->fetch();
        if ($u && password_verify($password, $u['password'])) {
            session_regenerate_id(true);
            $_SESSION['user'] = ['id' => $u['id'], 'name' => $u['name'], 'role' => $u['role']];
            redirect('dashboard.php');
        }
        $error = 'Wrong username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login | <?= e(SHOP_NAME) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="<?= url('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body style="background:linear-gradient(135deg,#6f42c1,#4b2a8f);min-height:100vh" class="d-flex align-items-center">
<div class="container" style="max-width:420px">
  <div class="card p-4">
    <h4 class="text-center mb-1" style="color:#4b2a8f"><?= e(SHOP_NAME) ?></h4>
    <p class="text-center text-muted">Please sign in</p>
    <?php if ($error): ?><div class="alert alert-danger py-2"><?= e($error) ?></div><?php endif; ?>
    <form method="post" autocomplete="off">
      <?= csrf_field() ?>
      <div class="mb-3"><label class="form-label">Username</label>
        <input name="username" class="form-control" required autofocus value="<?= e($_POST['username'] ?? '') ?>"></div>
      <div class="mb-3"><label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" required></div>
      <button class="btn btn-purple w-100">Login</button>
    </form>
    <p class="text-muted small mt-3 mb-0 text-center">Demo: admin / admin123 &nbsp;|&nbsp; worker / worker123</p>
  </div>
</div>
</body>
</html>
