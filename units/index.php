<?php
require_once __DIR__ . '/../includes/init.php';
require_admin();
require_once __DIR__ . '/../includes/crud.php';

render_crud($pdo, [
    'table' => 'units', 'path' => 'units/index.php',
    'title' => 'Units', 'singular' => 'Unit',
    'fields' => ['name' => ['label' => 'Unit Name', 'required' => true, 'unique' => true, 'max' => 50]],
    'columns' => ['name' => 'Unit'],
    'search' => ['t.name'],
    'guard' => 'SELECT COUNT(*) FROM products WHERE unit_id = ? AND is_deleted = 0',
    'guard_msg' => 'Active products use this unit. Change or remove them first.',
]);
