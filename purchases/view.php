<?php
require_once __DIR__ . '/../includes/init.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare('SELECT pu.*, d.name AS dealer, d.phone, d.gst_no FROM purchases pu JOIN dealers d ON d.id = pu.dealer_id WHERE pu.id = ?');
$st->execute([$id]);
$pu = $st->fetch();
if (!$pu) { flash('danger', 'Purchase not found.'); redirect('purchases/index.php'); }

$items = $pdo->prepare('SELECT pd.*, p.name, u.name AS unit FROM purchase_details pd
                        JOIN products p ON p.id = pd.product_id JOIN units u ON u.id = p.unit_id WHERE pd.purchase_id = ?');
$items->execute([$id]);
$items = $items->fetchAll();

$pageTitle = 'Purchase P-' . $id;
include __DIR__ . '/../includes/header.php';
?>
<div class="card"><div class="card-body">
  <div class="row mb-3">
    <div class="col-md-6"><strong>Dealer:</strong> <?= e($pu['dealer']) ?><br><strong>Phone:</strong> <?= e($pu['phone']) ?><br><strong>GST No:</strong> <?= e($pu['gst_no']) ?></div>
    <div class="col-md-6"><strong>Purchase #:</strong> P-<?= $id ?><br><strong>Date:</strong> <?= e($pu['purchase_date']) ?></div>
  </div>
  <div class="table-responsive"><table class="table">
    <thead><tr><th>#</th><th>Product</th><th class="text-end">Qty</th><th class="text-end">Price</th><th class="text-end">Amount</th></tr></thead>
    <tbody><?php foreach ($items as $i => $r): ?>
      <tr><td><?= $i + 1 ?></td><td><?= e($r['name']) ?></td><td class="text-end"><?= e(rtrim(rtrim($r['quantity'], '0'), '.')) ?> <?= e($r['unit']) ?></td>
        <td class="text-end"><?= money($r['price']) ?></td><td class="text-end"><?= money($r['subtotal']) ?></td></tr>
    <?php endforeach; ?></tbody>
    <tfoot><tr><th colspan="4" class="text-end">Total</th><th class="text-end">Rs. <?= money($pu['total_amount']) ?></th></tr></tfoot>
  </table></div>
  <a href="index.php" class="btn btn-outline-secondary">Back</a>
</div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
