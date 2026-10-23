<?php
require_once __DIR__ . '/../includes/init.php';
require_login();

$onlyLow = ($_GET['view'] ?? 'all') === 'low';
$sql = 'SELECT p.name, p.quantity, p.min_stock, p.purchase_price, c.name AS category, u.name AS unit
        FROM products p JOIN categories c ON c.id = p.category_id JOIN units u ON u.id = p.unit_id
        WHERE p.is_deleted = 0' . ($onlyLow ? ' AND p.quantity <= p.min_stock' : '') . ' ORDER BY p.name';
$rows = $pdo->query($sql)->fetchAll();
$qty = fn($n) => rtrim(rtrim(number_format((float)$n, 2, '.', ''), '0'), '.');
$value = 0;
foreach ($rows as $r) $value += $r['quantity'] * $r['purchase_price'];

$pageTitle = 'Stock Report';
include __DIR__ . '/../includes/header.php';
?>
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 no-print">
    <div class="btn-group btn-group-sm">
      <a href="?view=all" class="btn <?= $onlyLow ? 'btn-outline-purple' : 'btn-purple' ?>">All stock</a>
      <a href="?view=low" class="btn <?= $onlyLow ? 'btn-purple' : 'btn-outline-purple' ?>">Low stock only</a>
    </div>
    <button class="btn btn-sm btn-outline-secondary" onclick="window.print()">Print</button>
  </div>
  <div class="table-responsive"><table class="table table-hover align-middle mb-0">
    <thead><tr><th>#</th><th>Product</th><th>Category</th><th class="text-end">Available</th><th class="text-end">Minimum</th><th>Status</th>
      <?php if (is_admin()): ?><th class="text-end">Stock value (cost)</th><?php endif; ?></tr></thead>
    <tbody>
    <?php foreach ($rows as $i => $r): $low = (float)$r['quantity'] <= (float)$r['min_stock']; ?>
      <tr class="<?= $low ? 'table-danger' : '' ?>"><td><?= $i + 1 ?></td><td><?= e($r['name']) ?></td><td><?= e($r['category']) ?></td>
        <td class="text-end"><?= e($qty($r['quantity'])) ?> <?= e($r['unit']) ?></td><td class="text-end"><?= e($qty($r['min_stock'])) ?></td>
        <td><?= $low ? '<span class="badge bg-danger">Low stock</span>' : '<span class="badge bg-success">OK</span>' ?></td>
        <?php if (is_admin()): ?><td class="text-end"><?= money($r['quantity'] * $r['purchase_price']) ?></td><?php endif; ?></tr>
    <?php endforeach; if (!$rows): ?><tr><td colspan="7" class="text-center text-muted py-4">Nothing to show.</td></tr><?php endif; ?>
    </tbody>
    <?php if (is_admin() && $rows): ?><tfoot><tr><th colspan="6" class="text-end">Total stock value</th><th class="text-end">Rs. <?= money($value) ?></th></tr></tfoot><?php endif; ?>
  </table></div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
