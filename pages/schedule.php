<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login();

$ym = (string)($_GET['ym'] ?? date('Y-m'));
if (!preg_match('/^\d{4}-\d{2}$/', $ym)) $ym = date('Y-m');
[$year, $month] = array_map('intval', explode('-', $ym));
$from = sprintf('%04d-%02d-01', $year, $month);
$days = (int)date('t', strtotime($from));
$to   = sprintf('%04d-%02d-%02d', $year, $month, $days);

$prev_ym = date('Y-m', strtotime("$from -1 month"));
$next_ym = date('Y-m', strtotime("$from +1 month"));
$months_ru = [1=>'январь','февраль','март','апрель','май','июнь','июль','август','сентябрь','октябрь','ноябрь','декабрь'];
$title_human = $months_ru[$month] . ' ' . $year;

$filter_dept = isset($_GET['dept']) && $_GET['dept'] !== '' ? (int)$_GET['dept'] : 0;

$departments = db()->query('SELECT id, name, color FROM departments ORDER BY id')->fetchAll();
$dept_by_id = [];
foreach ($departments as $d) $dept_by_id[(int)$d['id']] = $d;

$sql = 'SELECT id, surname, first_name, patronymic, department_id FROM employees';
if ($filter_dept > 0) {
    $sql .= ' WHERE department_id = :d';
}
$sql .= ' ORDER BY surname, first_name';
$st = db()->prepare($sql);
$st->execute($filter_dept > 0 ? [':d' => $filter_dept] : []);
$employees = $st->fetchAll();

$st = db()->prepare('SELECT employee_id, shift_date, department_id FROM shifts WHERE shift_date BETWEEN :a AND :b');
$st->execute([':a' => $from, ':b' => $to]);
$shifts_map = [];
foreach ($st->fetchAll() as $sh) {
    $key = (int)$sh['employee_id'] . '|' . substr($sh['shift_date'], 0, 10);
    $shifts_map[$key] = (int)$sh['department_id'];
}

function short_dept(string $name): string {
    $name = trim($name);
    if ($name === '') return '';
    $parts = preg_split('/\s+/u', $name);
    if (count($parts) >= 2) {
        return mb_strtoupper(mb_substr($parts[0], 0, 1) . mb_substr($parts[1], 0, 1));
    }
    return mb_substr($name, 0, 2);
}

