<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/layout.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim((string)($_POST['login'] ?? ''));
    $pass  = (string)($_POST['password'] ?? '');
    if ($login === '' || $pass === '') {
        $error = 'Заполните логин и пароль.';
    } elseif (login_user($login, $pass)) {
        header('Location: /personnel_system/pages/employees.php');
        exit;
    } else {
        $error = 'Неверный логин или пароль.';
    }
}

if (current_user()) {
    header('Location: /personnel_system/pages/employees.php');
    exit;
}

layout_head('Вход');
?>
<div class="container" style="max-width: 420px;">
  <div class="card mt-5 shadow-sm">
    <div class="card-body p-4">
      <h1 class="h4 mb-4">Вход в систему</h1>

      <?php if ($error): ?>
        <div class="alert alert-danger py-2"><?= h($error) ?></div>
      <?php endif; ?>

      <form method="post" autocomplete="off">
        <div class="mb-3">
          <label class="form-label">Логин</label>
          <input type="text" name="login" class="form-control" required autofocus value="<?= h($_POST['login'] ?? '') ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">Пароль</label>
          <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Войти</button>
      </form>

      <div class="mt-4 small text-muted">
        <code>admin / admin123</code><br>
        <code>hr / hr123</code>
      </div>
    </div>
  </div>
</div>
<?php layout_foot(); ?>
