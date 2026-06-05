<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$sent  = (string)($_POST['csrf'] ?? '');
$stored = (string)($_SESSION['csrf'] ?? '');
if ($stored === '' || !hash_equals($stored, $sent)) {
    http_response_code(419);
    echo json_encode(['ok' => false, 'error' => 'CSRF mismatch']);
    exit;
}

$emp  = (int)($_POST['employee_id'] ?? 0);
$date = (string)($_POST['shift_date'] ?? '');
$dept = $_POST['department_id'] ?? '';
$dept = $dept === '' || $dept === null ? null : (int)$dept;

if ($emp <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Bad parameters']);
    exit;
}

try {
    if ($dept === null) {
        $st = db()->prepare('DELETE FROM shifts WHERE employee_id = :e AND shift_date = :d');
        $st->execute([':e' => $emp, ':d' => $date]);
        echo json_encode(['ok' => true, 'employee_id' => $emp, 'shift_date' => $date, 'department_id' => null]);
        exit;
    }

    $st = db()->prepare('
        INSERT INTO shifts (employee_id, shift_date, department_id)
        VALUES (:e, :d, :dep)
        ON DUPLICATE KEY UPDATE department_id = VALUES(department_id)
    ');
    $st->execute([':e' => $emp, ':d' => $date, ':dep' => $dept]);

    $st = db()->prepare('SELECT id, name, color FROM departments WHERE id = :id');
    $st->execute([':id' => $dept]);
    $d = $st->fetch();

    echo json_encode([
        'ok'              => true,
        'employee_id'     => $emp,
        'shift_date'      => $date,
        'department_id'   => $dept,
        'department_name' => $d['name'] ?? '',
        'color'           => $d['color'] ?? '#0d6efd',
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
