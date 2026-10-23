<aside class="sidebar" id="sidebar">
  <div class="brand"><i class="bi bi-wrench-adjustable-circle"></i> <?= e(SHOP_NAME) ?></div>
  <nav class="nav flex-column">
    <a class="nav-link <?= $active('dashboard') ?>" href="<?= url('dashboard.php') ?>"><i class="bi bi-speedometer2"></i> Dashboard</a>
    <a class="nav-link <?= $active('/billing/') ?>" href="<?= url('billing/index.php') ?>"><i class="bi bi-receipt"></i> Billing</a>
    <a class="nav-link <?= $active('/customers/') ?>" href="<?= url('customers/index.php') ?>"><i class="bi bi-people"></i> Customers</a>
    <a class="nav-link <?= $active('/products/') ?>" href="<?= url('products/index.php') ?>"><i class="bi bi-box-seam"></i> Products</a>
    <?php if (is_admin()): ?>
    <div class="nav-title">Masters</div>
    <a class="nav-link <?= $active('/categories/') ?>" href="<?= url('categories/index.php') ?>"><i class="bi bi-tags"></i> Categories</a>
    <a class="nav-link <?= $active('/subcategories/') ?>" href="<?= url('subcategories/index.php') ?>"><i class="bi bi-diagram-2"></i> Subcategories</a>
    <a class="nav-link <?= $active('/units/') ?>" href="<?= url('units/index.php') ?>"><i class="bi bi-rulers"></i> Units</a>
    <a class="nav-link <?= $active('/dealers/') ?>" href="<?= url('dealers/index.php') ?>"><i class="bi bi-truck"></i> Dealers</a>
    <a class="nav-link <?= $active('/purchases/') ?>" href="<?= url('purchases/index.php') ?>"><i class="bi bi-cart-plus"></i> Purchases</a>
    <?php endif; ?>
    <div class="nav-title">Reports</div>
    <?php if (is_admin()): ?>
    <a class="nav-link <?= $active('reports/sales') ?>" href="<?= url('reports/sales.php') ?>"><i class="bi bi-graph-up"></i> Sales Report</a>
    <?php endif; ?>
    <a class="nav-link <?= $active('reports/stock') ?>" href="<?= url('reports/stock.php') ?>"><i class="bi bi-clipboard-data"></i> Stock Report</a>
  </nav>
</aside>
