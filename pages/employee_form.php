<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
$is_edit = $id > 0;
$errors = [];

$emp = [
    'id'            => 0,
    'surname'       => '',
    'first_name'    => '',
    'patronymic'    => '',
    'department_id' => 0,
    'position_id'   => 0,
    'salary'        => '',
    'hire_date'     => '',
    'phone'         => '',
    'email'         => '',
];

if ($is_edit) {
    $st = db()->prepare('SELECT * FROM employees WHERE id = :id');
    $st->execute([':id' => $id]);
    $row = $st->fetch();
    if (!$row) {
        flash_set('error', 'Сотрудник не найден.');
        header('Location: /personnel_system/pages/employees.php');
        exit;
    }
    $emp = array_merge($emp, $row);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $emp['surname']       = trim((string)($_POST['surname'] ?? ''));
    $emp['first_name']    = trim((string)($_POST['first_name'] ?? ''));
    $emp['patronymic']    = trim((string)($_POST['patronymic'] ?? ''));
    $emp['department_id'] = (int)($_POST['department_id'] ?? 0);
    $emp['position_id']   = (int)($_POST['position_id'] ?? 0);
    $emp['salary']        = (string)($_POST['salary'] ?? '');
    $emp['hire_date']     = (string)($_POST['hire_date'] ?? '');
    $emp['phone']         = trim((string)($_POST['phone'] ?? ''));
    $emp['email']         = trim((string)($_POST['email'] ?? ''));

    if ($emp['surname'] === '')      $errors[] = 'Укажите фамилию.';
    if ($emp['first_name'] === '')   $errors[] = 'Укажите имя.';
    if ($emp['department_id'] <= 0)  $errors[] = 'Выберите подразделение.';
    if ($emp['position_id']   <= 0)  $errors[] = 'Выберите должность.';
    if (!is_numeric($emp['salary']) || (float)$emp['salary'] < 0) $errors[] = 'Оклад должен быть числом ≥ 0.';
    if ($emp['email'] !== '' && !filter_var($emp['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Некорректный email.';
    if ($emp['hire_date'] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $emp['hire_date'])) $errors[] = 'Некорректная дата приёма.';

    if (!$errors) {
        if ($is_edit) {
            $sql = 'UPDATE employees SET surname=:s, first_name=:f, patronymic=:p, department_id=:d, position_id=:po, salary=:sal, hire_date=:hd, phone=:ph, email=:em WHERE id=:id';
            $params = [':id' => $id];
        } else {
            $sql = 'INSERT INTO employees (surname, first_name, patronymic, department_id, position_id, salary, hire_date, phone, email) VALUES (:s, :f, :p, :d, :po, :sal, :hd, :ph, :em)';
            $params = [];
        }
        $params += [
            ':s'   => $emp['surname'],
            ':f'   => $emp['first_name'],
            ':p'   => $emp['patronymic'] ?: null,
            ':d'   => $emp['department_id'],
            ':po'  => $emp['position_id'],
            ':sal' => (float)$emp['salary'],
            ':hd'  => $emp['hire_date'] ?: null,
            ':ph'  => $emp['phone'] ?: null,
            ':em'  => $emp['email'] ?: null,
        ];
        db()->prepare($sql)->execute($params);
        flash_set('success', $is_edit ? 'Сотрудник обновлён.' : 'Сотрудник добавлен.');
        header('Location: /personnel_system/pages/employees.php');
        exit;
    }
}

$departments = db()->query('SELECT id, name FROM departments ORDER BY name')->fetchAll();
$positions   = db()->query('SELECT id, name FROM positions   ORDER BY name')->fetchAll();

layout_head($is_edit ? 'Изменение сотрудника' : 'Новый сотрудник');
layout_nav('employees');
?>
<div class="container-xxl" style="max-width: 720px;">
  <h1 class="page-title h3"><?= $is_edit ? 'Изменение сотрудника' : 'Добавление сотрудника' ?></h1>

  <?php if ($errors): ?>
    <div class="alert alert-danger">
      <ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= h($err) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <form method="post" class="card">
    <div class="card-body">
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">

      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Фамилия<span class="text-danger">*</span></label>
          <input type="text" name="surname" value="<?= h($emp['surname']) ?>" class="form-control" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Имя<span class="text-danger">*</span></label>
          <input type="text" name="first_name" value="<?= h($emp['first_name']) ?>" class="form-control" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Отчество</label>
          <input type="text" name="patronymic" value="<?= h($emp['patronymic']) ?>" class="form-control">
        </div>

        <div class="col-md-6">
          <label class="form-label">Подразделение<span class="text-danger">*</span></label>
          <select name="department_id" class="form-select" required>
            <option value="">- выберите -</option>
            <?php foreach ($departments as $d): ?>
              <option value="<?= (int)$d['id'] ?>" <?= (int)$emp['department_id'] === (int)$d['id'] ? 'selected' : '' ?>><?= h($d['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">Должность<span class="text-danger">*</span></label>
          <select name="position_id" class="form-select" required>
            <option value="">- выберите -</option>
            <?php foreach ($positions as $p): ?>
              <option value="<?= (int)$p['id'] ?>" <?= (int)$emp['position_id'] === (int)$p['id'] ? 'selected' : '' ?>><?= h($p['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-4">
          <label class="form-label">Оклад, ₽<span class="text-danger">*</span></label>
          <input type="number" min="0" step="100" name="salary" value="<?= h((string)$emp['salary']) ?>" class="form-control" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Дата приёма</label>
          <input type="date" name="hire_date" value="<?= h((string)$emp['hire_date']) ?>" class="form-control">
        </div>
        <div class="col-md-4">
          <label class="form-label">Телефон</label>
          <input type="text" name="phone" value="<?= h($emp['phone']) ?>" class="form-control" placeholder="+7-...">
        </div>

        <div class="col-md-12">
          <label class="form-label">Email</label>
          <input type="email" name="email" value="<?= h($emp['email']) ?>" class="form-control">
        </div>
      </div>
    </div>
    <div class="card-footer d-flex justify-content-between">
      <a class="btn btn-link" href="/personnel_system/pages/employees.php">Отмена</a>
      <button type="submit" class="btn btn-primary"><?= $is_edit ? 'Сохранить' : 'Добавить' ?></button>
    </div>
  </form>
</div>
<?php layout_foot(); ?>
