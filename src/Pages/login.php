<?php
/** @var string $appName */
?>
<section class="auth-layout">
    <div class="hero card">
        <div class="eyebrow">Inventory counts</div>
        <h1>Manual stock counting, designed for mobile.</h1>
        <p class="lead">Hand-enter quantities, keep a long-lived login via cookie, and close each session with a clean adjustment trail.</p>
        <div class="feature-list">
            <div class="feature-item">Mobile-first, modern UI</div>
            <div class="feature-item">DDD-oriented domain model</div>
            <div class="feature-item">MySQL init via <code>init.sql</code></div>
        </div>
    </div>

    <div class="card auth-card">
        <h2>Sign in</h2>
        <p class="muted">Use the seeded account from <code>init.sql</code> after import.</p>
        <form method="post" action="<?= e(url('login')) ?>" class="form-stack">
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="login">
            <label class="field">
                <span class="field-label">Email</span>
                <input class="input" type="email" name="email" autocomplete="email" required>
            </label>
            <label class="field">
                <span class="field-label">Password</span>
                <input class="input" type="password" name="password" autocomplete="current-password" required>
            </label>
            <label class="checkbox">
                <input type="checkbox" name="remember" value="1" checked>
                <span>Keep me logged in for a long time</span>
            </label>
            <button class="btn btn-primary btn-block" type="submit">Login</button>
        </form>
    </div>
</section>
