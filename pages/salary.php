<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/salary.php';
require_login();

$ym = (string)($_GET['ym'] ?? date('Y-m'));
if (!preg_match('/^\d{4}-\d{2}$/', $ym)) $ym = date('Y-m');

$filter_dept = isset($_GET['dept']) && $_GET['dept'] !== '' ? (int)$_GET['dept'] : 0;

$payroll = calculate_payroll($ym, $filter_dept);
$departments = db()->query('SELECT id, name, color FROM departments ORDER BY id')->fetchAll();

[$year, $month] = array_map('intval', explode('-', $ym));
$months_ru = [1=>'январь','февраль','март','апрель','май','июнь','июль','август','сентябрь','октябрь','ноябрь','декабрь'];
$title_human = $months_ru[$month] . ' ' . $year;

layout_head('Зарплата');
layout_nav('salary');
?>
<div class="container-xxl">
  <h1 class="page-title h3">Расчёт заработной платы</h1>
  <?php flash_render(); ?>

  <div class="card mb-3">
    <div class="card-body">
      <form method="get" class="row g-3 align-items-end">
        <div class="col-auto">
          <label class="form-label small text-uppercase text-muted">Месяц расчёта</label>
          <input type="month" name="ym" value="<?= h($ym) ?>" class="form-control" onchange="this.form.submit()">
        </div>
        <div class="col-auto">
          <label class="form-label small text-uppercase text-muted d-block">Подразделение</label>
          <div class="btn-group" role="group">
            <a class="btn btn-sm <?= $filter_dept === 0 ? 'btn-light border' : 'btn-outline-secondary border-0' ?>" href="?ym=<?= h($ym) ?>">Все</a>
            <?php foreach ($departments as $d): ?>
              <a class="btn btn-sm <?= $filter_dept === (int)$d['id'] ? 'btn-light border' : 'btn-outline-secondary border-0' ?>" href="?ym=<?= h($ym) ?>&dept=<?= (int)$d['id'] ?>"><?= h($d['name']) ?></a>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="col-auto ms-auto text-end">
          <div class="small text-muted">Период: <?= h($payroll['from']) ?> - <?= h($payroll['to']) ?></div>
          <div><span class="text-muted small">Итого ФОТ:</span> <span class="fw-semibold fs-5"><?= h(money_ru($payroll['total'])) ?></span></div>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-body p-0">
      <?php if (empty($payroll['rows'])): ?>
        <div class="p-4 text-center text-muted">Сотрудники не найдены для выбранного фильтра.</div>
      <?php else: ?>
      <table class="table mb-0 align-middle">
        <thead>
          <tr>
            <th>Сотрудник</th>
            <th>Основное подразделение</th>
            <th class="text-end">Оклад</th>
            <th class="text-center">Отработано смен</th>
            <th class="text-end">Сумма за смены</th>
            <th class="text-center">Коэф. совмещения</th>
            <th class="text-end">Итого к выплате</th>
            <th class="text-center">Детализация</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($payroll['rows'] as $i => $row): ?>
          <tr>
            <td>
              <div class="fw-semibold"><?= h($row['fio']) ?></div>
              <div class="small text-muted"><?= h($row['position']) ?></div>
            </td>
            <td><span class="badge-dept" style="background-color: <?= h($row['base_dept_color']) ?>"><?= h($row['base_dept_name']) ?></span></td>
            <td class="text-end"><?= h(money_ru($row['salary'])) ?></td>
            <td class="text-center"><?= $row['shifts_total'] > 0 ? $row['shifts_total'] : '-' ?></td>
            <td class="text-end"><?= h(money_ru($row['base_sum'])) ?></td>
            <td class="text-center">
              <?php if ($row['shifts_other'] > 0): ?>
                <span class="badge bg-warning text-dark"><?= $row['shifts_other'] ?>/<?= $row['shifts_total'] ?> · <?= number_format(($row['combine_coef'] - 1) * 100, 1) ?>%</span>
              <?php else: ?>
                <span class="text-muted">-</span>
              <?php endif; ?>
            </td>
            <td class="text-end fw-semibold text-primary"><?= h(money_ru($row['total_salary'])) ?></td>
            <td class="text-center">
              <?php if ($row['shifts_total'] > 0): ?>
                <a class="small" data-bs-toggle="collapse" href="#detail-<?= $i ?>" role="button">Подробнее →</a>
              <?php endif; ?>
            </td>
          </tr>
          <?php if ($row['shifts_total'] > 0): ?>
          <tr class="collapse" id="detail-<?= $i ?>">
            <td colspan="8" class="bg-light">
              <div class="small">
                <strong>Смены за <?= h($title_human) ?>:</strong>
                <div class="mt-2">
                  <?php foreach ($row['detail'] as $sh): ?>
                    <?php $is_combine = (int)$sh['department_id'] !== $row['base_dept_id']; ?>
                    <span class="badge me-1 mb-1" style="background-color: <?= h($sh['dept_color']) ?>; <?= $is_combine ? 'box-shadow: 0 0 0 2px #dc3545;' : '' ?>">
                      <?= h(substr($sh['shift_date'], 8, 2)) ?> · <?= h($sh['dept_name']) ?>
                    </span>
                  <?php endforeach; ?>
                </div>
                <div class="mt-2 text-muted">
                  Расчёт: <?= $row['shifts_total'] ?> смен × <?= h(money_ru($row['shift_cost'])) ?> = <?= h(money_ru($row['base_sum'])) ?>
                  <?php if ($row['shifts_other'] > 0): ?>
                    × коэф. (1 + <?= $row['shifts_other'] ?>/<?= $row['shifts_total'] ?> × <?= number_format($payroll['rate'], 2) ?>) = <?= h(money_ru($row['total_salary'])) ?>
                  <?php else: ?>
                    = <?= h(money_ru($row['total_salary'])) ?>
                  <?php endif; ?>
                </div>
              </div>
            </td>
          </tr>
          <?php endif; ?>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr class="table-light">
            <td colspan="6" class="fw-semibold text-end">Итого ФОТ за <?= h($title_human) ?>:</td>
            <td class="text-end fw-bold fs-5"><?= h(money_ru($payroll['total'])) ?></td>
            <td></td>
          </tr>
        </tfoot>
      </table>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php layout_foot(); ?>
