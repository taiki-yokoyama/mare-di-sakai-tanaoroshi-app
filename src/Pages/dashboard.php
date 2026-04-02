<?php
/** @var MareDiSakai\Inventory\Application\InventoryAppService $inventoryService */
$dashboard = $inventoryService->dashboard();
?>
<section class="hero-banner card">
    <div>
        <div class="eyebrow">Overview</div>
        <h1>Inventory management built for fast manual counts.</h1>
        <p class="lead">Count by hand, track differences, and close sessions with a clean adjustment trail.</p>
    </div>
    <div class="hero-actions">
        <a class="btn btn-primary" href="<?= e(url('sessions')) ?>">New session</a>
        <a class="btn btn-ghost" href="<?= e(url('items')) ?>">Manage items</a>
    </div>
</section>

<section class="stats-grid">
    <div class="stat-card">
        <div class="stat-value"><?= e((string) $dashboard['item_count']) ?></div>
        <div class="stat-label">Active items</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= e((string) $dashboard['open_session_count']) ?></div>
        <div class="stat-label">Open sessions</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= e((string) $dashboard['session_count']) ?></div>
        <div class="stat-label">Total sessions</div>
    </div>
</section>

<div class="grid grid-two">
    <section class="card">
        <h2>Quick actions</h2>
        <div class="stack">
            <a class="quick-link" href="<?= e(url('items')) ?>">
                <span>Items</span>
                <span class="muted small">Search and edit catalog</span>
            </a>
            <a class="quick-link" href="<?= e(url('sessions')) ?>">
                <span>Sessions</span>
                <span class="muted small">Start or continue counts</span>
            </a>
            <a class="quick-link" href="<?= e(url('users')) ?>">
                <span>Users</span>
                <span class="muted small">Manage logins</span>
            </a>
        </div>
    </section>

    <section class="card">
        <h2>Recent sessions</h2>
        <div class="stack">
            <?php foreach ($dashboard['recent_sessions'] as $sessionRow): ?>
                <?php
                $snapshotTotal = (int) $sessionRow['snapshot_total'];
                $countedTotal = (int) $sessionRow['counted_total'];
                $percent = progress_percent($countedTotal, $snapshotTotal);
                ?>
                <article class="card card-soft">
                    <div class="split">
                        <div>
                            <div class="card-title"><?= e((string) $sessionRow['name']) ?></div>
                            <div class="muted small"><?= e((string) $sessionRow['location_name']) ?></div>
                        </div>
                        <div class="<?= e(badge_class((string) $sessionRow['status'])) ?>"><?= e(session_status_label((string) $sessionRow['status'])) ?></div>
                    </div>
                    <div class="progress">
                        <div class="progress-bar" style="width: <?= e((string) $percent) ?>%"></div>
                    </div>
                    <div class="item-meta">
                        <span><?= e((string) $countedTotal) ?>/<?= e((string) $snapshotTotal) ?></span>
                        <span><?= e((string) $percent) ?>%</span>
                    </div>
                    <a class="btn btn-ghost btn-sm" href="<?= e(url('sessions', ['id' => (int) $sessionRow['id']])) ?>">Open</a>
                </article>
            <?php endforeach; ?>
            <?php if ($dashboard['recent_sessions'] === []): ?>
                <div class="empty-state">No sessions yet.</div>
            <?php endif; ?>
        </div>
    </section>
</div>

<div class="grid grid-two section-gap">
    <section class="card">
        <h2>Recent items</h2>
        <div class="stack">
            <?php foreach ($dashboard['items'] as $item): ?>
                <article class="card card-soft">
                    <div class="split">
                        <div>
                            <div class="card-title"><?= e($item->name()) ?></div>
                            <div class="muted small"><?= e($item->sku()->value()) ?></div>
                        </div>
                        <div class="badge"><?= e(format_quantity($item->currentStock()->toFloat())) ?></div>
                    </div>
                    <div class="item-meta">
                        <span>Unit: <?= e($item->unit()) ?></span>
                        <span><?= e($item->barcode() ?? '-') ?></span>
                    </div>
                </article>
            <?php endforeach; ?>
            <?php if ($dashboard['items'] === []): ?>
                <div class="empty-state">No items yet.</div>
            <?php endif; ?>
        </div>
    </section>

    <section class="card">
        <h2>Workflow notes</h2>
        <div class="stack">
            <div class="note-card">Create a session, snapshot current stock, and enter actual counts on the phone.</div>
            <div class="note-card">Use the cookie-based login to keep the team signed in across visits.</div>
            <div class="note-card">Close sessions only after every snapshot item has a recorded count.</div>
        </div>
    </section>
</div>
