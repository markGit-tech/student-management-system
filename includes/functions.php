<?php
/** Core helpers: DB connection, sessions, auth, CSRF, flash messages, validation. */
require_once __DIR__ . '/config.php';

session_set_cookie_params([
    'httponly' => true,                       // JS cannot read session cookie
    'samesite' => 'Lax',
    'secure'   => !empty($_SERVER['HTTPS']),
]);
session_start();

/** Singleton PDO connection (prepared statements only -> SQL injection safe). */
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER, DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                 PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                 PDO::ATTR_EMULATE_PREPARES => false]
            );
        } catch (PDOException $e) {
            http_response_code(500);
            exit('Database connection failed. Check includes/config.php');
        }
    }
    return $pdo;
}

/** Escape output (XSS prevention). */
function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function redirect(string $to): void { header("Location: $to"); exit; }

/* ---------- Flash messages ---------- */
function flash(string $msg, string $type = 'success'): void { $_SESSION['flash'] = ['msg' => $msg, 'type' => $type]; }
function pull_flash(): ?array { $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f; }

/* ---------- CSRF ---------- */
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }
function csrf_check(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(419);
        exit('Invalid or expired form token. Go back and refresh the page.');
    }
}

/* ---------- Auth / RBAC ---------- */
function user(): ?array { return $_SESSION['user'] ?? null; }
function is_admin(): bool { return (user()['role'] ?? '') === 'admin'; }
function require_login(): void { if (!user()) redirect('login.php'); }
function require_admin(): void {
    require_login();
    if (!is_admin()) { flash('Access denied: administrators only.', 'error'); redirect('dashboard.php'); }
}
function login_user(array $row): void {
    session_regenerate_id(true);              // prevent session fixation
    $_SESSION['user'] = ['id' => (int)$row['id'], 'name' => $row['name'], 'email' => $row['email'], 'role' => $row['role']];
}

/* ---------- Validation helpers ---------- */
function clean(string $key): string { return trim((string)($_POST[$key] ?? '')); }
function strong_password(string $p): ?string {
    if (strlen($p) < 8) return 'Password must be at least 8 characters.';
    if (!preg_match('/[A-Za-z]/', $p) || !preg_match('/\d/', $p)) return 'Password needs letters and numbers.';
    return null;
}
function generate_student_no(): string {
    do {
        $no = date('Y') . '-' . str_pad((string)random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        $st = db()->prepare('SELECT 1 FROM students WHERE student_no = ?');
        $st->execute([$no]);
    } while ($st->fetch());
    return $no;
}
function courses_all(): array { return db()->query('SELECT id, code, name FROM courses ORDER BY code')->fetchAll(); }
function field_err(array $errors, string $k): string {
    return isset($errors[$k]) ? '<p class="text-red-600 text-xs mt-1">' . e($errors[$k]) . '</p>' : '';
}
const INPUT_CLASS = 'w-full rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-indigo-500 outline-none bg-white';
