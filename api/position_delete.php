<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/helpers.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
csrf_check();

$id = (int)($_POST['id'] ?? 0);
try {
    $st = db()->prepare('DELETE FROM positions WHERE id = :id');
    $st->execute([':id' => $id]);
    flash_set('success', 'Должность удалена.');
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        flash_set('error', 'Нельзя удалить: на должность ссылаются сотрудники.');
    } else {
        flash_set('error', 'Ошибка удаления: ' . $e->getMessage());
    }
}
header('Location: /personnel_system/pages/positions.php');
