<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}
csrf_check();

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    flash_set('error', 'Не указан идентификатор сотрудника.');
    header('Location: /personnel_system/pages/employees.php');
    exit;
}

try {
    $st = db()->prepare('DELETE FROM employees WHERE id = :id');
    $st->execute([':id' => $id]);
    if ($st->rowCount() === 0) {
        flash_set('error', 'Сотрудник не найден.');
    } else {
        flash_set('success', 'Сотрудник удалён.');
    }
} catch (Throwable $e) {
    flash_set('error', 'Не удалось удалить сотрудника: ' . $e->getMessage());
}

header('Location: /personnel_system/pages/employees.php');
exit;
