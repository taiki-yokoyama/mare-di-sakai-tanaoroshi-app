<?php
declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $prefix = 'MareDiSakai\\Inventory\\';
    $baseDir = __DIR__ . '/';

    $prefixLength = strlen($prefix);
    if (strncmp($class, $prefix, $prefixLength) !== 0) {
        return;
    }

    $relativeClass = substr($class, $prefixLength);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (is_file($file)) {
        require $file;
    }
});

require_once __DIR__ . '/Support/functions.php';
require_once __DIR__ . '/Support/View.php';
require_once __DIR__ . '/Support/view_helpers.php';

date_default_timezone_set('Asia/Tokyo');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
