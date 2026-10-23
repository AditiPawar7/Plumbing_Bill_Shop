<?php
require_once __DIR__ . '/../includes/init.php';
require_login();

// ----- soft delete (admin only). The row stays in the database, so old bills still work. -----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin();
    csrf_check();
    $pdo->prepare('UPDATE products SET is_deleted = 1 WHERE id = ?')->execute([(int)($_POST['id'] ?? 0)]);
    flash('success', 'Product deleted (hidden). Old bills are not affected.');
    redirect('products/index.php');
}

$q = trim($_GET['q'] ?? '');
$cat = (int)($_GET['category_id'] ?? 0);
$from = 'FROM products p
         JOIN categories c ON c.id = p.category_id
         LEFT JOIN subcategories s ON s.id = p.subcategory_id
         JOIN units u ON u.id = p.unit_id
         WHERE p.is_deleted = 0';
$params = [];
if ($q !== '') { $from .= ' AND p.name LIKE ?'; $params[] = "%$q%"; }
if ($cat > 0)  { $from .= ' AND p.category_id = ?'; $params[] = $cat; }


[$rows, $pages, $page] = paginate(
    $pdo,
    'p.*, c.name AS category, s.name AS subcategory, u.name AS unit',
    $from,
    $params,
    'p.id DESC'
);

$cats = $pdo->query('SELECT id, name FROM categories WHERE is_deleted = 0 ORDER BY name')->fetchAll();
$qty = fn($n) => rtrim(rtrim(number_format((float)$n, 2, '.', ''), '0'), '.');

$pageTitle = 'Products';
include __DIR__ . '/../includes/header.php';
?>
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <form class="d-flex gap-2 flex-wrap" method="get">
      <input name="q" value="<?= e($q) ?>" class="form-control form-control-sm" placeholder="Search product...">
      <select name="category_id" class="form-select form-select-sm">
        <option value="0">All categories</option>
        <?php foreach ($cats as $c): ?><option value="<?= (int)$c['id'] ?>" <?= $cat === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
      </select>
      <button class="btn btn-sm btn-purple">Filter</button>
    </form>
    <?php if (is_admin()): ?><a href="form.php" class="btn btn-sm btn-purple"><i class="bi bi-plus-lg"></i> Add Product</a><?php endif; ?>
  </div>
  <div class="table-responsive"><table class="table table-hover align-middle mb-0">
    <thead><tr><th>#</th><th>Product</th><th>Category</th><th>Unit</th>
      <?php if (is_admin()): ?><th class="text-end">Purchase</th><?php endif; ?>
      <th class="text-end">Selling</th><th class="text-end">Stock</th><th>Status</th>
      <?php if (is_admin()): ?><th class="text-end">Actions</th><?php endif; ?></tr></thead>
    <tbody>
    <?php foreach ($rows as $i => $r): $low = (float)$r['quantity'] <= (float)$r['min_stock']; ?>
      <tr><td><?= $i + 1 + ($page - 1) * 10 ?></td>
        <td><?= e($r['name']) ?></td>
        <td><?= e($r['category']) ?><?= $r['subcategory'] ? ' <small class="text-muted">/ ' . e($r['subcategory']) . '</small>' : '' ?></td>
        <td><?= e($r['unit']) ?></td>
        <?php if (is_admin()): ?><td class="text-end"><?= money($r['purchase_price']) ?></td><?php endif; ?>
        <td class="text-end"><?= money($r['selling_price']) ?></td>
        <td class="text-end"><?= e($qty($r['quantity'])) ?></td>
        <td><?= $low ? '<span class="badge bg-danger">Low stock</span>' : '<span class="badge bg-success">OK</span>' ?></td>
        <?php if (is_admin()): ?>
        <td class="text-end text-nowrap">
          <a class="btn btn-sm btn-outline-purple" href="form.php?id=<?= (int)$r['id'] ?>">Edit</a>
          <form method="post" class="d-inline" data-confirm="Delete this product?">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <button class="btn btn-sm btn-outline-danger">Delete</button></form></td>
        <?php endif; ?></tr>
    <?php endforeach; if (!$rows): ?><tr><td colspan="9" class="text-center text-muted py-4">No products found.</td></tr><?php endif; ?>
    </tbody></table></div>
  <div class="card-footer bg-white"><?= pager($pages, $page) ?></div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
