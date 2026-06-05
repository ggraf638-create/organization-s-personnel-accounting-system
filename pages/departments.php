<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/helpers.php';
require_role('admin');

$edit_id = (int)($_GET['id'] ?? 0);
$errors  = [];

$form = ['id' => 0, 'name' => '', 'color' => '#0d6efd'];
if ($edit_id > 0) {
    $st = db()->prepare('SELECT * FROM departments WHERE id = :id');
    $st->execute([':id' => $edit_id]);
    $row = $st->fetch();
    if ($row) {
        $form = array_merge($form, $row);
    } else {
        flash_set('error', 'Подразделение не найдено.');
        header('Location: /personnel_system/pages/departments.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $form['name']  = trim((string)($_POST['name']  ?? ''));
    $form['color'] = trim((string)($_POST['color'] ?? '#0d6efd'));
    if ($form['name'] === '') $errors[] = 'Укажите название подразделения.';
    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $form['color'])) $errors[] = 'Цвет должен быть в формате #RRGGBB.';

    if (!$errors) {
        try {
            if ($edit_id > 0) {
                $st = db()->prepare('UPDATE departments SET name = :n, color = :c WHERE id = :id');
                $st->execute([':n' => $form['name'], ':c' => $form['color'], ':id' => $edit_id]);
                flash_set('success', 'Подразделение обновлено.');
            } else {
                $st = db()->prepare('INSERT INTO departments (name, color) VALUES (:n, :c)');
                $st->execute([':n' => $form['name'], ':c' => $form['color']]);
                flash_set('success', 'Подразделение добавлено.');
            }
            header('Location: /personnel_system/pages/departments.php');
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $errors[] = 'Подразделение с таким названием уже существует.';
            } else {
                $errors[] = 'Ошибка сохранения: ' . $e->getMessage();
            }
        }
    }
}

$departments = db()->query("
    SELECT d.*, (SELECT COUNT(*) FROM employees e WHERE e.department_id = d.id) AS emp_count
    FROM departments d ORDER BY d.id
")->fetchAll();

layout_head('Подразделения');
layout_nav('admin');
layout_admin_subnav('departments');
?>
<div class="container-xxl">
  <h1 class="page-title h3">Подразделения</h1>
  <?php flash_render(); ?>

  <div class="row g-3">
    <div class="col-md-7">
      <div class="card">
        <div class="card-body p-0">
          <table class="table mb-0 align-middle">
            <thead>
              <tr><th>Название</th><th>Цвет</th><th class="text-center">Сотрудников</th><th class="text-end">Действия</th></tr>
            </thead>
            <tbody>
              <?php foreach ($departments as $d): ?>
              <tr>
                <td>
                  <span class="badge-dept" style="background-color: <?= h($d['color']) ?>"><?= h($d['name']) ?></span>
                </td>
                <td><code><?= h($d['color']) ?></code></td>
                <td class="text-center"><?= (int)$d['emp_count'] ?></td>
                <td class="text-end">
                  <a class="btn btn-sm btn-outline-secondary" href="?id=<?= (int)$d['id'] ?>">✎</a>
                  <form action="/personnel_system/api/department_delete.php" method="post" class="d-inline" onsubmit="return confirm('Удалить подразделение?');">
                    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                    <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger" <?= (int)$d['emp_count'] > 0 ? 'disabled title="Есть закреплённые сотрудники"' : 'title="Удалить"' ?>>🗑</button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="col-md-5">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title h6 mb-3"><?= $edit_id > 0 ? 'Изменение подразделения' : 'Новое подразделение' ?></h5>
          <?php if ($errors): ?>
            <div class="alert alert-danger">
              <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul>
            </div>
          <?php endif; ?>
          <form method="post">
            <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
            <div class="mb-3">
              <label class="form-label">Название</label>
              <input type="text" name="name" value="<?= h($form['name']) ?>" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Цвет</label>
              <input type="color" name="color" value="<?= h($form['color']) ?>" class="form-control form-control-color">
            </div>
            <div class="d-flex justify-content-between">
              <?php if ($edit_id > 0): ?>
                <a class="btn btn-link" href="/personnel_system/pages/departments.php">Отмена</a>
              <?php else: ?><span></span><?php endif; ?>
              <button type="submit" class="btn btn-primary"><?= $edit_id > 0 ? 'Сохранить' : 'Добавить' ?></button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
<?php layout_foot(); ?>
