<?php
require_once __DIR__ . '/../includes/init.php';
require_admin();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$isEdit = $id > 0;
$p = ['name' => '', 'category_id' => '', 'subcategory_id' => '', 'unit_id' => '',
      'purchase_price' => '', 'selling_price' => '', 'quantity' => '0', 'min_stock' => '5'];

if ($isEdit) {
    $st = $pdo->prepare('SELECT * FROM products WHERE id = ? AND is_deleted = 0');
    $st->execute([$id]);
    $row = $st->fetch();
    if (!$row) { flash('danger', 'Product not found.'); redirect('products/index.php'); }
    $p = array_merge($p, $row);
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach (['name', 'category_id', 'subcategory_id', 'unit_id', 'purchase_price', 'selling_price', 'min_stock'] as $k) {
        $p[$k] = trim($_POST[$k] ?? '');
    }
    if (!$isEdit) $p['quantity'] = trim($_POST['quantity'] ?? '0');

    if ($p['name'] === '' || mb_strlen($p['name']) > 150) $errors['name'] = 'Product name is required (max 150 characters).';

    $chk = function (string $sql, array $args) use ($pdo) { $s = $pdo->prepare($sql); $s->execute($args); return (int)$s->fetchColumn() > 0; };
    if (!$chk('SELECT COUNT(*) FROM categories WHERE id = ? AND is_deleted = 0', [(int)$p['category_id']])) $errors['category_id'] = 'Choose a category.';
    if ($p['subcategory_id'] !== '' && !$chk('SELECT COUNT(*) FROM subcategories WHERE id = ? AND category_id = ? AND is_deleted = 0',
            [(int)$p['subcategory_id'], (int)$p['category_id']])) $errors['subcategory_id'] = 'Subcategory does not belong to this category.';
    if (!$chk('SELECT COUNT(*) FROM units WHERE id = ? AND is_deleted = 0', [(int)$p['unit_id']])) $errors['unit_id'] = 'Choose a unit.';

    foreach (['purchase_price', 'selling_price', 'min_stock'] as $k) {
        $n = num($p[$k]);
        if ($n === null || $n < 0 || $n > 99999999) $errors[$k] = 'Enter a valid number (0 or more).';
    }
    if (!$isEdit) {
        $n = num($p['quantity']);
        if ($n === null || $n < 0 || $n > 99999999) $errors['quantity'] = 'Enter a valid quantity (0 or more).';
    }
    if (!isset($errors['name']) && $chk('SELECT COUNT(*) FROM products WHERE is_deleted = 0 AND name = ? AND id <> ?', [$p['name'], $id])) {
        $errors['name'] = 'A product with this name already exists.';
    }

    if (!$errors) {
        $sub = $p['subcategory_id'] === '' ? null : (int)$p['subcategory_id'];
        if ($isEdit) {
            $pdo->prepare('UPDATE products SET name=?, category_id=?, subcategory_id=?, unit_id=?, purchase_price=?, selling_price=?, min_stock=? WHERE id=?')
                ->execute([$p['name'], (int)$p['category_id'], $sub, (int)$p['unit_id'], $p['purchase_price'], $p['selling_price'], $p['min_stock'], $id]);
            flash('success', 'Product updated.');
        } else {
            $pdo->prepare('INSERT INTO products (name, category_id, subcategory_id, unit_id, purchase_price, selling_price, quantity, min_stock) VALUES (?,?,?,?,?,?,?,?)')
                ->execute([$p['name'], (int)$p['category_id'], $sub, (int)$p['unit_id'], $p['purchase_price'], $p['selling_price'], $p['quantity'], $p['min_stock']]);
            flash('success', 'Product added.');
        }
        redirect('products/index.php');
    }
}

$cats  = $pdo->query('SELECT id, name FROM categories WHERE is_deleted = 0 ORDER BY name')->fetchAll();
$units = $pdo->query('SELECT id, name FROM units WHERE is_deleted = 0 ORDER BY name')->fetchAll();
$subs  = [];
if ((int)$p['category_id'] > 0) {
    $s = $pdo->prepare('SELECT id, name FROM subcategories WHERE category_id = ? AND is_deleted = 0 ORDER BY name');
    $s->execute([(int)$p['category_id']]);
    $subs = $s->fetchAll();
}
$bad = fn($k) => isset($errors[$k]) ? ' is-invalid' : '';
$err = fn($k) => isset($errors[$k]) ? '<div class="invalid-feedback">' . e($errors[$k]) . '</div>' : '';

