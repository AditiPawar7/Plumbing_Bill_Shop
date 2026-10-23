<?php
require_once __DIR__ . '/../includes/init.php';
require_admin();
require_once __DIR__ . '/../includes/crud.php';

render_crud($pdo, [
    'table' => 'subcategories', 'path' => 'subcategories/index.php',
    'title' => 'Subcategories', 'singular' => 'Subcategory',
    'from' => 'subcategories t JOIN categories c ON c.id = t.category_id',
    'select' => 't.*, c.name AS category_name',
    'fields' => [
        'category_id' => ['label' => 'Category', 'type' => 'select', 'required' => true,
                          'options_sql' => 'SELECT id, name FROM categories WHERE is_deleted = 0 ORDER BY name'],
        'name' => ['label' => 'Subcategory Name', 'required' => true, 'unique' => true, 'max' => 100, 'scope' => ['category_id']],
    ],
    'columns' => ['category_name' => 'Category', 'name' => 'Subcategory'],
    'search' => ['t.name', 'c.name'],
    'guard' => 'SELECT COUNT(*) FROM products WHERE subcategory_id = ? AND is_deleted = 0',
    'guard_msg' => 'Active products use this subcategory. Change or remove them first.',
]);
