<?php
declare(strict_types=1);

function render_layout(string $title, string $activePage, ?array $user, string $content): void
{
    $appName = app_name();
    $successMessage = flash_get('success');
    $errorMessage = flash_get('error');
    $isAuthenticated = $user !== null;

    ?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f766e">
    <title><?= e($title . ' - ' . $appName) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Noto+Sans+JP:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/app.css">
</head>
<body>
<div class="app-shell">
    <div class="app-background app-background-one"></div>
    <div class="app-background app-background-two"></div>

    <header class="topbar">
        <div class="brand">
            <div class="brand-mark">M</div>
            <div>
                <div class="brand-title"><?= e($appName) ?></div>
                <div class="brand-subtitle">モバイル優先の在庫棚卸し</div>
            </div>
        </div>

        <?php if ($isAuthenticated): ?>
            <div class="topbar-actions">
                <div class="user-pill">
                    <span class="user-name"><?= e($user['name']) ?></span>
                    <span class="user-role"><?= e(user_role_label((string) $user['role'])) ?></span>
                </div>
                <form method="post" action="<?= e(url('logout')) ?>">
                    <?= csrf_input() ?>
                    <input type="hidden" name="action" value="logout">
                    <button class="btn btn-ghost btn-sm" type="submit">ログアウト</button>
                </form>
            </div>
        <?php endif; ?>
    </header>

    <?php if ($isAuthenticated): ?>
        <nav class="nav-tabs">
            <a class="nav-tab<?= $activePage === 'dashboard' ? ' is-active' : '' ?>" href="<?= e(url('dashboard')) ?>">ダッシュボード</a>
            <a class="nav-tab<?= $activePage === 'items' ? ' is-active' : '' ?>" href="<?= e(url('items')) ?>">商品</a>
            <a class="nav-tab<?= $activePage === 'sessions' ? ' is-active' : '' ?>" href="<?= e(url('sessions')) ?>">セッション</a>
            <a class="nav-tab<?= $activePage === 'users' ? ' is-active' : '' ?>" href="<?= e(url('users')) ?>">ユーザー</a>
        </nav>
    <?php endif; ?>

    <main class="page">
        <?php if ($successMessage !== null): ?>
            <div class="notice notice-success"><?= e($successMessage) ?></div>
        <?php endif; ?>
        <?php if ($errorMessage !== null): ?>
            <div class="notice notice-error"><?= e($errorMessage) ?></div>
        <?php endif; ?>

        <?= $content ?>
    </main>
</div>
</body>
</html>
    <?php
}
