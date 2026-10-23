<?php
require_once __DIR__ . '/includes/init.php';
require_login();

$count = fn(string $sql) => (int)$pdo->query($sql)->fetchColumn();
$totalProducts   = $count('SELECT COUNT(*) FROM products WHERE is_deleted = 0');
$totalCategories = $count('SELECT COUNT(*) FROM categories WHERE is_deleted = 0');
$totalCustomers  = $count('SELECT COUNT(*) FROM customers WHERE is_deleted = 0');
$lowStockCount   = $count('SELECT COUNT(*) FROM products WHERE is_deleted = 0 AND quantity <= min_stock');
$todaySales      = (float)$pdo->query('SELECT COALESCE(SUM(grand_total),0) FROM bills WHERE bill_date = CURDATE()')->fetchColumn();
$monthSales      = (float)$pdo->query('SELECT COALESCE(SUM(grand_total),0) FROM bills WHERE YEAR(bill_date)=YEAR(CURDATE()) AND MONTH(bill_date)=MONTH(CURDATE())')->fetchColumn();

$low = $pdo->query('SELECT p.name, p.quantity, p.min_stock, u.name AS unit
                    FROM products p JOIN units u ON u.id = p.unit_id
                    WHERE p.is_deleted = 0 AND p.quantity <= p.min_stock
                    ORDER BY p.quantity ASC LIMIT 8')->fetchAll();
$recent = $pdo->query('SELECT b.id, b.bill_no, b.bill_date, b.grand_total, c.name AS customer
                       FROM bills b JOIN customers c ON c.id = b.customer_id
                       ORDER BY b.id DESC LIMIT 6')->fetchAll();

$pageTitle = 'Dashboard';
include __DIR__ . '/includes/header.php';

$cards = [
  ['Products',        $totalProducts,   'bi-box-seam'],
  ['Categories',      $totalCategories, 'bi-tags'],
  ['Customers',       $totalCustomers,  'bi-people'],
  ['Low Stock Items', $lowStockCount,   'bi-exclamation-triangle'],
  ["Today's Sales",   'Rs. ' . money($todaySales), 'bi-cash-coin'],
  ['This Month Sales','Rs. ' . money($monthSales), 'bi-graph-up-arrow'],
];
?>
<div class="row g-3 mb-4">
<?php foreach ($cards as [$label, $val, $icon]): ?>
  <div class="col-sm-6 col-xl-4">
    <div class="card stat-card"><div class="card-body d-flex justify-content-between align-items-center">
      <div><div class="text-muted"><?= e($label) ?></div><div class="num"><?= e($val) ?></div></div>
      <i class="bi <?= $icon ?>"></i></div></div>
  </div>
<?php endforeach; ?>
</div>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="card"><div class="card-header">Low Stock Products</div>
      <div class="table-responsive"><table class="table mb-0">
        <thead><tr><th>Product</th><th class="text-end">In stock</th><th class="text-end">Minimum</th></tr></thead>
        <tbody>
        <?php foreach ($low as $r): ?>
          <tr><td><?= e($r['name']) ?></td>
              <td class="text-end text-danger fw-bold"><?= e(rtrim(rtrim($r['quantity'], '0'), '.')) ?> <?= e($r['unit']) ?></td>
              <td class="text-end"><?= e(rtrim(rtrim($r['min_stock'], '0'), '.')) ?></td></tr>
        <?php endforeach; if (!$low): ?><tr><td colspan="3" class="text-center text-muted py-3">All products have enough stock.</td></tr><?php endif; ?>
        </tbody></table></div></div>
  </div>
  <div class="col-lg-6">
    <div class="card"><div class="card-header">Recent Bills</div>
      <div class="table-responsive"><table class="table mb-0">
        <thead><tr><th>Bill No</th><th>Customer</th><th>Date</th><th class="text-end">Total</th></tr></thead>
        <tbody>
        <?php foreach ($recent as $r): ?>
          <tr><td><a href="<?= url('billing/invoice.php?id=' . (int)$r['id']) ?>"><?= e($r['bill_no']) ?></a></td>
              <td><?= e($r['customer']) ?></td><td><?= e($r['bill_date']) ?></td>
              <td class="text-end"><?= money($r['grand_total']) ?></td></tr>
        <?php endforeach; if (!$recent): ?><tr><td colspan="4" class="text-center text-muted py-3">No bills yet.</td></tr><?php endif; ?>
        </tbody></table></div></div>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
