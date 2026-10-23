<header class="topbar d-flex align-items-center justify-content-between px-3 px-md-4">
  <div class="d-flex align-items-center gap-2">
    <button class="btn btn-sm btn-outline-secondary d-md-none" onclick="document.getElementById('sidebar').classList.toggle('show')"><i class="bi bi-list"></i></button>
    <h5 class="mb-0"><?= e($pageTitle) ?></h5>
  </div>
  <div class="d-flex align-items-center gap-3">
    <span class="text-muted small"><i class="bi bi-person-circle"></i> <?= e($_SESSION['user']['name']) ?>
      <span class="badge bg-purple"><?= e(ucfirst($_SESSION['user']['role'])) ?></span></span>
    <a href="<?= url('auth/logout.php') ?>" class="btn btn-sm btn-outline-purple">Logout</a>
  </div>
</header>
