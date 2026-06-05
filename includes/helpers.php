<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_check(): void {
    $sent = (string)($_POST['csrf'] ?? '');
    $stored = (string)($_SESSION['csrf'] ?? '');
    if ($stored === '' || !hash_equals($stored, $sent)) {
        http_response_code(419);
        exit('CSRF token mismatch');
    }
}

function fio_short(array $emp): string {
    $i = mb_substr($emp['first_name'] ?? '', 0, 1);
    $p = mb_substr($emp['patronymic'] ?? '', 0, 1);
    $initials = '';
    if ($i !== '') $initials .= ' ' . $i . '.';
    if ($p !== '') $initials .= $p . '.';
    return ($emp['surname'] ?? '') . $initials;
}

function money_ru(float|int|string $v): string {
    $n = (float)$v;
    return number_format($n, 0, ',', ' ') . ' ₽';
}

function back_url(string $fallback = '/personnel_system/'): string {
    return $_SERVER['HTTP_REFERER'] ?? $fallback;
}
