<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/helpers.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
csrf_check();

$id  = (int)($_POST['id'] ?? 0);
$cur = current_user();

if ($id === (int)$cur['id']) {
    flash_set('error', 'Нельзя удалить собственную учётную запись.');
    header('Location: /personnel_system/pages/users.php');
    exit;
}

try {
    $st = db()->prepare('DELETE FROM users WHERE id = :id');
    $st->execute([':id' => $id]);
    flash_set('success', 'Пользователь удалён.');
} catch (PDOException $e) {
    flash_set('error', 'Ошибка удаления: ' . $e->getMessage());
}
header('Location: /personnel_system/pages/users.php');
