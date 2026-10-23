<?php
require_once __DIR__ . '/../includes/init.php';
require_admin();
require_once __DIR__ . '/../includes/crud.php';

render_crud($pdo, [
    'table' => 'categories', 'path' => 'categories/index.php',
    'title' => 'Categories', 'singular' => 'Category',
    'fields' => ['name' => ['label' => 'Category Name', 'required' => true, 'unique' => true, 'max' => 100]],
    'columns' => ['name' => 'Category'],
    'search' => ['t.name'],
    'guard' => 'SELECT (SELECT COUNT(*) FROM products WHERE category_id = ? AND is_deleted = 0)
                     + (SELECT COUNT(*) FROM subcategories WHERE category_id = ? AND is_deleted = 0)',
    'guard_msg' => 'This category still has active products or subcategories. Remove them first.',
]);
