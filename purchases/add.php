<?php
require_once __DIR__ . '/../includes/init.php';
require_admin();

$dealers = $pdo->query('SELECT id, name FROM dealers WHERE is_deleted = 0 ORDER BY name')->fetchAll();
$products = $pdo->query('SELECT p.id, p.name, u.name AS unit, p.purchase_price AS price
                         FROM products p JOIN units u ON u.id = p.unit_id
                         WHERE p.is_deleted = 0 ORDER BY p.name')->fetchAll();

$error = '';
$dealerId = (int)($_POST['dealer_id'] ?? 0);
$date = $_POST['purchase_date'] ?? date('Y-m-d');
$oldRows = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $pids = $_POST['product_id'] ?? [];
    $qtys = $_POST['quantity'] ?? [];
    $prices = $_POST['price'] ?? [];

    foreach ($pids as $i => $pid) {
        $oldRows[] = ['product_id' => $pid, 'quantity' => $qtys[$i] ?? 1, 'price' => $prices[$i] ?? ''];
    }

    try {
        $d = $pdo->prepare('SELECT COUNT(*) FROM dealers WHERE id = ? AND is_deleted = 0');
        $d->execute([$dealerId]);
        if (!$d->fetchColumn()) throw new RuntimeException('Please choose a dealer.');
        if (!valid_date($date) || $date > date('Y-m-d')) throw new RuntimeException('Enter a valid purchase date (not in the future).');

        // clean and validate every row
        $lines = [];
        foreach ($pids as $i => $pid) {
            if ($pid === '' && ($qtys[$i] ?? '') === '') continue;
            $pid = (int)$pid; $q = num($qtys[$i] ?? ''); $pr = num($prices[$i] ?? '');
            if ($pid <= 0 || $q === null || $q <= 0 || $pr === null || $pr < 0) {
                throw new RuntimeException('Each row needs a product, a quantity above 0 and a valid price.');
            }
            $lines[] = ['pid' => $pid, 'qty' => round($q, 2), 'price' => round($pr, 2)];
        }
        if (!$lines) throw new RuntimeException('Add at least one product.');

        $pdo->beginTransaction();
        $total = 0.0;
        foreach ($lines as &$l) {
            $c = $pdo->prepare('SELECT COUNT(*) FROM products WHERE id = ? AND is_deleted = 0');
            $c->execute([$l['pid']]);
            if (!$c->fetchColumn()) throw new RuntimeException('One of the products no longer exists.');
            $l['sub'] = round($l['qty'] * $l['price'], 2);
            $total += $l['sub'];
        }
        unset($l);

        $pdo->prepare('INSERT INTO purchases (dealer_id, purchase_date, total_amount, created_by) VALUES (?,?,?,?)')
            ->execute([$dealerId, $date, round($total, 2), $_SESSION['user']['id']]);
        $purchaseId = (int)$pdo->lastInsertId();

        $ins = $pdo->prepare('INSERT INTO purchase_details (purchase_id, product_id, quantity, price, subtotal) VALUES (?,?,?,?,?)');
        // stock goes up and the latest purchase price is remembered
        $upd = $pdo->prepare('UPDATE products SET quantity = quantity + ?, purchase_price = ? WHERE id = ?');
        foreach ($lines as $l) {
            $ins->execute([$purchaseId, $l['pid'], $l['qty'], $l['price'], $l['sub']]);
            $upd->execute([$l['qty'], $l['price'], $l['pid']]);
        }
        $pdo->commit();

        flash('success', 'Purchase P-' . $purchaseId . ' saved and stock updated.');
        redirect('purchases/view.php?id=' . $purchaseId);
    } catch (RuntimeException $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = $ex->getMessage();
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = 'Could not save the purchase. Nothing was changed.';
    }
}

$pageTitle = 'New Purchase';
include __DIR__ . '/../includes/header.php';
?>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<form method="post" novalidate>
  <?= csrf_field() ?>
  <div class="card mb-3"><div class="card-header">Purchase Details</div><div class="card-body row g-3">
    <div class="col-md-6"><label class="form-label">Dealer *</label>
      <select name="dealer_id" class="form-select">
        <option value="">-- Select dealer --</option>
        <?php foreach ($dealers as $d): ?><option value="<?= (int)$d['id'] ?>" <?= $dealerId === (int)$d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option><?php endforeach; ?>
      </select></div>
    <div class="col-md-6"><label class="form-label">Purchase Date *</label>
      <input type="date" name="purchase_date" class="form-control" max="<?= date('Y-m-d') ?>" value="<?= e($date) ?>"></div>
  </div></div>

  <div class="card"><div class="card-header d-flex justify-content-between">Products
    <button type="button" id="addRow" class="btn btn-sm btn-purple"><i class="bi bi-plus-lg"></i> Add Row</button></div>
    <div class="table-responsive"><table class="table align-middle mb-0">
      <thead><tr><th>Product</th><th>Quantity</th><th>Purchase Price</th><th class="text-end">Amount</th><th></th></tr></thead>
      <tbody id="lines"></tbody>
      <tfoot><tr><th colspan="3" class="text-end">Total</th><th class="text-end" id="sub">0.00</th><th></th></tr></tfoot>
    </table></div></div>
  <div class="mt-3"><button class="btn btn-purple">Save Purchase</button> <a href="index.php" class="btn btn-outline-secondary">Cancel</a></div>
</form>
<?php
$extraScript = '<script src="' . url('assets/js/lines.js') . '"></script><script>initLines('
    . json_encode(['products' => $products, 'rows' => $oldRows, 'priceReadonly' => false, 'showStock' => false], JSON_HEX_TAG | JSON_HEX_AMP)
    . ');</script>';
include __DIR__ . '/../includes/footer.php';