$pageTitle = $isEdit ? 'Edit Product' : 'Add Product';
include __DIR__ . '/../includes/header.php';
?>
<div class="card" style="max-width:900px">
  <div class="card-header"><?= e($pageTitle) ?></div>
  <div class="card-body">
    <form method="post" novalidate>
      <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
      <div class="row g-3">
        <div class="col-md-12"><label class="form-label">Product Name *</label>
          <input name="name" class="form-control<?= $bad('name') ?>" value="<?= e($p['name']) ?>"><?= $err('name') ?></div>

        <div class="col-md-6"><label class="form-label">Category *</label>
          <select name="category_id" id="category" class="form-select<?= $bad('category_id') ?>">
            <option value="">-- Select --</option>
            <?php foreach ($cats as $c): ?><option value="<?= (int)$c['id'] ?>" <?= (string)$p['category_id'] === (string)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
          </select><?= $err('category_id') ?></div>

        <div class="col-md-6"><label class="form-label">Subcategory</label>
          <select name="subcategory_id" id="subcategory" class="form-select<?= $bad('subcategory_id') ?>">
            <option value="">-- None --</option>
            <?php foreach ($subs as $s): ?><option value="<?= (int)$s['id'] ?>" <?= (string)$p['subcategory_id'] === (string)$s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option><?php endforeach; ?>
          </select><?= $err('subcategory_id') ?></div>

        <div class="col-md-6"><label class="form-label">Unit *</label>
          <div class="input-group has-validation">
            <select name="unit_id" id="unit" class="form-select<?= $bad('unit_id') ?>">
              <option value="">-- Select --</option>
              <?php foreach ($units as $u): ?><option value="<?= (int)$u['id'] ?>" <?= (string)$p['unit_id'] === (string)$u['id'] ? 'selected' : '' ?>><?= e($u['name']) ?></option><?php endforeach; ?>
            </select>
            <button type="button" class="btn btn-purple" data-bs-toggle="modal" data-bs-target="#unitModal" title="Add new unit"><i class="bi bi-plus-lg"></i></button>
            <?= $err('unit_id') ?>
          </div></div>

        <div class="col-md-6"><label class="form-label">Minimum Stock *</label>
          <input name="min_stock" class="form-control<?= $bad('min_stock') ?>" value="<?= e($p['min_stock']) ?>"><?= $err('min_stock') ?></div>

        <div class="col-md-4"><label class="form-label">Purchase Price *</label>
          <input name="purchase_price" class="form-control<?= $bad('purchase_price') ?>" value="<?= e($p['purchase_price']) ?>"><?= $err('purchase_price') ?></div>
        <div class="col-md-4"><label class="form-label">Selling Price *</label>
          <input name="selling_price" class="form-control<?= $bad('selling_price') ?>" value="<?= e($p['selling_price']) ?>"><?= $err('selling_price') ?></div>
        <div class="col-md-4"><label class="form-label"><?= $isEdit ? 'Current Stock' : 'Opening Quantity *' ?></label>
          <?php if ($isEdit): ?>
            <input class="form-control" value="<?= e($p['quantity']) ?>" disabled>
            <div class="form-text">Stock changes only through Purchases and Bills.</div>
          <?php else: ?>
            <input name="quantity" class="form-control<?= $bad('quantity') ?>" value="<?= e($p['quantity']) ?>"><?= $err('quantity') ?>
          <?php endif; ?></div>
      </div>
      <div class="mt-4">
        <button class="btn btn-purple"><?= $isEdit ? 'Update' : 'Save' ?> Product</button>
        <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
      </div>
    </form>
  </div>
</div>

<!-- Add Unit popup (the + button) -->
<div class="modal fade" id="unitModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title">Add New Unit</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <input id="newUnit" class="form-control" placeholder="e.g. Roll, Set, Litre" maxlength="50">
    <div id="unitError" class="text-danger small mt-2"></div>
  </div>
  <div class="modal-footer"><button class="btn btn-purple" id="saveUnit">Save Unit</button></div>
</div></div></div>
<?php
$ajaxSub = url('products/ajax_subcategories.php');
$ajaxUnit = url('units/ajax_add.php');
$tok = csrf_token();
$extraScript = <<<HTML
<script>
// Category -> Subcategory dependent dropdown
document.getElementById('category').addEventListener('change', function () {
  var sub = document.getElementById('subcategory');
  sub.innerHTML = '<option value="">-- None --</option>';
  if (!this.value) return;
  fetch('$ajaxSub?category_id=' + encodeURIComponent(this.value))
    .then(function (r) { return r.json(); })
    .then(function (list) {
      list.forEach(function (s) {
        var o = document.createElement('option'); o.value = s.id; o.textContent = s.name; sub.appendChild(o);
      });
    });
});
// "+" button: save a new unit with AJAX and select it
document.getElementById('saveUnit').addEventListener('click', function () {
  var name = document.getElementById('newUnit').value.trim();
  var fd = new FormData(); fd.append('name', name); fd.append('csrf', '$tok');
  fetch('$ajaxUnit', { method: 'POST', body: fd })
    .then(function (r) { return r.json(); })
    .then(function (res) {
      if (!res.ok) { document.getElementById('unitError').textContent = res.error; return; }
      var o = document.createElement('option'); o.value = res.id; o.textContent = res.name; o.selected = true;
      document.getElementById('unit').appendChild(o);
      document.getElementById('newUnit').value = ''; document.getElementById('unitError').textContent = '';
      bootstrap.Modal.getInstance(document.getElementById('unitModal')).hide();
    });
});
</script>
HTML;
include __DIR__ . '/../includes/footer.php';
