<?php
/** @var string $appName */
?>
<section class="auth-layout">
    <div class="hero card">
        <div class="eyebrow">棚卸し管理</div>
        <h1>モバイル向けに設計した手動棚卸し。</h1>
        <p class="lead">数量を手入力し、Cookie でログイン状態を長く保持し、各セッションをきれいな調整履歴付きで終了します。</p>
        <div class="feature-list">
            <div class="feature-item">モバイル優先のモダンな UI</div>
            <div class="feature-item">DDD ベースのドメインモデル</div>
            <div class="feature-item"><code>init.sql</code> で MySQL を初期化</div>
        </div>
    </div>

    <div class="card auth-card">
        <h2>ログイン</h2>
        <p class="muted"><code>init.sql</code> を取り込んだ後、初期アカウントでログインしてください。</p>
        <form method="post" action="<?= e(url('login')) ?>" class="form-stack">
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="login">
            <label class="field">
                <span class="field-label">メールアドレス</span>
                <input class="input" type="email" name="email" autocomplete="email" required>
            </label>
            <label class="field">
                <span class="field-label">パスワード</span>
                <input class="input" type="password" name="password" autocomplete="current-password" required>
            </label>
            <label class="checkbox">
                <input type="checkbox" name="remember" value="1" checked>
                <span>ログイン状態を長く保持する</span>
            </label>
            <button class="btn btn-primary btn-block" type="submit">ログイン</button>
        </form>
    </div>
</section>
