<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/salary.php';
require_login();

$type = (string)($_GET['type'] ?? 'salary');
if (!in_array($type, ['salary','load','combine'], true)) {
    http_response_code(400);
    exit('Unknown report type');
}

$ym = (string)($_GET['ym'] ?? date('Y-m'));
if (!preg_match('/^\d{4}-\d{2}$/', $ym)) $ym = date('Y-m');

$filename = "report_{$type}_{$ym}.csv";

header('Content-Type: text/csv; charset=utf-8');
header("Content-Disposition: attachment; filename=\"$filename\"");

// BOM для корректного открытия в Excel
echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');

if ($type === 'salary') {
    fputcsv($out, ['Сотрудник', 'Подразделение', 'Должность', 'Смен', 'Оклад', 'База', 'Коэффициент', 'К выплате'], ';');
    $payroll = calculate_payroll($ym, 0);
    foreach ($payroll['rows'] as $r) {
        fputcsv($out, [
            $r['fio'],
            $r['base_dept_name'],
            $r['position'],
            $r['shifts_total'],
            $r['salary'],
            $r['base_sum'],
            number_format($r['combine_coef'], 3, '.', ''),
            $r['total_salary'],
        ], ';');
    }
    fputcsv($out, ['Итого ФОТ', '', '', '', '', '', '', $payroll['total']], ';');

} elseif ($type === 'load') {
    fputcsv($out, ['Подразделение', 'Смен', 'Сотрудников'], ';');
    foreach (calculate_load($ym) as $r) {
        fputcsv($out, [$r['name'], $r['shifts_count'], $r['employees_count']], ';');
    }

} else { // combine
    fputcsv($out, ['Сотрудник', 'Основное подразделение', 'Подразделение смен', 'Количество смен'], ';');
    foreach (calculate_combine($ym) as $r) {
        foreach ($r['by_dept'] as $bd) {
            fputcsv($out, [$r['fio'], $r['base_dept_name'], $bd['dept_name'], $bd['shifts_count']], ';');
        }
    }
}

fclose($out);
