<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

function h(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function layout_head(string $page_title): void {
    $cfg = require __DIR__ . '/config.php';
    $app_title = $cfg['app']['title'];
    ?><!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<title><?= h($page_title) ?> - <?= h($app_title) ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="/personnel_system/assets/css/style.css">
</head>
<body class="bg-light">
<?php
}

function layout_nav(string $active = ''): void {
    $u = current_user();
    if (!$u) return;
    $items = [
        ['key' => 'employees',  'href' => '/personnel_system/pages/employees.php',  'label' => 'Сотрудники',  'roles' => ['admin','hr']],
        ['key' => 'schedule',   'href' => '/personnel_system/pages/schedule.php',   'label' => 'График смен', 'roles' => ['admin','hr']],
        ['key' => 'salary',     'href' => '/personnel_system/pages/salary.php',     'label' => 'Зарплата',    'roles' => ['admin','hr']],
        ['key' => 'reports',    'href' => '/personnel_system/pages/reports.php',    'label' => 'Отчёты',      'roles' => ['admin','hr']],
        ['key' => 'admin',      'href' => '/personnel_system/pages/admin.php',      'label' => 'Администрирование', 'roles' => ['admin']],
    ];
    ?>
<nav class="navbar navbar-expand navbar-light bg-white border-bottom">
  <div class="container-xxl">
    <a class="navbar-brand fw-semibold" href="/personnel_system/">Учёт кадров</a>
    <ul class="navbar-nav me-auto">
<?php foreach ($items as $it):
    if (!in_array($u['role'], $it['roles'], true)) continue;
    $cls = $active === $it['key'] ? 'nav-link active fw-semibold' : 'nav-link'; ?>
      <li class="nav-item"><a class="<?= $cls ?>" href="<?= h($it['href']) ?>"><?= h($it['label']) ?></a></li>
<?php endforeach; ?>
    </ul>
    <span class="text-muted small me-3"><?= h($u['full_name']) ?> · <?= h($u['role']) ?></span>
    <a class="btn btn-sm btn-outline-secondary" href="/personnel_system/logout.php">Выйти</a>
  </div>
</nav>
<?php
}

function layout_foot(): void {
    ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
}

function layout_admin_subnav(string $active = ''): void {
    $items = [
        ['key' => 'admin',       'href' => '/personnel_system/pages/admin.php',       'label' => 'Обзор'],
        ['key' => 'departments', 'href' => '/personnel_system/pages/departments.php', 'label' => 'Подразделения'],
        ['key' => 'positions',   'href' => '/personnel_system/pages/positions.php',   'label' => 'Должности'],
        ['key' => 'users',       'href' => '/personnel_system/pages/users.php',       'label' => 'Пользователи'],
    ];
    ?>
<div class="container-xxl pt-3">
  <ul class="nav nav-pills">
<?php foreach ($items as $it):
    $cls = $active === $it['key'] ? 'nav-link active' : 'nav-link'; ?>
    <li class="nav-item"><a class="<?= $cls ?>" href="<?= h($it['href']) ?>"><?= h($it['label']) ?></a></li>
<?php endforeach; ?>
  </ul>
</div>
<?php
}

function flash_set(string $type, string $msg): void {
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg];
}

function flash_render(): void {
    if (empty($_SESSION['flash'])) return;
    foreach ($_SESSION['flash'] as $f) {
        $cls = $f['type'] === 'error' ? 'alert-danger' : ($f['type'] === 'success' ? 'alert-success' : 'alert-info');
        echo '<div class="alert ' . $cls . '">' . h($f['msg']) . '</div>';
    }
    $_SESSION['flash'] = [];
}
