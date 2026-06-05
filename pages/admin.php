<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/helpers.php';
require_role('admin');

$counts = [
    'departments' => (int)db()->query('SELECT COUNT(*) FROM departments')->fetchColumn(),
    'positions'   => (int)db()->query('SELECT COUNT(*) FROM positions')->fetchColumn(),
    'users'       => (int)db()->query('SELECT COUNT(*) FROM users')->fetchColumn(),
];

layout_head('Администрирование');
layout_nav('admin');
layout_admin_subnav('admin');
?>
<div class="container-xxl">
  <h1 class="page-title h3">Администрирование</h1>
  <?php flash_render(); ?>

  <div class="row g-3">
    <div class="col-md-4">
      <a class="card text-decoration-none text-reset h-100" href="/personnel_system/pages/departments.php">
        <div class="card-body">
          <div class="text-muted small text-uppercase">Подразделения</div>
          <div class="display-6 fw-light mt-2"><?= $counts['departments'] ?></div>
        </div>
      </a>
    </div>
    <div class="col-md-4">
      <a class="card text-decoration-none text-reset h-100" href="/personnel_system/pages/positions.php">
        <div class="card-body">
          <div class="text-muted small text-uppercase">Должности</div>
          <div class="display-6 fw-light mt-2"><?= $counts['positions'] ?></div>
        </div>
      </a>
    </div>
    <div class="col-md-4">
      <a class="card text-decoration-none text-reset h-100" href="/personnel_system/pages/users.php">
        <div class="card-body">
          <div class="text-muted small text-uppercase">Пользователи</div>
          <div class="display-6 fw-light mt-2"><?= $counts['users'] ?></div>
        </div>
      </a>
    </div>
  </div>
</div>
<?php layout_foot(); ?>
