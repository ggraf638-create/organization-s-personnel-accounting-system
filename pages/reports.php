<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/salary.php';
require_login();

$type = (string)($_GET['type'] ?? 'salary');
if (!in_array($type, ['salary','load','combine'], true)) $type = 'salary';

$ym = (string)($_GET['ym'] ?? date('Y-m'));
if (!preg_match('/^\d{4}-\d{2}$/', $ym)) $ym = date('Y-m');

[$year, $month] = array_map('intval', explode('-', $ym));
$months_ru = [1=>'январь','февраль','март','апрель','май','июнь','июль','август','сентябрь','октябрь','ноябрь','декабрь'];
$title_human = $months_ru[$month] . ' ' . $year;

layout_head('Отчёты');
layout_nav('reports');
?>
<div class="container-xxl">
  <h1 class="page-title h3">Отчёты</h1>
  <?php flash_render(); ?>

  <div class="card mb-3">
    <div class="card-body">
      <form method="get" class="row g-3 align-items-end">
        <div class="col-auto">
          <label class="form-label small text-uppercase text-muted">Месяц</label>
          <input type="month" name="ym" value="<?= h($ym) ?>" class="form-control" onchange="this.form.submit()">
        </div>
        <div class="col-auto">
          <label class="form-label small text-uppercase text-muted d-block">Тип отчёта</label>
          <div class="btn-group" role="group">
            <a class="btn btn-sm <?= $type === 'salary'  ? 'btn-light border' : 'btn-outline-secondary border-0' ?>" href="?ym=<?= h($ym) ?>&type=salary">Заработная плата</a>
            <a class="btn btn-sm <?= $type === 'load'    ? 'btn-light border' : 'btn-outline-secondary border-0' ?>" href="?ym=<?= h($ym) ?>&type=load">Загрузка подразделений</a>
            <a class="btn btn-sm <?= $type === 'combine' ? 'btn-light border' : 'btn-outline-secondary border-0' ?>" href="?ym=<?= h($ym) ?>&type=combine">Совмещение должностей</a>
          </div>
        </div>
        <div class="col-auto ms-auto">
          <a class="btn btn-outline-primary" href="/personnel_system/api/report_csv.php?type=<?= h($type) ?>&ym=<?= h($ym) ?>">📥 Скачать CSV</a>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-body p-0">
    <?php if ($type === 'salary'):
        $payroll = calculate_payroll($ym, 0); ?>
      <table class="table mb-0 align-middle">
        <thead><tr>
          <th>Сотрудник</th>
          <th>Подразделение</th>
          <th>Должность</th>
          <th class="text-center">Смен</th>
          <th class="text-end">Оклад</th>
          <th class="text-end">База</th>
          <th class="text-center">Коэф.</th>
          <th class="text-end">К выплате</th>
        </tr></thead>
        <tbody>
          <?php foreach ($payroll['rows'] as $r): ?>
          <tr>
            <td><?= h($r['fio']) ?></td>
            <td><span class="badge-dept" style="background-color: <?= h($r['base_dept_color']) ?>"><?= h($r['base_dept_name']) ?></span></td>
            <td><?= h($r['position']) ?></td>
            <td class="text-center"><?= $r['shifts_total'] ?: '-' ?></td>
            <td class="text-end"><?= h(money_ru($r['salary'])) ?></td>
            <td class="text-end"><?= h(money_ru($r['base_sum'])) ?></td>
            <td class="text-center"><?= number_format($r['combine_coef'], 3) ?></td>
            <td class="text-end fw-semibold"><?= h(money_ru($r['total_salary'])) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot><tr class="table-light"><td colspan="7" class="text-end fw-semibold">Итого ФОТ за <?= h($title_human) ?>:</td><td class="text-end fw-bold"><?= h(money_ru($payroll['total'])) ?></td></tr></tfoot>
      </table>

    <?php elseif ($type === 'load'):
        $load = calculate_load($ym); ?>
      <table class="table mb-0 align-middle">
        <thead><tr>
          <th>Подразделение</th>
          <th class="text-center">Количество смен</th>
          <th class="text-center">Задействовано сотрудников</th>
          <th>Распределение нагрузки</th>
        </tr></thead>
        <tbody>
          <?php
            $max_shifts = max(array_map(fn($r) => (int)$r['shifts_count'], $load) ?: [1]);
          ?>
          <?php foreach ($load as $r): $pct = $max_shifts > 0 ? (int)$r['shifts_count'] / $max_shifts * 100 : 0; ?>
          <tr>
            <td><span class="badge-dept" style="background-color: <?= h($r['color']) ?>"><?= h($r['name']) ?></span></td>
            <td class="text-center fw-semibold"><?= (int)$r['shifts_count'] ?></td>
            <td class="text-center"><?= (int)$r['employees_count'] ?></td>
            <td>
              <div class="progress" style="height: 8px;">
                <div class="progress-bar" style="width: <?= number_format($pct, 1) ?>%; background-color: <?= h($r['color']) ?>"></div>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

    <?php else /* combine */:
        $combine = calculate_combine($ym); ?>
      <?php if (empty($combine)): ?>
        <div class="p-4 text-center text-muted">Совмещений за <?= h($title_human) ?> не зафиксировано.</div>
      <?php else: ?>
      <table class="table mb-0 align-middle">
        <thead><tr>
          <th>Сотрудник</th>
          <th>Основное подразделение</th>
          <th>Распределение смен</th>
          <th class="text-center">Всего</th>
          <th class="text-center">Из них в чужих</th>
        </tr></thead>
        <tbody>
          <?php foreach ($combine as $r): ?>
          <tr>
            <td class="fw-semibold"><?= h($r['fio']) ?></td>
            <td><span class="badge-dept" style="background-color: <?= h($r['base_dept_color']) ?>"><?= h($r['base_dept_name']) ?></span></td>
            <td>
              <?php foreach ($r['by_dept'] as $bd): ?>
                <span class="badge me-1" style="background-color: <?= h($bd['dept_color']) ?>"><?= h($bd['dept_name']) ?> · <?= $bd['shifts_count'] ?></span>
              <?php endforeach; ?>
            </td>
            <td class="text-center"><?= $r['total'] ?></td>
            <td class="text-center"><strong class="text-danger"><?= $r['other_shifts'] ?></strong></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    <?php endif; ?>
    </div>
  </div>
</div>
<?php layout_foot(); ?>
