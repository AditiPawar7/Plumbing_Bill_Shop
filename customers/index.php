<?php
require_once __DIR__ . '/../includes/init.php';
require_login();   // workers may manage customers too
require_once __DIR__ . '/../includes/crud.php';

render_crud($pdo, [
    'table' => 'customers', 'path' => 'customers/index.php',
    'title' => 'Customers', 'singular' => 'Customer',
    'fields' => [
        'name'    => ['label' => 'Customer Name', 'required' => true, 'max' => 100],
        'phone'   => ['label' => 'Phone', 'type' => 'phone', 'max' => 20],
        'address' => ['label' => 'Address', 'type' => 'textarea', 'max' => 255],
    ],
    'columns' => ['name' => 'Name', 'phone' => 'Phone', 'address' => 'Address'],
    'search' => ['t.name', 't.phone', 't.address'],
]);
