<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

/**
 * Расчёт заработной платы за месяц.
 *
 * shift_cost   = salary / monthly_norm
 * base_sum     = shifts_total × shift_cost
 * combine_coef = 1 + (shifts_other / shifts_total) × combine_rate
 * total_salary = base_sum × combine_coef
 */
function calculate_payroll(string $ym, int $dept_filter = 0): array {
    $cfg  = require __DIR__ . '/config.php';
    $norm = (float)$cfg['app']['monthly_norm'];
    $rate = (float)$cfg['app']['combine_rate'];

    [$year, $month] = array_map('intval', explode('-', $ym));
    $from = sprintf('%04d-%02d-01', $year, $month);
    $to   = date('Y-m-t', strtotime($from));

    $sql = "
        SELECT e.id, e.surname, e.first_name, e.patronymic, e.salary,
               e.department_id AS base_dept_id,
               d.name  AS base_dept_name,
               d.color AS base_dept_color,
               p.name  AS position_name
        FROM employees e
        JOIN departments d ON d.id = e.department_id
        JOIN positions   p ON p.id = e.position_id
    ";
    $params = [];
    if ($dept_filter > 0) {
        $sql .= ' WHERE e.department_id = :df';
        $params[':df'] = $dept_filter;
    }
    $sql .= ' ORDER BY e.surname, e.first_name';
    $st = db()->prepare($sql);
    $st->execute($params);
    $employees = $st->fetchAll();

    $st = db()->prepare("
        SELECT s.employee_id, s.shift_date, s.department_id,
               d.name AS dept_name, d.color AS dept_color
        FROM shifts s
        JOIN departments d ON d.id = s.department_id
        WHERE s.shift_date BETWEEN :a AND :b
        ORDER BY s.shift_date
    ");
    $st->execute([':a' => $from, ':b' => $to]);
    $shifts_by_emp = [];
    foreach ($st->fetchAll() as $sh) {
        $shifts_by_emp[(int)$sh['employee_id']][] = $sh;
    }

    $rows = [];
    $payroll_total = 0.0;

    foreach ($employees as $e) {
        $emp_shifts = $shifts_by_emp[(int)$e['id']] ?? [];
        $total = count($emp_shifts);
        $main  = 0;
        $other = 0;
        foreach ($emp_shifts as $sh) {
            if ((int)$sh['department_id'] === (int)$e['base_dept_id']) $main++;
            else $other++;
        }

        $shift_cost = $norm > 0 ? ((float)$e['salary']) / $norm : 0.0;
        $base_sum   = $total * $shift_cost;
        $coef       = $other > 0 && $total > 0 ? 1 + ($other / $total) * $rate : 1.0;
        $total_pay  = round($base_sum * $coef);

        $payroll_total += $total_pay;

        $rows[] = [
            'emp_id'          => (int)$e['id'],
            'fio'             => trim($e['surname'] . ' ' . mb_substr($e['first_name'], 0, 1) . '.' . ($e['patronymic'] ? mb_substr($e['patronymic'], 0, 1) . '.' : '')),
            'surname'         => $e['surname'],
            'first_name'      => $e['first_name'],
            'patronymic'      => $e['patronymic'],
            'base_dept_id'    => (int)$e['base_dept_id'],
            'base_dept_name'  => $e['base_dept_name'],
            'base_dept_color' => $e['base_dept_color'],
            'position'        => $e['position_name'],
            'salary'          => (float)$e['salary'],
            'shifts_total'    => $total,
            'shifts_main'     => $main,
            'shifts_other'    => $other,
            'shift_cost'      => round($shift_cost),
            'base_sum'        => round($base_sum),
            'combine_coef'    => $coef,
            'total_salary'    => $total_pay,
            'detail'          => $emp_shifts,
        ];
    }

    return [
        'rows'    => $rows,
        'total'   => $payroll_total,
        'ym'      => $ym,
        'from'    => $from,
        'to'      => $to,
        'norm'    => $norm,
        'rate'    => $rate,
    ];
}

/**
 * Загрузка подразделений за месяц.
 */
function calculate_load(string $ym): array {
    [$y, $m] = array_map('intval', explode('-', $ym));
    $from = sprintf('%04d-%02d-01', $y, $m);
    $to   = date('Y-m-t', strtotime($from));

    $st = db()->prepare("
        SELECT d.id, d.name, d.color,
               COUNT(s.id) AS shifts_count,
               COUNT(DISTINCT s.employee_id) AS employees_count
        FROM departments d
        LEFT JOIN shifts s ON s.department_id = d.id AND s.shift_date BETWEEN :a AND :b
        GROUP BY d.id, d.name, d.color
        ORDER BY shifts_count DESC, d.id
    ");
    $st->execute([':a' => $from, ':b' => $to]);
    return $st->fetchAll();
}

/**
 * Сотрудники, у которых были смены в более чем одном подразделении за месяц.
 */
function calculate_combine(string $ym): array {
    [$y, $m] = array_map('intval', explode('-', $ym));
    $from = sprintf('%04d-%02d-01', $y, $m);
    $to   = date('Y-m-t', strtotime($from));

    $st = db()->prepare("
        SELECT e.id, e.surname, e.first_name, e.patronymic,
               e.department_id AS base_dept_id,
               bd.name  AS base_dept_name,
               bd.color AS base_dept_color,
               s.department_id,
               d.name  AS dept_name,
               d.color AS dept_color,
               COUNT(*) AS shifts_count
        FROM shifts s
        JOIN employees   e  ON e.id  = s.employee_id
        JOIN departments d  ON d.id  = s.department_id
        JOIN departments bd ON bd.id = e.department_id
        WHERE s.shift_date BETWEEN :a AND :b
        GROUP BY e.id, e.surname, e.first_name, e.patronymic, e.department_id, bd.name, bd.color, s.department_id, d.name, d.color
        ORDER BY e.surname, e.first_name
    ");
    $st->execute([':a' => $from, ':b' => $to]);
    $rows = $st->fetchAll();

    $by_emp = [];
    foreach ($rows as $r) {
        $eid = (int)$r['id'];
        if (!isset($by_emp[$eid])) {
            $by_emp[$eid] = [
                'emp_id'         => $eid,
                'fio'            => trim($r['surname'] . ' ' . mb_substr($r['first_name'], 0, 1) . '.' . ($r['patronymic'] ? mb_substr($r['patronymic'], 0, 1) . '.' : '')),
                'base_dept_id'   => (int)$r['base_dept_id'],
                'base_dept_name' => $r['base_dept_name'],
                'base_dept_color'=> $r['base_dept_color'],
                'by_dept'        => [],
                'total'          => 0,
                'main_shifts'    => 0,
                'other_shifts'   => 0,
            ];
        }
        $by_emp[$eid]['by_dept'][] = [
            'dept_id'      => (int)$r['department_id'],
            'dept_name'    => $r['dept_name'],
            'dept_color'   => $r['dept_color'],
            'shifts_count' => (int)$r['shifts_count'],
        ];
        $by_emp[$eid]['total'] += (int)$r['shifts_count'];
        if ((int)$r['department_id'] === (int)$r['base_dept_id']) {
            $by_emp[$eid]['main_shifts'] += (int)$r['shifts_count'];
        } else {
            $by_emp[$eid]['other_shifts'] += (int)$r['shifts_count'];
        }
    }

    return array_values(array_filter($by_emp, fn($x) => count($x['by_dept']) > 1));
}
