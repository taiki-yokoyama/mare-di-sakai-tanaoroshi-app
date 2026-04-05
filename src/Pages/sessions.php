<?php
/** @var MareDiSakai\Inventory\Application\InventoryAppService $inventoryService */
/** @var ?MareDiSakai\Inventory\Domain\Entity\User $currentUser */
$sessions = $inventoryService->listSessions();
$sessionId = request_int('id', 0);
$detail = $sessionId > 0 ? $inventoryService->sessionDetail($sessionId) : null;
?>
<section class="page-header">
    <div>
        <div class="eyebrow">棚卸し</div>
        <h1>在庫セッション</h1>
        <p class="muted">セッションを作成し、現在庫をスナップショットして、実測数量を手入力します。</p>
    </div>
</section>

<div class="grid grid-two">
    <section class="card">
        <h2>セッションを作成</h2>
        <form method="post" action="<?= e(url('sessions')) ?>" class="form-grid">
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="session-create">
            <label class="field">
                <span class="field-label">セッション名</span>
                <input class="input" type="text" name="name" placeholder="4月末棚卸し" required>
            </label>
            <label class="field">
                <span class="field-label">拠点</span>
                <input class="input" type="text" name="location_name" placeholder="本店" value="本店" required>
            </label>
            <label class="field">
                <span class="field-label">メモ</span>
                <textarea class="textarea" name="memo" rows="4" placeholder="任意のメモ"></textarea>
            </label>
            <button class="btn btn-primary" type="submit">セッションを作成</button>
        </form>
    </section>

    <section class="card">
        <h2>セッション一覧</h2>
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
                            <div class="muted small"><?= e((string) $sessionRow['location_name']) ?> ・ <?= e(human_datetime((string) $sessionRow['started_at'])) ?></div>
                        </div>
                        <div class="<?= e(badge_class((string) $sessionRow['status'])) ?>"><?= e(session_status_label((string) $sessionRow['status'])) ?></div>
                    </div>
                    <div class="progress">
                        <div class="progress-bar" style="width: <?= e((string) $percent) ?>%"></div>
                    </div>
                    <div class="item-meta">
                        <span><?= e((string) $countedTotal) ?>/<?= e((string) $snapshotTotal) ?> 件入力済み</span>
                        <span><?= e((string) $percent) ?>%</span>
                    </div>
                    <a class="btn btn-ghost btn-sm" href="<?= e(url('sessions', ['id' => (int) $sessionRow['id']])) ?>">詳細</a>
                </article>
            <?php endforeach; ?>
            <?php if ($sessions === []): ?>
                <div class="empty-state">まだセッションはありません。</div>
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
                <div class="eyebrow">セッション詳細</div>
                <h2><?= e($session->name()) ?></h2>
                <div class="muted small"><?= e($session->locationName()) ?> ・ 開始 <?= e(human_datetime($session->startedAt())) ?></div>
            </div>
            <div class="<?= e(badge_class($session->status())) ?>"><?= e(session_status_label($session->status())) ?></div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?= e((string) $detail['counted_count']) ?></div>
                <div class="stat-label">入力済み数</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= e((string) $detail['snapshot_count']) ?></div>
                <div class="stat-label">スナップショット件数</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= e((string) progress_percent((int) $detail['counted_count'], (int) $detail['snapshot_count'])) ?>%</div>
                <div class="stat-label">進捗</div>
            </div>
        </div>

        <div class="stack">
            <?php foreach ($detail['rows'] as $row): ?>
                <article class="card card-soft">
                    <div class="split">
                        <div>
                            <div class="card-title"><?= e($row['name']) ?></div>
                            <div class="muted small"><?= e($row['sku']) ?><?php if (!empty($row['barcode'])): ?> ・ <?= e((string) $row['barcode']) ?><?php endif; ?></div>
                        </div>
                        <div class="badge badge-muted">予定 <?= e(format_quantity((float) $row['expected_qty'])) ?></div>
                    </div>
                    <form method="post" action="<?= e(url('sessions', ['id' => $session->id()])) ?>" class="count-form">
                        <?= csrf_input() ?>
                        <input type="hidden" name="action" value="count-save">
                        <input type="hidden" name="session_id" value="<?= e((string) $session->id()) ?>">
                        <input type="hidden" name="item_id" value="<?= e((string) $row['item_id']) ?>">
                        <label class="field">
                            <span class="field-label">入力数量</span>
                            <input class="input" type="number" step="0.001" min="0" name="counted_qty" value="<?= e($row['counted_qty'] === null ? '' : format_quantity((float) $row['counted_qty'])) ?>" <?= $session->isOpen() ? '' : 'disabled' ?> required>
                        </label>
                        <div class="item-meta">
                            <span>差分: <?= e($row['difference_qty'] === null ? '-' : format_quantity((float) $row['difference_qty'])) ?></span>
                            <span>保存日時: <?= e($row['counted_at'] ?? '-') ?></span>
                        </div>
                        <div class="actions-row">
                            <button class="btn btn-secondary btn-sm" type="submit" <?= $session->isOpen() ? '' : 'disabled' ?>>数量を保存</button>
                        </div>
                    </form>
                </article>
            <?php endforeach; ?>
            <?php if ($detail['rows'] === []): ?>
                <div class="empty-state">このセッションにはスナップショットされた商品がありません。</div>
            <?php endif; ?>
        </div>

        <div class="actions-row section-gap">
            <?php if ($session->isOpen()): ?>
                <form method="post" action="<?= e(url('sessions', ['id' => $session->id()])) ?>">
                    <?= csrf_input() ?>
                    <input type="hidden" name="action" value="session-close">
                    <input type="hidden" name="session_id" value="<?= e((string) $session->id()) ?>">
                    <button class="btn btn-primary" type="submit" <?= $detail['is_complete'] ? '' : 'disabled' ?>>セッションを終了</button>
                </form>
                <?php if (!$detail['is_complete']): ?>
                    <div class="notice notice-warning">終了する前にすべての数量を保存してください。</div>
                <?php endif; ?>
            <?php else: ?>
                <div class="notice notice-success">このセッションはロックされています。</div>
            <?php endif; ?>
        </div>
    </section>
<?php endif; ?>
