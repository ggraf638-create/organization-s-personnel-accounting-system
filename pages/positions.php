<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/helpers.php';
require_role('admin');

$edit_id = (int)($_GET['id'] ?? 0);
$errors  = [];
$form    = ['id' => 0, 'name' => ''];

if ($edit_id > 0) {
    $st = db()->prepare('SELECT * FROM positions WHERE id = :id');
    $st->execute([':id' => $edit_id]);
    $row = $st->fetch();
    if ($row) {
        $form = array_merge($form, $row);
    } else {
        flash_set('error', 'Должность не найдена.');
        header('Location: /personnel_system/pages/positions.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $form['name'] = trim((string)($_POST['name'] ?? ''));
    if ($form['name'] === '') $errors[] = 'Укажите название должности.';
    if (!$errors) {
        try {
            if ($edit_id > 0) {
                $st = db()->prepare('UPDATE positions SET name = :n WHERE id = :id');
                $st->execute([':n' => $form['name'], ':id' => $edit_id]);
                flash_set('success', 'Должность обновлена.');
            } else {
                $st = db()->prepare('INSERT INTO positions (name) VALUES (:n)');
                $st->execute([':n' => $form['name']]);
                flash_set('success', 'Должность добавлена.');
            }
            header('Location: /personnel_system/pages/positions.php');
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $errors[] = 'Должность с таким названием уже существует.';
            } else {
                $errors[] = 'Ошибка сохранения: ' . $e->getMessage();
            }
        }
    }
}

$positions = db()->query("
    SELECT p.*, (SELECT COUNT(*) FROM employees e WHERE e.position_id = p.id) AS emp_count
    FROM positions p ORDER BY p.name
")->fetchAll();

layout_head('Должности');
layout_nav('admin');
layout_admin_subnav('positions');
?>
<div class="container-xxl">
  <h1 class="page-title h3">Должности</h1>
  <?php flash_render(); ?>

  <div class="row g-3">
    <div class="col-md-7">
      <div class="card">
        <div class="card-body p-0">
          <table class="table mb-0 align-middle">
            <thead><tr><th>Название</th><th class="text-center">Сотрудников</th><th class="text-end">Действия</th></tr></thead>
            <tbody>
              <?php foreach ($positions as $p): ?>
              <tr>
                <td><?= h($p['name']) ?></td>
                <td class="text-center"><?= (int)$p['emp_count'] ?></td>
                <td class="text-end">
                  <a class="btn btn-sm btn-outline-secondary" href="?id=<?= (int)$p['id'] ?>">✎</a>
                  <form action="/personnel_system/api/position_delete.php" method="post" class="d-inline" onsubmit="return confirm('Удалить должность?');">
                    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                    <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger" <?= (int)$p['emp_count'] > 0 ? 'disabled title="Есть сотрудники на этой должности"' : 'title="Удалить"' ?>>🗑</button>
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
          <h5 class="card-title h6 mb-3"><?= $edit_id > 0 ? 'Изменение должности' : 'Новая должность' ?></h5>
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
            <div class="d-flex justify-content-between">
              <?php if ($edit_id > 0): ?>
                <a class="btn btn-link" href="/personnel_system/pages/positions.php">Отмена</a>
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
