<?php
/** @var MareDiSakai\Inventory\Application\InventoryAppService $inventoryService */
/** @var ?MareDiSakai\Inventory\Domain\Entity\User $currentUser */
$users = $inventoryService->listUsers();
?>
<section class="page-header">
    <div>
        <div class="eyebrow">権限</div>
        <h1>ユーザー</h1>
        <p class="muted">Cookie でログイン状態を長く保持するシンプルなログインアカウントです。</p>
    </div>
</section>

<div class="grid grid-two">
    <section class="card">
        <h2>ユーザーを作成</h2>
        <form method="post" action="<?= e(url('users')) ?>" class="form-grid">
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="user-save">
            <label class="field">
                <span class="field-label">名前</span>
                <input class="input" type="text" name="name" required>
            </label>
            <label class="field">
                <span class="field-label">メールアドレス</span>
                <input class="input" type="email" name="email" required>
            </label>
            <label class="field">
                <span class="field-label">パスワード</span>
                <input class="input" type="password" name="password" required>
            </label>
            <button class="btn btn-primary" type="submit">ユーザーを作成</button>
        </form>
    </section>

    <section class="card">
        <h2>登録済みユーザー</h2>
        <div class="stack">
            <?php foreach ($users as $user): ?>
                <article class="card card-soft">
                    <div class="split">
                        <div>
                            <div class="card-title"><?= e($user->name()) ?></div>
                            <div class="muted small"><?= e($user->email()->value()) ?></div>
                        </div>
                        <div class="badge"><?= e(user_role_label($user->role())) ?></div>
                    </div>
                    <div class="item-meta">
                        <span>作成日時: <?= e(human_datetime($user->createdAt())) ?></span>
                        <span>ID: <?= e((string) $user->id()) ?></span>
                    </div>
                    <?php if ($currentUser !== null && (int) $currentUser->id() !== (int) $user->id()): ?>
                        <form method="post" action="<?= e(url('users')) ?>" class="actions-row">
                            <?= csrf_input() ?>
                            <input type="hidden" name="action" value="user-delete">
                            <input type="hidden" name="user_id" value="<?= e((string) $user->id()) ?>">
                            <button class="btn btn-danger btn-sm" type="submit" onclick="return confirm('このユーザーを削除しますか？')">削除</button>
                        </form>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
            <?php if ($users === []): ?>
                <div class="empty-state">まだユーザーはありません。</div>
            <?php endif; ?>
        </div>
    </section>
</div>
