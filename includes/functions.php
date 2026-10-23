<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function url(string $p = ''): string { return BASE_URL . '/' . ltrim($p, '/'); }
function redirect(string $p): void { header('Location: ' . url($p)); exit; }
function money($n): string { return number_format((float)$n, 2); }

// ----- authentication / roles -----
function is_logged_in(): bool { return !empty($_SESSION['user']); }
function is_admin(): bool { return ($_SESSION['user']['role'] ?? '') === 'admin'; }
function require_login(): void { if (!is_logged_in()) redirect('auth/login.php'); }
function require_admin(): void {
    require_login();
    if (!is_admin()) { flash('danger', 'Only the admin can open that page.'); redirect('dashboard.php'); }
}

// ----- flash messages -----
function flash(?string $type = null, ?string $msg = null) {
    if ($type !== null) { $_SESSION['flash'] = [$type, $msg]; return null; }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

// ----- CSRF protection for every POST form -----
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . csrf_token() . '">'; }
function csrf_check(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(400);
        die('Invalid or expired form token. Go back and try again.');
    }
}

// ----- pagination -----
// $from must start with "FROM ..." and include its WHERE clause; $params are positional (?).
function paginate(PDO $pdo, string $select, string $from, array $params, string $order, int $per = 10): array {
    $st = $pdo->prepare("SELECT COUNT(*) $from");
    $st->execute($params);
    $total = (int)$st->fetchColumn();
    $pages = max(1, (int)ceil($total / $per));
    $page  = min(max(1, (int)($_GET['page'] ?? 1)), $pages);
    $off   = ($page - 1) * $per;
    $st = $pdo->prepare("$select $from ORDER BY $order LIMIT $per OFFSET $off");
    $st->execute($params);
    return [$st->fetchAll(), $pages, $page, $total];
}

function pager(int $pages, int $page): string {
    if ($pages <= 1) return '';
    $q = $_GET; $html = '<nav><ul class="pagination pagination-sm mb-0">';
    for ($i = 1; $i <= $pages; $i++) {
        $q['page'] = $i;
        $html .= '<li class="page-item' . ($i === $page ? ' active' : '') . '"><a class="page-link" href="?'
              . e(http_build_query($q)) . '">' . $i . '</a></li>';
    }
    return $html . '</ul></nav>';
}

// Clean a number from a form: returns float or null when invalid.
function num($v): ?float { return (is_numeric($v) && is_finite((float)$v)) ? (float)$v : null; }

// True only for a real date written as YYYY-MM-DD
function valid_date(string $d): bool {
    $x = DateTime::createFromFormat('Y-m-d', $d);
    return $x && $x->format('Y-m-d') === $d;
}
