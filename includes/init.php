
<?php

require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('SHOP_NAME', 'Plumbing Bill Shop');

if (!defined('SHOP_ADDR')) {
    define('SHOP_ADDR', '');
}

// 1. Check whether the user is logged in
function is_logged_in()
{
    return isset($_SESSION['user']['id']);
}
// Check whether the logged-in user is an administrator
function is_admin()
{
    return isset($_SESSION['user']['role'])
        && strtolower($_SESSION['user']['role']) === 'admin';
}

function require_admin()
{
    if (!is_admin()) {
        http_response_code(403);
        exit('Access denied. Admin only.');
    }
}

// Store or retrieve flash messages
// Store or retrieve flash messages

function flash($type = null, $message = null)
{
    // Store a flash message
    if ($type !== null && $message !== null) {
        $_SESSION['flash'] = [$type, $message];
        return;
    }

    // Retrieve and remove the stored message
    if ($type === null) {
        $storedMessage = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        return $storedMessage;
    }

    return null;
}

// Format a number as Indian currency
function money($amount)
{
    return '₹' . number_format((float)$amount, 2);
}

function paginate($pdo, $select, $from, $params = [], $orderBy = 'id DESC')
{
    $select = str_replace('\\*', '*', $select);

    $countSql = "SELECT COUNT(*) " . $from;
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalRecords = (int) $countStmt->fetchColumn();

    $perPage = 10;
    $pages = max(1, (int) ceil($totalRecords / $perPage));

    $page = max(1, (int) ($_GET['page'] ?? 1));
    $page = min($page, $pages);
    $offset = ($page - 1) * $perPage;

    $sql = "SELECT " . $select . " " . $from
         . " ORDER BY " . $orderBy
         . " LIMIT " . $perPage
         . " OFFSET " . $offset;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return [$rows, $pages, $page];
}





function pager($pages, $page)
{
    if ($pages <= 1) {
        return '';
    }

    $html = '<nav aria-label="Page navigation"><ul class="pagination pagination-sm mb-0">';

    // Previous page
    if ($page > 1) {
        $html .= '<li class="page-item">';
        $html .= '<a class="page-link" href="?page=' . ($page - 1) . '">Previous</a>';
        $html .= '</li>';
    }

    // Page numbers
    for ($i = 1; $i <= $pages; $i++) {
        $active = ($i === (int)$page) ? ' active' : '';

        $html .= '<li class="page-item' . $active . '">';
        $html .= '<a class="page-link" href="?page=' . $i . '">' . $i . '</a>';
        $html .= '</li>';
    }

    // Next page
    if ($page < $pages) {
        $html .= '<li class="page-item">';
        $html .= '<a class="page-link" href="?page=' . ($page + 1) . '">Next</a>';
        $html .= '</li>';
    }

    $html .= '</ul></nav>';

    return $html;
}


// 2. Redirect to the login page if the user is not logged in
function require_login()
{
    if (!is_logged_in()) {
        redirect('auth/login.php');
    }
}

// 3. Redirect the user to another page
function redirect($path)
{
    header('Location: ' . url($path));
    exit;
}

// 4. Generate the page URL
function url($path = '')
{
    return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
}

// 5. Safely display HTML output
function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// 6. Generate a security token for the login form
function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

// 7. Create a hidden security token field for the form
function csrf_field()
{
    return '<input type="hidden" name="csrf_token" value="' .
        e(csrf_token()) . '">';
}

// 8. Validate the security token when the form is submitted
function csrf_check()
{
    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        empty($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        http_response_code(403);
        exit('Invalid security token. Please refresh the login page and try again.');
    }
}

function valid_date($date)
{
    if (!is_string($date) || trim($date) === '') {
        return false;
    }

    $d = DateTime::createFromFormat('!Y-m-d', $date);

    return $d !== false && $d->format('Y-m-d') === $date;
}


function num($value)
{
    if (!is_numeric($value)) {
        return null;
    }

    return (float) $value;
}

