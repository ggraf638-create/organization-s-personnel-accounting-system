<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/helpers.php';
require_role('admin');

$edit_id = (int)($_GET['id'] ?? 0);
$errors  = [];
$form    = ['id' => 0, 'login' => '', 'full_name' => '', 'role' => 'hr'];

if ($edit_id > 0) {
    $st = db()->prepare('SELECT id, login, full_name, role FROM users WHERE id = :id');
    $st->execute([':id' => $edit_id]);
    $row = $st->fetch();
    if ($row) {
        $form = array_merge($form, $row);
    } else {
        flash_set('error', 'Пользователь не найден.');
        header('Location: /personnel_system/pages/users.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $form['login']     = trim((string)($_POST['login'] ?? ''));
    $form['full_name'] = trim((string)($_POST['full_name'] ?? ''));
    $form['role']      = (string)($_POST['role'] ?? 'hr');
    $pass              = (string)($_POST['password'] ?? '');

    if ($form['login'] === '')                                                   $errors[] = 'Укажите логин.';
    if ($form['full_name'] === '')                                               $errors[] = 'Укажите ФИО.';
    if (!in_array($form['role'], ['admin','hr'], true))                          $errors[] = 'Некорректная роль.';
    if ($edit_id === 0 && $pass === '')                                          $errors[] = 'При создании пользователя укажите пароль.';
    if ($pass !== '' && mb_strlen($pass) < 4)                                    $errors[] = 'Пароль должен быть не короче 4 символов.';

    if (!$errors) {
        try {
            if ($edit_id > 0) {
                if ($pass !== '') {
                    $st = db()->prepare('UPDATE users SET login=:l, full_name=:f, role=:r, password_hash=:p WHERE id=:id');
                    $st->execute([':l' => $form['login'], ':f' => $form['full_name'], ':r' => $form['role'], ':p' => password_hash($pass, PASSWORD_DEFAULT), ':id' => $edit_id]);
                } else {
                    $st = db()->prepare('UPDATE users SET login=:l, full_name=:f, role=:r WHERE id=:id');
                    $st->execute([':l' => $form['login'], ':f' => $form['full_name'], ':r' => $form['role'], ':id' => $edit_id]);
                }
                flash_set('success', 'Пользователь обновлён.');
            } else {
                $st = db()->prepare('INSERT INTO users (login, full_name, role, password_hash) VALUES (:l, :f, :r, :p)');
                $st->execute([':l' => $form['login'], ':f' => $form['full_name'], ':r' => $form['role'], ':p' => password_hash($pass, PASSWORD_DEFAULT)]);
                flash_set('success', 'Пользователь добавлен.');
            }
            header('Location: /personnel_system/pages/users.php');
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $errors[] = 'Пользователь с таким логином уже существует.';
            } else {
                $errors[] = 'Ошибка сохранения: ' . $e->getMessage();
            }
        }
    }
}

$users = db()->query('SELECT id, login, full_name, role, created_at FROM users ORDER BY id')->fetchAll();
$cur   = current_user();

layout_head('Пользователи');
layout_nav('admin');
layout_admin_subnav('users');
?>
<div class="container-xxl">
  <h1 class="page-title h3">Пользователи системы</h1>
  <?php flash_render(); ?>

  <div class="row g-3">
    <div class="col-md-7">
      <div class="card">
        <div class="card-body p-0">
          <table class="table mb-0 align-middle">
            <thead><tr><th>Логин</th><th>ФИО</th><th>Роль</th><th class="text-end">Действия</th></tr></thead>
            <tbody>
              <?php foreach ($users as $u): ?>
              <tr<?= (int)$u['id'] === (int)$cur['id'] ? ' class="table-light"' : '' ?>>
                <td><code><?= h($u['login']) ?></code><?= (int)$u['id'] === (int)$cur['id'] ? ' <span class="badge bg-secondary ms-1">это вы</span>' : '' ?></td>
                <td><?= h($u['full_name']) ?></td>
                <td><span class="badge bg-<?= $u['role'] === 'admin' ? 'primary' : 'secondary' ?>"><?= h($u['role']) ?></span></td>
                <td class="text-end">
                  <a class="btn btn-sm btn-outline-secondary" href="?id=<?= (int)$u['id'] ?>">✎</a>
                  <form action="/personnel_system/api/user_delete.php" method="post" class="d-inline" onsubmit="return confirm('Удалить пользователя <?= h(addslashes($u['login'])) ?>?');">
                    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                    <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger" <?= (int)$u['id'] === (int)$cur['id'] ? 'disabled title="Нельзя удалить себя"' : 'title="Удалить"' ?>>🗑</button>
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
          <h5 class="card-title h6 mb-3"><?= $edit_id > 0 ? 'Изменение пользователя' : 'Новый пользователь' ?></h5>
          <?php if ($errors): ?>
            <div class="alert alert-danger">
              <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul>
            </div>
          <?php endif; ?>
          <form method="post">
            <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
            <div class="mb-3">
              <label class="form-label">Логин</label>
              <input type="text" name="login" value="<?= h($form['login']) ?>" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">ФИО</label>
              <input type="text" name="full_name" value="<?= h($form['full_name']) ?>" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Роль</label>
              <select name="role" class="form-select" required>
                <option value="hr"    <?= $form['role'] === 'hr'    ? 'selected' : '' ?>>hr</option>
                <option value="admin" <?= $form['role'] === 'admin' ? 'selected' : '' ?>>admin</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Пароль <?= $edit_id > 0 ? '<span class="text-muted small">(оставьте пустым, чтобы не менять)</span>' : '' ?></label>
              <input type="password" name="password" class="form-control" <?= $edit_id === 0 ? 'required' : '' ?> autocomplete="new-password">
            </div>
            <div class="d-flex justify-content-between">
              <?php if ($edit_id > 0): ?>
                <a class="btn btn-link" href="/personnel_system/pages/users.php">Отмена</a>
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
