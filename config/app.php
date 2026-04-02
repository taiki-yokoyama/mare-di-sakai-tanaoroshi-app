<?php
declare(strict_types=1);

return [
    'app_name' => 'mare-di-sakai-tanaoroshi-app',
    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'database' => 'mare_di_sakai',
        'username' => 'root',
        'password' => '',
    ],
    'remember_cookie' => [
        'name' => 'mare_di_sakai_remember',
        'days' => 180,
    ],
    'security' => [
        'password_iterations' => 100000,
    ],
];
