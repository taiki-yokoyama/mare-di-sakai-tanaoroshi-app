<?php
/** @var MareDiSakai\Inventory\Application\InventoryAppService $inventoryService */
/** @var ?MareDiSakai\Inventory\Domain\Entity\User $currentUser */
$sessions = $inventoryService->listSessions();
$sessionId = request_int('id', 0);
$detail = $sessionId > 0 ? $inventoryService->sessionDetail($sessionId) : null;
?>
<section class="page-header">
    <div>
        <div class="eyebrow">Counting</div>
        <h1>Inventory sessions</h1>
        <p class="muted">Create a session, snapshot the current stock, then enter counted quantities by hand.</p>
    </div>
</section>

<div class="grid grid-two">
    <section class="card">
        <h2>Create session</h2>
        <form method="post" action="<?= e(url('sessions')) ?>" class="form-grid">
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="session-create">
            <label class="field">
                <span class="field-label">Session name</span>
                <input class="input" type="text" name="name" placeholder="April closing count" required>
            </label>
            <label class="field">
                <span class="field-label">Location</span>
                <input class="input" type="text" name="location_name" placeholder="Main store" value="Main" required>
            </label>
            <label class="field">
                <span class="field-label">Memo</span>
                <textarea class="textarea" name="memo" rows="4" placeholder="Optional note"></textarea>
            </label>
            <button class="btn btn-primary" type="submit">Create session</button>
        </form>
    </section>

    <section class="card">
        <h2>Session list</h2>
        <div class="stack">
            <?php foreach ($sessions as $sessionRow): ?>
                <?php
                $snapshotTotal = (int) $sessionRow['snapshot_total'];
                $countedTotal = (int) $sessionRow['counted_total'];
                $percent = progress_percent($countedTotal, $snapshotTotal);
                ?>
                <article class="card card-soft">
                    <div class="split">
                        <div>
                            <div class="card-title"><?= e((string) $sessionRow['name']) ?></div>
                            <div class="muted small"><?= e((string) $sessionRow['location_name']) ?> · <?= e(human_datetime((string) $sessionRow['started_at'])) ?></div>
                        </div>
                        <div class="<?= e(badge_class((string) $sessionRow['status'])) ?>"><?= e(session_status_label((string) $sessionRow['status'])) ?></div>
                    </div>
                    <div class="progress">
                        <div class="progress-bar" style="width: <?= e((string) $percent) ?>%"></div>
                    </div>
                    <div class="item-meta">
                        <span><?= e((string) $countedTotal) ?>/<?= e((string) $snapshotTotal) ?> counted</span>
                        <span><?= e((string) $percent) ?>%</span>
                    </div>
                    <a class="btn btn-ghost btn-sm" href="<?= e(url('sessions', ['id' => (int) $sessionRow['id']])) ?>">View</a>
                </article>
            <?php endforeach; ?>
            <?php if ($sessions === []): ?>
                <div class="empty-state">No sessions yet.</div>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php if ($detail): ?>
    <?php
    /** @var MareDiSakai\Inventory\Domain\Entity\InventorySession $session */
    $session = $detail['session'];
    ?>
    <section class="card section-gap">
        <div class="split">
            <div>
                <div class="eyebrow">Session detail</div>
                <h2><?= e($session->name()) ?></h2>
                <div class="muted small"><?= e($session->locationName()) ?> · started <?= e(human_datetime($session->startedAt())) ?></div>
            </div>
            <div class="<?= e(badge_class($session->status())) ?>"><?= e(session_status_label($session->status())) ?></div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?= e((string) $detail['counted_count']) ?></div>
                <div class="stat-label">Counted</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= e((string) $detail['snapshot_count']) ?></div>
                <div class="stat-label">Snapshot items</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= e((string) progress_percent((int) $detail['counted_count'], (int) $detail['snapshot_count'])) ?>%</div>
                <div class="stat-label">Progress</div>
            </div>
        </div>

        <div class="stack">
            <?php foreach ($detail['rows'] as $row): ?>
                <article class="card card-soft">
                    <div class="split">
                        <div>
                            <div class="card-title"><?= e($row['name']) ?></div>
                            <div class="muted small"><?= e($row['sku']) ?><?php if (!empty($row['barcode'])): ?> · <?= e((string) $row['barcode']) ?><?php endif; ?></div>
                        </div>
                        <div class="badge badge-muted">Expected <?= e(format_quantity((float) $row['expected_qty'])) ?></div>
                    </div>
                    <form method="post" action="<?= e(url('sessions', ['id' => $session->id()])) ?>" class="count-form">
                        <?= csrf_input() ?>
                        <input type="hidden" name="action" value="count-save">
                        <input type="hidden" name="session_id" value="<?= e((string) $session->id()) ?>">
                        <input type="hidden" name="item_id" value="<?= e((string) $row['item_id']) ?>">
                        <label class="field">
                            <span class="field-label">Counted quantity</span>
                            <input class="input" type="number" step="0.001" min="0" name="counted_qty" value="<?= e($row['counted_qty'] === null ? '' : format_quantity((float) $row['counted_qty'])) ?>" <?= $session->isOpen() ? '' : 'disabled' ?> required>
                        </label>
                        <div class="item-meta">
                            <span>Difference: <?= e($row['difference_qty'] === null ? '-' : format_quantity((float) $row['difference_qty'])) ?></span>
                            <span>Saved at: <?= e($row['counted_at'] ?? '-') ?></span>
                        </div>
                        <div class="actions-row">
                            <button class="btn btn-secondary btn-sm" type="submit" <?= $session->isOpen() ? '' : 'disabled' ?>>Save count</button>
                        </div>
                    </form>
                </article>
            <?php endforeach; ?>
            <?php if ($detail['rows'] === []): ?>
                <div class="empty-state">No items were snapshotted for this session.</div>
            <?php endif; ?>
        </div>

        <div class="actions-row section-gap">
            <?php if ($session->isOpen()): ?>
                <form method="post" action="<?= e(url('sessions', ['id' => $session->id()])) ?>">
                    <?= csrf_input() ?>
                    <input type="hidden" name="action" value="session-close">
                    <input type="hidden" name="session_id" value="<?= e((string) $session->id()) ?>">
                    <button class="btn btn-primary" type="submit" <?= $detail['is_complete'] ? '' : 'disabled' ?>>Close session</button>
                </form>
                <?php if (!$detail['is_complete']): ?>
                    <div class="notice notice-warning">All counts must be saved before closing.</div>
                <?php endif; ?>
            <?php else: ?>
                <div class="notice notice-success">This session is locked.</div>
            <?php endif; ?>
        </div>
    </section>
<?php endif; ?>
