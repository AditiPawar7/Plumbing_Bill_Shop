<?php
require_once __DIR__ . '/../includes/init.php';
require_login();

$customers = $pdo->query('SELECT id, name, phone FROM customers WHERE is_deleted = 0 ORDER BY name')->fetchAll();
$products = $pdo->query('SELECT p.id, p.name, u.name AS unit, p.selling_price AS price, p.quantity AS stock
                         FROM products p JOIN units u ON u.id = p.unit_id
                         WHERE p.is_deleted = 0 ORDER BY p.name')->fetchAll();

$error = '';
$customerId = (int)($_POST['customer_id'] ?? 0);
$date = $_POST['bill_date'] ?? date('Y-m-d');
$discountIn = $_POST['discount'] ?? '0';
$taxIn = $_POST['tax_percent'] ?? '0';
$oldRows = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $pids = is_array($_POST['product_id'] ?? null) ? $_POST['product_id'] : [];
    $qtys = is_array($_POST['quantity'] ?? null) ? $_POST['quantity'] : [];
    foreach ($pids as $i => $pid) $oldRows[] = ['product_id' => $pid, 'quantity' => $qtys[$i] ?? 1];

    try {
        $c = $pdo->prepare('SELECT COUNT(*) FROM customers WHERE id = ? AND is_deleted = 0');
        $c->execute([$customerId]);
        if (!$c->fetchColumn()) throw new RuntimeException('Please choose a customer.');
        if (!valid_date($date) || $date > date('Y-m-d')) throw new RuntimeException('Enter a valid bill date (not in the future).');

        $discount = num($discountIn); $taxPct = num($taxIn);
        if ($discount === null || $discount < 0) throw new RuntimeException('Discount must be 0 or more.');
        if ($taxPct === null || $taxPct < 0 || $taxPct > 100) throw new RuntimeException('Tax % must be between 0 and 100.');

        // merge duplicate products into one line
        $need = [];
        foreach ($pids as $i => $pid) {
            if ($pid === '' && ($qtys[$i] ?? '') === '') continue;
            $pid = (int)$pid; $q = num($qtys[$i] ?? '');
            if ($pid <= 0 || $q === null || $q <= 0) throw new RuntimeException('Each row needs a product and a quantity above 0.');
            $need[$pid] = round(($need[$pid] ?? 0) + $q, 2);
        }
        if (!$need) throw new RuntimeException('Add at least one product.');
        ksort($need);   // same lock order every time avoids deadlocks

        $pdo->beginTransaction();
        $lines = [];
        $sub = 0.0;
        $lock = $pdo->prepare('SELECT id, name, selling_price, quantity FROM products WHERE id = ? AND is_deleted = 0 FOR UPDATE');
        foreach ($need as $pid => $qty) {
            $lock->execute([$pid]);
            $p = $lock->fetch();
            if (!$p) throw new RuntimeException('One of the products no longer exists.');
            if ($qty > round((float)$p['quantity'], 2)) {
                throw new RuntimeException('Not enough stock for "' . $p['name'] . '". Available: ' . round((float)$p['quantity'], 2) . ', requested: ' . $qty . '.');
            }
            $price = (float)$p['selling_price'];           // price always comes from the database
            $line = round($price * $qty, 2);
            $lines[] = ['pid' => $pid, 'qty' => $qty, 'price' => $price, 'sub' => $line];
            $sub += $line;
        }
        $sub      = round($sub, 2);
        $discount = round(min($discount, $sub), 2);       // discount can never exceed the subtotal
        $taxable  = $sub - $discount;
        $tax      = round($taxable * $taxPct / 100, 2);
        $grand    = round($taxable + $tax, 2);

        $pdo->prepare('INSERT INTO bills (bill_no, customer_id, bill_date, subtotal, discount, tax_percent, tax_amount, grand_total, created_by)
                       VALUES (?,?,?,?,?,?,?,?,?)')
            ->execute(['TMP' . bin2hex(random_bytes(6)), $customerId, $date, $sub, $discount, $taxPct, $tax, $grand, $_SESSION['user']['id']]);
        $billId = (int)$pdo->lastInsertId();
        $billNo = sprintf('INV-%s-%04d', date('Y'), $billId);
        $pdo->prepare('UPDATE bills SET bill_no = ? WHERE id = ?')->execute([$billNo, $billId]);

        $ins = $pdo->prepare('INSERT INTO bill_details (bill_id, product_id, quantity, price, subtotal) VALUES (?,?,?,?,?)');
        $upd = $pdo->prepare('UPDATE products SET quantity = quantity - ? WHERE id = ?');
        foreach ($lines as $l) {
            $ins->execute([$billId, $l['pid'], $l['qty'], $l['price'], $l['sub']]);
            $upd->execute([$l['qty'], $l['pid']]);
        }
        $pdo->commit();

        flash('success', 'Bill ' . $billNo . ' created.');
        redirect('billing/invoice.php?id=' . $billId);

    } catch (RuntimeException $ex) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = $ex->getMessage();

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log('Bill Save Error: ' . $e->getMessage());
        $error = 'Bill save error: ' . $e->getMessage();
    }
}


