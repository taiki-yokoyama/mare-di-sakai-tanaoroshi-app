<?php
/** @var MareDiSakai\Inventory\Application\InventoryAppService $inventoryService */
/** @var ?MareDiSakai\Inventory\Domain\Entity\User $currentUser */
$search = request_string('search', '');
$items = $inventoryService->listItems($search);
$editId = request_int('edit', 0);
$editingItem = null;
foreach ($items as $candidate) {
    if ($candidate->id() === $editId) {
        $editingItem = $candidate;
        break;
    }
}
?>
<section class="page-header">
    <div>
        <div class="eyebrow">商品</div>
        <h1>商品マスタ</h1>
        <p class="muted">モバイル画面のまま、商品を検索・追加・更新・論理削除できます。</p>
    </div>
    <a class="btn btn-ghost" href="<?= e(url('items')) ?>">検索をリセット</a>
</section>

<div class="grid grid-two">
    <section class="card">
        <h2><?= $editingItem ? '商品を編集' : '商品を追加' ?></h2>
        <form method="post" action="<?= e(url('items')) ?>" class="form-grid">
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="item-save">
            <?php if ($editingItem): ?>
                <input type="hidden" name="item_id" value="<?= e((string) $editingItem->id()) ?>">
            <?php endif; ?>
            <label class="field">
                <span class="field-label">SKU</span>
                <input class="input" type="text" name="sku" value="<?= e($editingItem ? $editingItem->sku()->value() : '') ?>" required>
            </label>
            <label class="field">
                <span class="field-label">商品名</span>
                <input class="input" type="text" name="name" value="<?= e($editingItem ? $editingItem->name() : '') ?>" required>
            </label>
            <label class="field">
                <span class="field-label">バーコード</span>
                <input class="input" type="text" name="barcode" value="<?= e($editingItem && $editingItem->barcode() ? $editingItem->barcode() : '') ?>">
            </label>
            <label class="field">
                <span class="field-label">単位</span>
                <input class="input" type="text" name="unit" value="<?= e($editingItem ? $editingItem->unit() : '個') ?>" required>
            </label>
            <label class="field">
                <span class="field-label">現在在庫</span>
                <input class="input" type="number" name="current_stock_qty" step="0.001" min="0" value="<?= e($editingItem ? format_quantity($editingItem->currentStock()->toFloat()) : '0') ?>" required>
            </label>
            <div class="actions-row">
                <button class="btn btn-primary" type="submit"><?= $editingItem ? '商品を更新' : '商品を作成' ?></button>
                <?php if ($editingItem): ?>
                    <a class="btn btn-ghost" href="<?= e(url('items')) ?>">キャンセル</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section class="card">
        <h2>検索</h2>
        <form method="get" action="<?= e(url('items')) ?>" class="form-row">
            <input type="hidden" name="page" value="items">
            <input class="input" type="search" name="search" value="<?= e($search) ?>" placeholder="SKU、商品名、バーコード">
            <button class="btn btn-secondary" type="submit">検索</button>
        </form>
        <div class="spacer"></div>
        <div class="stack">
            <?php foreach ($items as $item): ?>
                <article class="card card-soft">
                    <div class="split">
                        <div>
                            <div class="card-title"><?= e($item->name()) ?></div>
                            <div class="muted small"><?= e($item->sku()->value()) ?><?php if ($item->barcode()): ?> ・ <?= e($item->barcode()) ?><?php endif; ?></div>
                        </div>
                        <div class="badge"><?= e($item->isActive() ? '有効' : '無効') ?></div>
                    </div>
                    <div class="item-meta">
                        <span>単位: <?= e(unit_label($item->unit())) ?></span>
                        <span>在庫: <?= e(format_quantity($item->currentStock()->toFloat())) ?></span>
                    </div>
                    <div class="actions-row">
                        <a class="btn btn-ghost btn-sm" href="<?= e(url('items', ['edit' => $item->id()])) ?>">編集</a>
                        <form method="post" action="<?= e(url('items')) ?>">
                            <?= csrf_input() ?>
                            <input type="hidden" name="action" value="item-delete">
                            <input type="hidden" name="item_id" value="<?= e((string) $item->id()) ?>">
                            <button class="btn btn-danger btn-sm" type="submit" onclick="return confirm('この商品を削除しますか？')">削除</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
            <?php if ($items === []): ?>
                <div class="empty-state">該当する商品はありません。</div>
            <?php endif; ?>
        </div>
    </section>
</div>
