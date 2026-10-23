<?php
require_once __DIR__ . '/../includes/init.php';
require_login();

$q = trim($_GET['q'] ?? '');
$from = 'FROM bills b JOIN customers c ON c.id = b.customer_id WHERE 1=1';
$params = [];
if ($q !== '') { $from .= ' AND (b.bill_no LIKE ? OR c.name LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; }

[$rows, $pages, $page] = paginate(
    $pdo,
    'b.*, c.name AS customer',
    $from,
    $params,
    'b.id DESC'
);


$pageTitle = 'Billing';
include __DIR__ . '/../includes/header.php';
?>
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <form class="d-flex gap-2" method="get">
      <input name="q" value="<?= e($q) ?>" class="form-control form-control-sm" placeholder="Bill no or customer...">
      <button class="btn btn-sm btn-purple">Search</button>
    </form>
    <a href="create.php" class="btn btn-sm btn-purple"><i class="bi bi-plus-lg"></i> New Bill</a>
  </div>
  <div class="table-responsive"><table class="table table-hover align-middle mb-0">
    <thead><tr><th>Bill No</th><th>Date</th><th>Customer</th><th class="text-end">Total (Rs.)</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr><td><?= e($r['bill_no']) ?></td><td><?= e($r['bill_date']) ?></td><td><?= e($r['customer']) ?></td>
        <td class="text-end"><?= money($r['grand_total']) ?></td>
        <td class="text-end"><a class="btn btn-sm btn-outline-purple" href="invoice.php?id=<?= (int)$r['id'] ?>">View / Print</a></td></tr>
    <?php endforeach; if (!$rows): ?><tr><td colspan="5" class="text-center text-muted py-4">No bills found.</td></tr><?php endif; ?>
    </tbody></table></div>
  <div class="card-footer bg-white"><?= pager($pages, $page) ?></div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
