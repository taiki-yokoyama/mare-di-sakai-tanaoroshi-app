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
        <div class="eyebrow">Catalog</div>
        <h1>Item master</h1>
        <p class="muted">Search, add, update, and soft-delete items without leaving the mobile flow.</p>
    </div>
    <a class="btn btn-ghost" href="<?= e(url('items')) ?>">Reset search</a>
</section>

<div class="grid grid-two">
    <section class="card">
        <h2><?= $editingItem ? 'Edit item' : 'Add item' ?></h2>
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
                <span class="field-label">Name</span>
                <input class="input" type="text" name="name" value="<?= e($editingItem ? $editingItem->name() : '') ?>" required>
            </label>
            <label class="field">
                <span class="field-label">Barcode</span>
                <input class="input" type="text" name="barcode" value="<?= e($editingItem && $editingItem->barcode() ? $editingItem->barcode() : '') ?>">
            </label>
            <label class="field">
                <span class="field-label">Unit</span>
                <input class="input" type="text" name="unit" value="<?= e($editingItem ? $editingItem->unit() : 'pcs') ?>" required>
            </label>
            <label class="field">
                <span class="field-label">Current stock</span>
                <input class="input" type="number" name="current_stock_qty" step="0.001" min="0" value="<?= e($editingItem ? format_quantity($editingItem->currentStock()->toFloat()) : '0') ?>" required>
            </label>
            <div class="actions-row">
                <button class="btn btn-primary" type="submit"><?= $editingItem ? 'Update item' : 'Create item' ?></button>
                <?php if ($editingItem): ?>
                    <a class="btn btn-ghost" href="<?= e(url('items')) ?>">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section class="card">
        <h2>Search</h2>
        <form method="get" action="<?= e(url('items')) ?>" class="form-row">
            <input type="hidden" name="page" value="items">
            <input class="input" type="search" name="search" value="<?= e($search) ?>" placeholder="SKU, name, barcode">
            <button class="btn btn-secondary" type="submit">Search</button>
        </form>
        <div class="spacer"></div>
        <div class="stack">
            <?php foreach ($items as $item): ?>
                <article class="card card-soft">
                    <div class="split">
                        <div>
                            <div class="card-title"><?= e($item->name()) ?></div>
                            <div class="muted small"><?= e($item->sku()->value()) ?><?php if ($item->barcode()): ?> · <?= e($item->barcode()) ?><?php endif; ?></div>
                        </div>
                        <div class="badge"><?= e($item->isActive() ? 'Active' : 'Inactive') ?></div>
                    </div>
                    <div class="item-meta">
                        <span>Unit: <?= e($item->unit()) ?></span>
                        <span>Stock: <?= e(format_quantity($item->currentStock()->toFloat())) ?></span>
                    </div>
                    <div class="actions-row">
                        <a class="btn btn-ghost btn-sm" href="<?= e(url('items', ['edit' => $item->id()])) ?>">Edit</a>
                        <form method="post" action="<?= e(url('items')) ?>">
                            <?= csrf_input() ?>
                            <input type="hidden" name="action" value="item-delete">
                            <input type="hidden" name="item_id" value="<?= e((string) $item->id()) ?>">
                            <button class="btn btn-danger btn-sm" type="submit" onclick="return confirm('Delete this item?')">Delete</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
            <?php if ($items === []): ?>
                <div class="empty-state">No items found.</div>
            <?php endif; ?>
        </div>
    </section>
</div>
