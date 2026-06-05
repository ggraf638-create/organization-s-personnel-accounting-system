<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login();

$dept_id = isset($_GET['dept']) && $_GET['dept'] !== '' ? (int)$_GET['dept'] : 0;
$q       = trim((string)($_GET['q'] ?? ''));
$ym      = (string)($_GET['ym'] ?? date('Y-m'));

$ym_safe = preg_match('/^\d{4}-\d{2}$/', $ym) ? $ym : date('Y-m');
[$year, $month] = array_map('intval', explode('-', $ym_safe));
$from = sprintf('%04d-%02d-01', $year, $month);
$to   = date('Y-m-t', strtotime($from));

$departments = db()->query('SELECT id, name, color FROM departments ORDER BY id')->fetchAll();

$sql = "
    SELECT e.*, d.name AS dept_name, d.color AS dept_color, p.name AS position_name,
           (SELECT COUNT(*) FROM shifts s WHERE s.employee_id = e.id AND s.shift_date BETWEEN :from AND :to) AS shifts_count
    FROM employees e
    JOIN departments d ON d.id = e.department_id
    JOIN positions   p ON p.id = e.position_id
    WHERE 1=1
";
$params = [':from' => $from, ':to' => $to];
if ($dept_id > 0) {
    $sql .= ' AND e.department_id = :d';
    $params[':d'] = $dept_id;
}
if ($q !== '') {
    $sql .= ' AND e.surname LIKE :q';
    $params[':q'] = $q . '%';
}
$sql .= ' ORDER BY e.surname, e.first_name';

$st = db()->prepare($sql);
$st->execute($params);
$employees = $st->fetchAll();

layout_head('Сотрудники');
layout_nav('employees');
?>
<div class="container-xxl">
  <h1 class="page-title h3">Сотрудники</h1>
  <?php flash_render(); ?>

  <div class="card mb-3">
    <div class="card-body">
      <form method="get" class="row g-3 align-items-end">
        <div class="col-auto">
          <label class="form-label small text-uppercase text-muted">Подразделение</label>
          <div class="btn-group" role="group">
            <a class="btn btn-sm <?= $dept_id === 0 ? 'btn-light border' : 'btn-outline-secondary border-0' ?>"
               href="?<?= http_build_query(array_filter(['q' => $q, 'ym' => $ym_safe])) ?>">Все</a>
            <?php foreach ($departments as $d): ?>
              <a class="btn btn-sm <?= $dept_id === (int)$d['id'] ? 'btn-light border' : 'btn-outline-secondary border-0' ?>"
                 href="?<?= http_build_query(array_filter(['dept' => $d['id'], 'q' => $q, 'ym' => $ym_safe])) ?>"><?= h($d['name']) ?></a>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="col-md-4">
          <label class="form-label small text-uppercase text-muted">Поиск по фамилии</label>
          <input type="text" name="q" value="<?= h($q) ?>" class="form-control" placeholder="Иванов, Петрова...">
          <?php if ($dept_id > 0): ?><input type="hidden" name="dept" value="<?= (int)$dept_id ?>"><?php endif; ?>
        </div>
        <div class="col-auto">
          <label class="form-label small text-uppercase text-muted d-block">Месяц</label>
          <input type="month" name="ym" value="<?= h($ym_safe) ?>" class="form-control" onchange="this.form.submit()">
        </div>
        <div class="col-auto ms-auto">
          <a class="btn btn-primary" href="/personnel_system/pages/employee_form.php">+ Добавить сотрудника</a>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-body p-0">
      <?php if (empty($employees)): ?>
        <div class="p-4 text-center text-muted">Сотрудники не найдены.</div>
      <?php else: ?>
      <table class="table mb-0 align-middle">
        <thead>
          <tr>
            <th>Фамилия И.О.</th>
            <th>Основное подразделение</th>
            <th>Должность</th>
            <th class="text-center">Смен в <?= h($ym_safe) ?></th>
            <th>Оклад</th>
            <th class="text-end">Действия</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($employees as $e): ?>
          <tr>
            <td>
              <a href="/personnel_system/pages/employee_form.php?id=<?= (int)$e['id'] ?>" class="text-decoration-none fw-semibold"><?= h(fio_short($e)) ?></a>
            </td>
            <td>
              <span class="badge-dept" style="background-color: <?= h($e['dept_color']) ?>"><?= h($e['dept_name']) ?></span>
            </td>
            <td><?= h($e['position_name']) ?></td>
            <td class="text-center"><?= (int)$e['shifts_count'] > 0 ? (int)$e['shifts_count'] : '-' ?></td>
            <td><?= h(money_ru($e['salary'])) ?></td>
            <td class="text-end">
              <a class="btn btn-sm btn-outline-secondary" href="/personnel_system/pages/employee_form.php?id=<?= (int)$e['id'] ?>" title="Изменить">✎</a>
              <form action="/personnel_system/api/employee_delete.php" method="post" class="d-inline" onsubmit="return confirm('Удалить сотрудника <?= h(addslashes(fio_short($e))) ?>?');">
                <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int)$e['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger" title="Удалить">🗑</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>

  <div class="text-muted small mt-3">Всего записей: <?= count($employees) ?></div>
</div>
<?php layout_foot(); ?>
