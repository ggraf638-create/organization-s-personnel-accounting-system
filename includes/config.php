<?php
declare(strict_types=1);

return [
    'db' => [
        'host'     => '127.0.0.1',
        'port'     => 3306,
        'name'     => 'personnel_db',
        'user'     => 'root',
        'password' => '',
        'charset'  => 'utf8mb4',
    ],
    'app' => [
        'title'        => 'Информационная система учёта кадров',
        'monthly_norm' => 22,
        'combine_rate' => 0.15,
    ],
];
