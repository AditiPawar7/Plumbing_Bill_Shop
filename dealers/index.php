<?php
require_once __DIR__ . '/../includes/init.php';
require_admin();
require_once __DIR__ . '/../includes/crud.php';

render_crud($pdo, [
    'table' => 'dealers', 'path' => 'dealers/index.php',
    'title' => 'Dealers', 'singular' => 'Dealer',
    'fields' => [
        'name'    => ['label' => 'Dealer Name', 'required' => true, 'max' => 100],
        'phone'   => ['label' => 'Phone', 'type' => 'phone', 'max' => 20],
        'address' => ['label' => 'Address', 'type' => 'textarea', 'max' => 255],
        'gst_no'  => ['label' => 'GST Number', 'max' => 20],
    ],
    'columns' => ['name' => 'Name', 'phone' => 'Phone', 'gst_no' => 'GST No', 'address' => 'Address'],
    'search' => ['t.name', 't.phone', 't.gst_no'],
    'guard' => 'SELECT COUNT(*) FROM purchases WHERE dealer_id = ?',
    'guard_msg' => 'This dealer has purchase records and cannot be deleted.',
]);