layout_head('График смен');
layout_nav('schedule');
?>
<style>
.schedule-table { font-size: 0.85rem; }
.schedule-table th, .schedule-table td { padding: 0.25rem; text-align: center; vertical-align: middle; }
.schedule-table th.day { width: 36px; min-width: 36px; }
.schedule-table th.emp { width: 180px; text-align: left; padding-left: 0.75rem; position: sticky; left: 0; background: #fff; z-index: 2; }
.schedule-table td.emp { text-align: left; padding-left: 0.75rem; position: sticky; left: 0; background: #fff; font-weight: 500; }
.schedule-table td.day-cell { cursor: pointer; height: 36px; }
.schedule-table td.day-cell:hover { outline: 2px solid #0d6efd; outline-offset: -2px; }
.shift-chip { display: inline-block; min-width: 28px; padding: 2px 6px; border-radius: 4px; color: #fff; font-size: 0.75rem; font-weight: 600; }
.shift-chip.combine { box-shadow: 0 0 0 2px #fff, 0 0 0 3px #dc3545; }
.day-empty { color: #ced4da; }
.dept-popup { position: absolute; background: #fff; border: 1px solid #dee2e6; border-radius: 6px; box-shadow: 0 4px 16px rgba(0,0,0,0.12); padding: 6px; z-index: 1050; display: flex; gap: 4px; }
.dept-popup button { border: none; background: none; padding: 4px 8px; border-radius: 4px; font-size: 0.85rem; cursor: pointer; color: #fff; font-weight: 600; min-width: 32px; }
.dept-popup button.clear { color: #6c757d; background: #f1f3f5; }
.dept-popup button:hover { opacity: 0.85; }
</style>

<div class="container-xxl">
  <h1 class="page-title h3">График смен</h1>
  <?php flash_render(); ?>

  <div class="card mb-3">
    <div class="card-body">
      <div class="row g-3 align-items-end">
        <div class="col-auto">
          <label class="form-label small text-uppercase text-muted">Месяц</label>
          <div class="d-flex align-items-center gap-2">
            <a class="btn btn-sm btn-outline-secondary" href="?ym=<?= h($prev_ym) ?><?= $filter_dept ? '&dept='.$filter_dept : '' ?>">←</a>
            <span class="fw-semibold mx-2" style="min-width: 150px; display: inline-block; text-align: center;"><?= h($title_human) ?></span>
            <a class="btn btn-sm btn-outline-secondary" href="?ym=<?= h($next_ym) ?><?= $filter_dept ? '&dept='.$filter_dept : '' ?>">→</a>
          </div>
        </div>
        <div class="col-auto">
          <label class="form-label small text-uppercase text-muted d-block">Фильтр</label>
          <div class="btn-group" role="group">
            <a class="btn btn-sm <?= $filter_dept === 0 ? 'btn-light border' : 'btn-outline-secondary border-0' ?>" href="?ym=<?= h($ym) ?>">Все</a>
            <?php foreach ($departments as $d): ?>
              <a class="btn btn-sm <?= $filter_dept === (int)$d['id'] ? 'btn-light border' : 'btn-outline-secondary border-0' ?>" href="?ym=<?= h($ym) ?>&dept=<?= (int)$d['id'] ?>"><?= h($d['name']) ?></a>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="col-auto ms-auto">
          <span class="small text-muted">Легенда:</span>
          <?php foreach ($departments as $d): ?>
            <span class="shift-chip ms-2" style="background-color: <?= h($d['color']) ?>"><?= h(short_dept($d['name'])) ?></span>
            <span class="small text-muted me-2"><?= h($d['name']) ?></span>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-body p-0" style="overflow-x: auto;">
      <table class="table mb-0 schedule-table">
        <thead>
          <tr>
            <th class="emp">Сотрудник</th>
            <?php for ($d = 1; $d <= $days; $d++): ?>
              <th class="day"><?= $d ?></th>
            <?php endfor; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($employees as $e): ?>
          <tr>
            <td class="emp"><?= h(fio_short($e)) ?></td>
            <?php for ($d = 1; $d <= $days; $d++):
                $date = sprintf('%04d-%02d-%02d', $year, $month, $d);
                $key  = (int)$e['id'] . '|' . $date;
                $cur_dept = $shifts_map[$key] ?? null;
                $cur_color = $cur_dept ? ($dept_by_id[$cur_dept]['color'] ?? '#0d6efd') : '';
                $cur_label = $cur_dept ? short_dept($dept_by_id[$cur_dept]['name'] ?? '') : '';
                $combine  = $cur_dept !== null && (int)$cur_dept !== (int)$e['department_id'];
            ?>
              <td class="day-cell" data-emp="<?= (int)$e['id'] ?>" data-date="<?= h($date) ?>" data-dept="<?= $cur_dept !== null ? (int)$cur_dept : '' ?>" data-base-dept="<?= (int)$e['department_id'] ?>">
                <?php if ($cur_dept !== null): ?>
                  <span class="shift-chip<?= $combine ? ' combine' : '' ?>" style="background-color: <?= h($cur_color) ?>"><?= h($cur_label) ?></span>
                <?php else: ?>
                  <span class="day-empty">·</span>
                <?php endif; ?>
              </td>
            <?php endfor; ?>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
(function() {
  const csrf = <?= json_encode(csrf_token()) ?>;
  const depts = <?= json_encode($departments, JSON_UNESCAPED_UNICODE) ?>;
  let popup = null;

  function shortDept(name) {
    const parts = name.trim().split(/\s+/);
    if (parts.length >= 2) return (parts[0][0] + parts[1][0]).toUpperCase();
    return name.slice(0, 2).toUpperCase();
  }

  function closePopup() {
    if (popup) { popup.remove(); popup = null; }
  }

  document.addEventListener('click', (ev) => {
    const cell = ev.target.closest('.day-cell');
    if (!cell) { closePopup(); return; }
    if (popup && popup._cell === cell) return;

    closePopup();
    popup = document.createElement('div');
    popup._cell = cell;
    popup.className = 'dept-popup';
    depts.forEach(d => {
      const b = document.createElement('button');
      b.style.backgroundColor = d.color;
      b.textContent = shortDept(d.name);
      b.title = d.name;
      b.onclick = (e) => { e.stopPropagation(); saveShift(cell, d.id); };
      popup.appendChild(b);
    });
    const clr = document.createElement('button');
    clr.className = 'clear';
    clr.textContent = '×';
    clr.title = 'Удалить смену';
    clr.onclick = (e) => { e.stopPropagation(); saveShift(cell, ''); };
    popup.appendChild(clr);

    document.body.appendChild(popup);
    const r = cell.getBoundingClientRect();
    popup.style.top  = (window.scrollY + r.bottom + 4) + 'px';
    popup.style.left = (window.scrollX + r.left)       + 'px';
  });

  document.addEventListener('keydown', (ev) => {
    if (ev.key === 'Escape') closePopup();
  });

  function saveShift(cell, deptId) {
    const fd = new FormData();
    fd.append('csrf', csrf);
    fd.append('employee_id', cell.dataset.emp);
    fd.append('shift_date',  cell.dataset.date);
    fd.append('department_id', deptId === '' ? '' : String(deptId));

    fetch('/personnel_system/api/shift_save.php', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(data => {
        if (!data.ok) { alert('Ошибка: ' + (data.error || 'неизвестно')); return; }
        cell.innerHTML = '';
        if (data.department_id) {
          const chip = document.createElement('span');
          const baseDept = parseInt(cell.dataset.baseDept, 10);
          const combine = data.department_id !== baseDept;
          chip.className = 'shift-chip' + (combine ? ' combine' : '');
          chip.style.backgroundColor = data.color;
          chip.textContent = shortDept(data.department_name);
          cell.appendChild(chip);
          cell.dataset.dept = data.department_id;
        } else {
          const dot = document.createElement('span');
          dot.className = 'day-empty';
          dot.textContent = '·';
          cell.appendChild(dot);
          cell.dataset.dept = '';
        }
        closePopup();
      })
      .catch(err => alert('Сбой запроса: ' + err.message));
  }
})();
</script>

<?php layout_foot(); ?>