$pageTitle = 'New Bill';
include __DIR__ . '/../includes/header.php';
?>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<form method="post" novalidate>
  <?= csrf_field() ?>
  <div class="card mb-3"><div class="card-header">Customer</div><div class="card-body row g-3">
    <div class="col-md-6"><label class="form-label">Customer *</label>
      <select name="customer_id" class="form-select">
        <option value="">-- Select customer --</option>
        <?php foreach ($customers as $c): ?><option value="<?= (int)$c['id'] ?>" <?= $customerId === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?><?= $c['phone'] ? ' - ' . e($c['phone']) : '' ?></option><?php endforeach; ?>
      </select>
      <div class="form-text">New customer? <a href="<?= url('customers/index.php') ?>">Add here</a> first.</div></div>
    <div class="col-md-6"><label class="form-label">Bill Date *</label>
      <input type="date" name="bill_date" class="form-control" max="<?= date('Y-m-d') ?>" value="<?= e($date) ?>"></div>
  </div></div>

  <div class="card"><div class="card-header d-flex justify-content-between">Products
    <button type="button" id="addRow" class="btn btn-sm btn-purple"><i class="bi bi-plus-lg"></i> Add Row</button></div>
    <div class="table-responsive"><table class="table align-middle mb-0">
      <thead><tr><th>Product</th><th>Quantity</th><th>Rate</th><th class="text-end">Amount</th><th></th></tr></thead>
      <tbody id="lines"></tbody>
    </table></div>
    <div class="card-body border-top"><div class="row justify-content-end"><div class="col-md-5 col-lg-4">
      <div class="d-flex justify-content-between mb-2"><span>Sub Total</span><strong id="sub">0.00</strong></div>
      <div class="d-flex justify-content-between align-items-center mb-2"><span>Discount (Rs.)</span>
        <input type="number" step="0.01" min="0" name="discount" id="discount" class="form-control form-control-sm w-50" value="<?= e($discountIn) ?>"></div>
      <div class="d-flex justify-content-between align-items-center mb-2"><span>Tax % (if any)</span>
        <input type="number" step="0.01" min="0" max="100" name="tax_percent" id="tax_percent" class="form-control form-control-sm w-50" value="<?= e($taxIn) ?>"></div>
      <div class="d-flex justify-content-between text-muted"><span>Discount</span><span id="disc">-0.00</span></div>
      <div class="d-flex justify-content-between text-muted"><span>Tax</span><span id="tax">0.00</span></div>
      <hr class="my-2">
      <div class="d-flex justify-content-between fs-5"><strong>Grand Total</strong><strong class="text-success" id="grand">0.00</strong></div>
      <div class="form-text">The final amounts are recalculated on the server when you save.</div>
    </div></div></div>
  </div>
  <div class="mt-3"><button class="btn btn-purple">Generate Bill</button> <a href="index.php" class="btn btn-outline-secondary">Cancel</a></div>
</form>
<?php
$extraScript = '<script src="' . url('assets/js/lines.js') . '"></script><script>initLines('
    . json_encode(['products' => $products, 'rows' => $oldRows, 'priceReadonly' => true, 'showStock' => true], JSON_HEX_TAG | JSON_HEX_AMP)
    . ');</script>';
include __DIR__ . '/../includes/footer.php';
