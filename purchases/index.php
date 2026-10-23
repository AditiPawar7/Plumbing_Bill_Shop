<?php
require_once __DIR__ . '/../includes/init.php';
require_admin();

$q = trim($_GET['q'] ?? '');
$from = 'FROM purchases pu JOIN dealers d ON d.id = pu.dealer_id JOIN users us ON us.id = pu.created_by WHERE 1=1';
$params = [];
if ($q !== '') { $from .= ' AND d.name LIKE ?'; $params[] = "%$q%"; }

[$rows, $pages, $page] = paginate(
    $pdo,
    'pu.*, d.name AS dealer, us.name AS user_name',
    $from,
    $params,
    'pu.id DESC'
);


$pageTitle = 'Purchases';
include __DIR__ . '/../includes/header.php';
?>
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <form class="d-flex gap-2" method="get">
      <input name="q" value="<?= e($q) ?>" class="form-control form-control-sm" placeholder="Search dealer...">
      <button class="btn btn-sm btn-purple">Search</button>
    </form>
    <a href="add.php" class="btn btn-sm btn-purple"><i class="bi bi-plus-lg"></i> New Purchase</a>
  </div>
  <div class="table-responsive"><table class="table table-hover align-middle mb-0">
    <thead><tr><th>Purchase #</th><th>Date</th><th>Dealer</th><th>Entered by</th><th class="text-end">Total (Rs.)</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr><td>P-<?= (int)$r['id'] ?></td><td><?= e($r['purchase_date']) ?></td><td><?= e($r['dealer']) ?></td>
        <td><?= e($r['user_name']) ?></td><td class="text-end"><?= money($r['total_amount']) ?></td>
        <td class="text-end"><a class="btn btn-sm btn-outline-purple" href="view.php?id=<?= (int)$r['id'] ?>">View</a></td></tr>
    <?php endforeach; if (!$rows): ?><tr><td colspan="6" class="text-center text-muted py-4">No purchases yet.</td></tr><?php endif; ?>
    </tbody></table></div>
  <div class="card-footer bg-white"><?= pager($pages, $page) ?></div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
