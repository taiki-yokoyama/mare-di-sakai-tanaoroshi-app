<?php
declare(strict_types=1);

function app_config(): array
{
    static $config = null;

    if ($config === null) {
        $config = require dirname(__DIR__, 2) . '/config/app.php';
    }

    return $config;
}

function app_name(): string
{
    $config = app_config();

    return (string) ($config['app_name'] ?? 'mare-di-sakai-tanaoroshi-app');
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function url(string $page, array $query = []): string
{
    $params = array_merge(['page' => $page], $query);

    return '?' . http_build_query($params);
}

function flash_set(string $key, string $message): void
{
    $_SESSION['flash'][$key] = $message;
}

function flash_get(string $key): ?string
{
    if (!isset($_SESSION['flash'][$key])) {
        return null;
    }

    $message = (string) $_SESSION['flash'][$key];
    unset($_SESSION['flash'][$key]);

    return $message;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['csrf_token'];
}

function csrf_input(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        return;
    }

    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        throw new RuntimeException('Invalid CSRF token.');
    }
}

function redirect_to(string $target): void
{
    header('Location: ' . $target);
    exit;
}

function request_string(string $key, string $default = ''): string
{
    $value = $_POST[$key] ?? $_GET[$key] ?? $default;
    if (is_string($value)) {
        return trim($value);
    }

    return $default;
}

function request_int(string $key, int $default = 0): int
{
    $value = $_POST[$key] ?? $_GET[$key] ?? $default;

    if (is_numeric($value)) {
        return (int) $value;
    }

    return $default;
}

function request_bool(string $key): bool
{
    $value = $_POST[$key] ?? $_GET[$key] ?? null;

    return in_array($value, ['1', 1, true, 'true', 'on'], true);
}

function format_quantity(float $value): string
{
    $formatted = number_format(round($value, 3), 3, '.', '');
    $formatted = rtrim(rtrim($formatted, '0'), '.');

    return $formatted === '' ? '0' : $formatted;
}

function human_datetime(?string $value): string
{
    if ($value === null || $value === '') {
        return '-';
    }

    return date('Y-m-d H:i', strtotime($value));
}

function clear_cookie(string $name): void
{
    setcookie($name, '', [
        'expires' => time() - 3600,
        'path' => '/',
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
    ]);
}

function set_cookie(string $name, string $value, int $expiresAt): void
{
    setcookie($name, $value, [
        'expires' => $expiresAt,
        'path' => '/',
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
    ]);
}
