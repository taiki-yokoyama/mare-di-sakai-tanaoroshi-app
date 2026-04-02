<?php
/** @var MareDiSakai\Inventory\Application\InventoryAppService $inventoryService */
/** @var ?MareDiSakai\Inventory\Domain\Entity\User $currentUser */
$users = $inventoryService->listUsers();
?>
<section class="page-header">
    <div>
        <div class="eyebrow">Access</div>
        <h1>Users</h1>
        <p class="muted">Simple login accounts with long-lived cookie sessions.</p>
    </div>
</section>

<div class="grid grid-two">
    <section class="card">
        <h2>Create user</h2>
        <form method="post" action="<?= e(url('users')) ?>" class="form-grid">
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="user-save">
            <label class="field">
                <span class="field-label">Name</span>
                <input class="input" type="text" name="name" required>
            </label>
            <label class="field">
                <span class="field-label">Email</span>
                <input class="input" type="email" name="email" required>
            </label>
            <label class="field">
                <span class="field-label">Password</span>
                <input class="input" type="password" name="password" required>
            </label>
            <button class="btn btn-primary" type="submit">Create user</button>
        </form>
    </section>

    <section class="card">
        <h2>Registered users</h2>
        <div class="stack">
            <?php foreach ($users as $user): ?>
                <article class="card card-soft">
                    <div class="split">
                        <div>
                            <div class="card-title"><?= e($user->name()) ?></div>
                            <div class="muted small"><?= e($user->email()->value()) ?></div>
                        </div>
                        <div class="badge"><?= e($user->role()) ?></div>
                    </div>
                    <div class="item-meta">
                        <span>Created: <?= e(human_datetime($user->createdAt())) ?></span>
                        <span>ID: <?= e((string) $user->id()) ?></span>
                    </div>
                    <?php if ($currentUser !== null && (int) $currentUser->id() !== (int) $user->id()): ?>
                        <form method="post" action="<?= e(url('users')) ?>" class="actions-row">
                            <?= csrf_input() ?>
                            <input type="hidden" name="action" value="user-delete">
                            <input type="hidden" name="user_id" value="<?= e((string) $user->id()) ?>">
                            <button class="btn btn-danger btn-sm" type="submit" onclick="return confirm('Delete this user?')">Delete</button>
                        </form>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
            <?php if ($users === []): ?>
                <div class="empty-state">No users yet.</div>
            <?php endif; ?>
        </div>
    </section>
</div>
