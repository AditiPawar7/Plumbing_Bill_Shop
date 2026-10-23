<?php
/**
 * Generic CRUD page used by Categories, Subcategories, Units, Customers and Dealers.
 * One function = add + edit + soft delete + search + pagination, so the code is written once.
 *
 * $c keys: table, path, title, singular, fields, columns, search, guard, guard_msg,
 *          from (optional JOIN), select (optional)
 * Every query uses alias "t" for the main table.
 */
function render_crud(PDO $pdo, array $c): void
{
    $t = $c['table'];
    $fields = $c['fields'];
    $errors = [];
    $old = [];
    $editRow = null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $action = $_POST['action'] ?? 'save';
        $id = (int)($_POST['id'] ?? 0);

        // ----- soft delete -----
        if ($action === 'delete') {
            $blocked = false;
            if (!empty($c['guard'])) {
                $g = $pdo->prepare($c['guard']);
                $g->execute(array_fill(0, substr_count($c['guard'], '?'), $id));
                if ((int)$g->fetchColumn() > 0) { flash('danger', $c['guard_msg']); $blocked = true; }
            }
            if (!$blocked) {
                $pdo->prepare("UPDATE $t SET is_deleted = 1 WHERE id = ?")->execute([$id]);
                flash('success', $c['singular'] . ' deleted.');
            }
            redirect($c['path']);
        }

        // ----- validate -----
        $vals = [];
        foreach ($fields as $f => $d) {
            $v = trim($_POST[$f] ?? '');
            $vals[$f] = $v;
            $type = $d['type'] ?? 'text';
            if (!empty($d['required']) && $v === '') { $errors[$f] = $d['label'] . ' is required.'; continue; }
            if ($v !== '' && !empty($d['max']) && mb_strlen($v) > $d['max']) { $errors[$f] = $d['label'] . ' is too long.'; continue; }
            if ($type === 'phone' && $v !== '' && !preg_match('/^[0-9+\-\s]{7,15}$/', $v)) { $errors[$f] = 'Enter a valid phone number.'; continue; }
            if ($type === 'select') {
                $ok = $pdo->prepare('SELECT COUNT(*) FROM (' . $d['options_sql'] . ') o WHERE o.id = ?');
                $ok->execute([(int)$v]);
                if (!(int)$ok->fetchColumn()) $errors[$f] = 'Please choose a valid ' . strtolower($d['label']) . '.';
            }
        }
        // duplicate check (only among active rows)
        foreach ($fields as $f => $d) {
            if (empty($d['unique']) || isset($errors[$f]) || $vals[$f] === '') continue;
            $sql = "SELECT COUNT(*) FROM $t WHERE is_deleted = 0 AND $f = ? AND id <> ?";
            $p = [$vals[$f], $id];
            foreach (($d['scope'] ?? []) as $s) { $sql .= " AND $s = ?"; $p[] = $vals[$s]; }
            $dup = $pdo->prepare($sql);
            $dup->execute($p);
            if ((int)$dup->fetchColumn() > 0) $errors[$f] = $d['label'] . ' already exists.';
        }

        // ----- save -----
        if (!$errors) {
            $cols = array_keys($fields);
            $params = [];
            foreach ($cols as $col) {
                $v = $vals[$col];
                $params[] = ($v === '' && ($fields[$col]['type'] ?? 'text') !== 'select') ? null : $v;
            }
            if ($id > 0) {
                $set = implode(', ', array_map(fn($x) => "$x = ?", $cols));
                $params[] = $id;
                $pdo->prepare("UPDATE $t SET $set WHERE id = ? AND is_deleted = 0")->execute($params);
                flash('success', $c['singular'] . ' updated.');
            } else {
                $ph = implode(', ', array_fill(0, count($cols), '?'));
                $pdo->prepare("INSERT INTO $t (" . implode(', ', $cols) . ") VALUES ($ph)")->execute($params);
                flash('success', $c['singular'] . ' added.');
            }
            redirect($c['path']);
        }
        $old = $vals;
        if ($id > 0) $editRow = ['id' => $id];
    }

    if (!$editRow && isset($_GET['edit'])) {
        $s = $pdo->prepare("SELECT * FROM $t WHERE id = ? AND is_deleted = 0");
        $s->execute([(int)$_GET['edit']]);
        $editRow = $s->fetch() ?: null;
    }

    // ----- list -----
    $q = trim($_GET['q'] ?? '');
    $from = 'FROM ' . ($c['from'] ?? "$t t") . ' WHERE t.is_deleted = 0';
    $params = [];
    if ($q !== '') {
        $like = [];
        foreach ($c['search'] as $col) { $like[] = "$col LIKE ?"; $params[] = "%$q%"; }
        $from .= ' AND (' . implode(' OR ', $like) . ')';
    }
    [$rows, $pages, $page] = paginate($pdo, $c['select'] ?? 't.*', $from, $params, 't.id DESC');

    $pageTitle = $c['title'];
    include __DIR__ . '/header.php';
    $isEdit = !empty($editRow['id']);
    ?>
    <div class="row g-3">
      <div class="col-lg-4">
        <div class="card"><div class="card-header"><?= $isEdit ? 'Edit' : 'Add' ?> <?= e($c['singular']) ?></div>
          <div class="card-body">
            <form method="post" novalidate>
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= $isEdit ? (int)$editRow['id'] : 0 ?>">
              <?php foreach ($fields as $f => $d):
                  $type = $d['type'] ?? 'text';
                  $val = $old[$f] ?? ($editRow[$f] ?? '');
                  $bad = isset($errors[$f]) ? ' is-invalid' : ''; ?>
                <div class="mb-3">
                  <label class="form-label"><?= e($d['label']) ?><?= !empty($d['required']) ? ' *' : '' ?></label>
                  <?php if ($type === 'select'): ?>
                    <select name="<?= $f ?>" class="form-select<?= $bad ?>">
                      <option value="">-- Select --</option>
                      <?php foreach ($pdo->query($d['options_sql'])->fetchAll() as $o): ?>
                        <option value="<?= (int)$o['id'] ?>" <?= (string)$val === (string)$o['id'] ? 'selected' : '' ?>><?= e($o['name']) ?></option>
                      <?php endforeach; ?>
                    </select>
                  <?php elseif ($type === 'textarea'): ?>
                    <textarea name="<?= $f ?>" rows="2" class="form-control<?= $bad ?>"><?= e($val) ?></textarea>
                  <?php else: ?>
                    <input name="<?= $f ?>" class="form-control<?= $bad ?>" value="<?= e($val) ?>">
                  <?php endif; ?>
                  <?php if (isset($errors[$f])): ?><div class="invalid-feedback"><?= e($errors[$f]) ?></div><?php endif; ?>
                </div>
              <?php endforeach; ?>
              <button class="btn btn-purple"><?= $isEdit ? 'Update' : 'Save' ?></button>
              <?php if ($isEdit): ?><a href="<?= url($c['path']) ?>" class="btn btn-outline-secondary">Cancel</a><?php endif; ?>
            </form>
          </div></div>
      </div>

      <div class="col-lg-8">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span><?= e($c['title']) ?></span>
            <form class="d-flex gap-2" method="get">
              <input name="q" value="<?= e($q) ?>" class="form-control form-control-sm" placeholder="Search...">
              <button class="btn btn-sm btn-purple">Search</button>
            </form>
          </div>
          <div class="table-responsive"><table class="table table-hover align-middle mb-0">
            <thead><tr><th>#</th>
              <?php foreach ($c['columns'] as $label): ?><th><?= e($label) ?></th><?php endforeach; ?>
              <th class="text-end">Actions</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $i => $r): ?>
              <tr><td><?= $i + 1 + ($page - 1) * 10 ?></td>
                <?php foreach ($c['columns'] as $col => $label): ?><td><?= e($r[$col] ?? '') ?></td><?php endforeach; ?>
                <td class="text-end text-nowrap">
                  <a class="btn btn-sm btn-outline-purple" href="?edit=<?= (int)$r['id'] ?>">Edit</a>
                  <form method="post" class="d-inline" data-confirm="Delete this <?= e(strtolower($c['singular'])) ?>?">
                    <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <button class="btn btn-sm btn-outline-danger">Delete</button></form>
                </td></tr>
            <?php endforeach; if (!$rows): ?>
              <tr><td colspan="<?= count($c['columns']) + 2 ?>" class="text-center text-muted py-4">No records found.</td></tr>
            <?php endif; ?>
            </tbody></table></div>
          <div class="card-footer bg-white"><?= pager($pages, $page) ?></div>
        </div>
      </div>
    </div>
    <?php
    include __DIR__ . '/footer.php';
}
