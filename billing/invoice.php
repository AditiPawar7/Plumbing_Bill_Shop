<?php
require_once __DIR__ . '/../includes/init.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare('SELECT b.*, c.name AS customer, c.phone, c.address, u.name AS cashier
                     FROM bills b JOIN customers c ON c.id = b.customer_id JOIN users u ON u.id = b.created_by WHERE b.id = ?');
$st->execute([$id]);
$b = $st->fetch();
if (!$b) { flash('danger', 'Bill not found.'); redirect('billing/index.php'); }

$d = $pdo->prepare('SELECT bd.*, p.name, un.name AS unit FROM bill_details bd
                    JOIN products p ON p.id = bd.product_id JOIN units un ON un.id = p.unit_id WHERE bd.bill_id = ?');
$d->execute([$id]);
$items = $d->fetchAll();
$qty = fn($n) => rtrim(rtrim(number_format((float)$n, 2, '.', ''), '0'), '.');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Invoice <?= e($b['bill_no']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="<?= url('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>
<div class="text-center mt-3 no-print">
  <button onclick="window.print()" class="btn btn-purple">Print Invoice</button>
  <a href="index.php" class="btn btn-outline-secondary">Back to Bills</a>
  <a href="create.php" class="btn btn-outline-purple">New Bill</a>
</div>

<div class="invoice">
  <div class="text-center mb-3">
    <h2 class="mb-0"><?= e(SHOP_NAME) ?></h2>
    <div class="text-muted"><?= e(SHOP_ADDR) ?></div>
    <h5 class="mt-2">TAX INVOICE</h5>
  </div>
  <div class="row mb-3">
    <div class="col-6"><strong>Billed to:</strong><br><?= e($b['customer']) ?><br><?= e($b['phone']) ?><br><?= e($b['address']) ?></div>
    <div class="col-6 text-end"><strong>Bill No:</strong> <?= e($b['bill_no']) ?><br><strong>Date:</strong> <?= e(date('d-m-Y', strtotime($b['bill_date']))) ?><br><strong>Cashier:</strong> <?= e($b['cashier']) ?></div>
  </div>
  <table class="table table-bordered">
    <thead><tr><th>#</th><th>Item</th><th class="text-end">Qty</th><th class="text-end">Rate</th><th class="text-end">Amount</th></tr></thead>
    <tbody>
    <?php foreach ($items as $i => $r): ?>
      <tr><td><?= $i + 1 ?></td><td><?= e($r['name']) ?></td><td class="text-end"><?= e($qty($r['quantity'])) ?> <?= e($r['unit']) ?></td>
        <td class="text-end"><?= money($r['price']) ?></td><td class="text-end"><?= money($r['subtotal']) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr><td colspan="4" class="text-end">Sub Total</td><td class="text-end"><?= money($b['subtotal']) ?></td></tr>
      <tr><td colspan="4" class="text-end">Discount</td><td class="text-end">-<?= money($b['discount']) ?></td></tr>
      <?php if ((float)$b['tax_percent'] > 0): ?>
      <tr><td colspan="4" class="text-end">Tax (<?= e(rtrim(rtrim($b['tax_percent'], '0'), '.')) ?>%)</td><td class="text-end"><?= money($b['tax_amount']) ?></td></tr>
      <?php endif; ?>
      <tr><th colspan="4" class="text-end fs-5">Grand Total</th><th class="text-end fs-5">Rs. <?= money($b['grand_total']) ?></th></tr>
    </tfoot>
  </table>
  <div class="row mt-5">
    <div class="col-6 text-muted small">Goods once sold will not be taken back.<br>Thank you, visit again!</div>
    <div class="col-6 text-end">For <?= e(SHOP_NAME) ?><br><br><br>Authorised Signature</div>
  </div>
</div>
</body>
</html>
