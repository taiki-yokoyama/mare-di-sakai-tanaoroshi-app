<?php
/** @var MareDiSakai\Inventory\Application\InventoryAppService $inventoryService */
$dashboard = $inventoryService->dashboard();
?>
<section class="hero-banner card">
    <div>
        <div class="eyebrow">概要</div>
        <h1>素早い手入力棚卸しのための在庫管理。</h1>
        <p class="lead">手入力で数量を記録し、差分を追跡し、調整履歴を残してセッションを終了します。</p>
    </div>
    <div class="hero-actions">
        <a class="btn btn-primary" href="<?= e(url('sessions')) ?>">新規セッション</a>
        <a class="btn btn-ghost" href="<?= e(url('items')) ?>">商品管理</a>
    </div>
</section>

<section class="stats-grid">
    <div class="stat-card">
        <div class="stat-value"><?= e((string) $dashboard['item_count']) ?></div>
        <div class="stat-label">有効商品数</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= e((string) $dashboard['open_session_count']) ?></div>
        <div class="stat-label">進行中セッション数</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= e((string) $dashboard['session_count']) ?></div>
        <div class="stat-label">セッション総数</div>
    </div>
</section>

<div class="grid grid-two">
    <section class="card">
        <h2>クイック操作</h2>
        <div class="stack">
            <a class="quick-link" href="<?= e(url('items')) ?>">
                <span>商品</span>
                <span class="muted small">商品一覧を検索・編集</span>
            </a>
            <a class="quick-link" href="<?= e(url('sessions')) ?>">
                <span>セッション</span>
                <span class="muted small">棚卸しを開始・継続</span>
            </a>
            <a class="quick-link" href="<?= e(url('users')) ?>">
                <span>ユーザー</span>
                <span class="muted small">ログインを管理</span>
            </a>
        </div>
    </section>

    <section class="card">
        <h2>最近のセッション</h2>
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
                    <a class="btn btn-ghost btn-sm" href="<?= e(url('sessions', ['id' => (int) $sessionRow['id']])) ?>">開く</a>
                </article>
            <?php endforeach; ?>
            <?php if ($dashboard['recent_sessions'] === []): ?>
                <div class="empty-state">まだセッションはありません。</div>
            <?php endif; ?>
        </div>
    </section>
</div>

<div class="grid grid-two section-gap">
    <section class="card">
        <h2>最近の商品</h2>
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
                        <span>単位: <?= e(unit_label($item->unit())) ?></span>
                        <span><?= e($item->barcode() ?? '-') ?></span>
                    </div>
                </article>
            <?php endforeach; ?>
            <?php if ($dashboard['items'] === []): ?>
                <div class="empty-state">まだ商品はありません。</div>
            <?php endif; ?>
        </div>
    </section>

    <section class="card">
        <h2>運用メモ</h2>
        <div class="stack">
            <div class="note-card">セッションを作成し、現在庫をスナップショットして、スマホで実測値を入力します。</div>
            <div class="note-card">Cookie ベースのログインで、チームのログイン状態を維持します。</div>
            <div class="note-card">スナップショットした全商品に数量が入ってからセッションを終了してください。</div>
        </div>
    </section>
</div>
