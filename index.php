<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
if (current_user()) {
    header('Location: /personnel_system/pages/employees.php');
} else {
    header('Location: /personnel_system/login.php');
}
exit;
