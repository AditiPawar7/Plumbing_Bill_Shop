<?php
require_once __DIR__ . '/../includes/init.php';
require_admin();

$mode = $_GET['mode'] ?? 'daily';
if (!in_array($mode, ['daily', 'monthly', 'range'], true)) $mode = 'daily';
$day = valid_date($_GET['day'] ?? '') ? $_GET['day'] : date('Y-m-d');
$month = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $_GET['month'] ?? '') ? $_GET['month'] : date('Y-m');
$rf = valid_date($_GET['from'] ?? '') ? $_GET['from'] : date('Y-m-01');
$rt = valid_date($_GET['to'] ?? '') ? $_GET['to'] : date('Y-m-d');

if ($mode === 'daily')        { $from = $to = $day; }
elseif ($mode === 'monthly')  { $from = $month . '-01'; $to = date('Y-m-t', strtotime($from)); }
else                          { $from = min($rf, $rt); $to = max($rf, $rt); }

$st = $pdo->prepare('SELECT b.*, c.name AS customer FROM bills b JOIN customers c ON c.id = b.customer_id
                     WHERE b.bill_date BETWEEN ? AND ? ORDER BY b.bill_date, b.id');
$st->execute([$from, $to]);
$bills = $st->fetchAll();

$tot = ['sub' => 0, 'disc' => 0, 'tax' => 0, 'grand' => 0];
$perDay = [];
foreach ($bills as $b) {
    $tot['sub'] += $b['subtotal']; $tot['disc'] += $b['discount']; $tot['tax'] += $b['tax_amount']; $tot['grand'] += $b['grand_total'];
    $perDay[$b['bill_date']]['n'] = ($perDay[$b['bill_date']]['n'] ?? 0) + 1;
    $perDay[$b['bill_date']]['t'] = ($perDay[$b['bill_date']]['t'] ?? 0) + $b['grand_total'];
}

$pageTitle = 'Sales Report';
include __DIR__ . '/../includes/header.php';
?>
<div class="card mb-3 no-print"><div class="card-body">
  <form method="get" class="row g-2 align-items-end">
    <div class="col-md-2"><label class="form-label">Report type</label>
      <select name="mode" id="mode" class="form-select">
        <option value="daily" <?= $mode === 'daily' ? 'selected' : '' ?>>Daily</option>
        <option value="monthly" <?= $mode === 'monthly' ? 'selected' : '' ?>>Monthly</option>
        <option value="range" <?= $mode === 'range' ? 'selected' : '' ?>>Date range</option>
      </select></div>
    <div class="col-md-3 m-daily"><label class="form-label">Date</label><input type="date" name="day" class="form-control" value="<?= e($day) ?>"></div>
    <div class="col-md-3 m-monthly"><label class="form-label">Month</label><input type="month" name="month" class="form-control" value="<?= e($month) ?>"></div>
    <div class="col-md-2 m-range"><label class="form-label">From</label><input type="date" name="from" class="form-control" value="<?= e($rf) ?>"></div>
    <div class="col-md-2 m-range"><label class="form-label">To</label><input type="date" name="to" class="form-control" value="<?= e($rt) ?>"></div>
    <div class="col-auto"><button class="btn btn-purple">Show</button>
      <button type="button" class="btn btn-outline-secondary" onclick="window.print()">Print</button></div>
  </form>
</div></div>

<div class="row g-3 mb-3">
  <?php foreach ([['Bills', count($bills)], ['Sub Total', money($tot['sub'])], ['Discount', money($tot['disc'])], ['Tax', money($tot['tax'])], ['Total Sales (Rs.)', money($tot['grand'])]] as [$l, $v]): ?>
  <div class="col-6 col-lg"><div class="card stat-card"><div class="card-body"><div class="text-muted small"><?= e($l) ?></div><div class="num fs-4"><?= e($v) ?></div></div></div></div>
  <?php endforeach; ?>
</div>
<p class="text-muted">Period: <?= e(date('d-m-Y', strtotime($from))) ?> to <?= e(date('d-m-Y', strtotime($to))) ?></p>

<?php if ($mode !== 'daily' && $perDay): ?>
<div class="card mb-3"><div class="card-header">Day-wise Summary</div>
  <div class="table-responsive"><table class="table mb-0"><thead><tr><th>Date</th><th class="text-end">Bills</th><th class="text-end">Sales (Rs.)</th></tr></thead><tbody>
  <?php foreach ($perDay as $dte => $v): ?><tr><td><?= e(date('d-m-Y', strtotime($dte))) ?></td><td class="text-end"><?= (int)$v['n'] ?></td><td class="text-end"><?= money($v['t']) ?></td></tr><?php endforeach; ?>
  </tbody></table></div></div>
<?php endif; ?>

<div class="card"><div class="card-header">All Bills</div>
  <div class="table-responsive"><table class="table table-hover mb-0">
    <thead><tr><th>Bill No</th><th>Date</th><th>Customer</th><th class="text-end">Sub Total</th><th class="text-end">Discount</th><th class="text-end">Tax</th><th class="text-end">Total</th></tr></thead>
    <tbody>
    <?php foreach ($bills as $b): ?>
      <tr><td><a href="<?= url('billing/invoice.php?id=' . (int)$b['id']) ?>"><?= e($b['bill_no']) ?></a></td><td><?= e($b['bill_date']) ?></td><td><?= e($b['customer']) ?></td>
        <td class="text-end"><?= money($b['subtotal']) ?></td><td class="text-end"><?= money($b['discount']) ?></td><td class="text-end"><?= money($b['tax_amount']) ?></td><td class="text-end"><?= money($b['grand_total']) ?></td></tr>
    <?php endforeach; if (!$bills): ?><tr><td colspan="7" class="text-center text-muted py-4">No sales in this period.</td></tr><?php endif; ?>
    </tbody></table></div></div>
<?php
$extraScript = <<<HTML
<script>
function showMode() {
  var m = document.getElementById('mode').value;
  ['daily','monthly','range'].forEach(function (k) {
    document.querySelectorAll('.m-' + k).forEach(function (el) { el.style.display = (k === m) ? '' : 'none'; });
  });
}
document.getElementById('mode').addEventListener('change', showMode); showMode();
</script>
HTML;
include __DIR__ . '/../includes/footer.php';
