<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function login_user(string $login, string $password): bool {
    $st = db()->prepare('SELECT id, login, password_hash, role, full_name FROM users WHERE login = :l LIMIT 1');
    $st->execute([':l' => $login]);
    $u = $st->fetch();
    if (!$u || !password_verify($password, $u['password_hash'])) {
        return false;
    }
    $_SESSION['user'] = [
        'id'        => (int)$u['id'],
        'login'     => $u['login'],
        'role'      => $u['role'],
        'full_name' => $u['full_name'],
    ];
    return true;
}

function logout_user(): void {
    $_SESSION = [];
    session_destroy();
}

function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

function require_login(): void {
    if (!current_user()) {
        header('Location: /personnel_system/login.php');
        exit;
    }
}

function require_role(string ...$roles): void {
    require_login();
    if (!in_array(current_user()['role'], $roles, true)) {
        http_response_code(403);
        echo '<h1>403</h1><p>Недостаточно прав.</p>';
        exit;
    }
}
