<?php
declare(strict_types=1);

namespace MareDiSakai\Inventory\Infrastructure\Repository;

use DateTimeImmutable;
use MareDiSakai\Inventory\Domain\Entity\Item;
use MareDiSakai\Inventory\Domain\ValueObject\ItemCode;
use MareDiSakai\Inventory\Domain\ValueObject\Quantity;
use PDO;

final class PdoItemRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @return Item[]
     */
    public function all(string $search = ''): array
    {
        $search = trim($search);

        if ($search === '') {
            $statement = $this->pdo->query('
                SELECT id, sku, name, barcode, unit, current_stock_qty, is_active, created_at
                FROM items
                ORDER BY is_active DESC, name ASC
            ');
        } else {
            $statement = $this->pdo->prepare('
                SELECT id, sku, name, barcode, unit, current_stock_qty, is_active, created_at
                FROM items
                WHERE sku LIKE :search
                   OR name LIKE :search
                   OR barcode LIKE :search
                ORDER BY is_active DESC, name ASC
            ');
            $statement->execute(['search' => '%' . $search . '%']);
        }

        $items = [];
        foreach ($statement->fetchAll() as $row) {
            $items[] = $this->hydrate($row);
        }

        return $items;
    }

    public function allActive(): array
    {
        $statement = $this->pdo->query('
            SELECT id, sku, name, barcode, unit, current_stock_qty, is_active, created_at
            FROM items
            WHERE is_active = 1
            ORDER BY name ASC
        ');

        $items = [];
        foreach ($statement->fetchAll() as $row) {
            $items[] = $this->hydrate($row);
        }

        return $items;
    }

    public function countActive(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM items WHERE is_active = 1')->fetchColumn();
    }

    public function findById(int $id): ?Item
    {
        $statement = $this->pdo->prepare('
            SELECT id, sku, name, barcode, unit, current_stock_qty, is_active, created_at
            FROM items
            WHERE id = :id
            LIMIT 1
        ');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function save(Item $item): Item
    {
        $barcode = $item->barcode();
        $barcode = $barcode !== null && $barcode !== '' ? $barcode : null;

        if ($item->id() === null) {
            $statement = $this->pdo->prepare('
                INSERT INTO items (sku, name, barcode, unit, current_stock_qty, is_active, created_at, updated_at)
                VALUES (:sku, :name, :barcode, :unit, :current_stock_qty, :is_active, :created_at, :created_at)
            ');
            $statement->execute([
                'sku' => $item->sku()->value(),
                'name' => $item->name(),
                'barcode' => $barcode,
                'unit' => $item->unit(),
                'current_stock_qty' => $item->currentStock()->toFloat(),
                'is_active' => $item->isActive() ? 1 : 0,
                'created_at' => $item->createdAt(),
            ]);

            return $item->withId((int) $this->pdo->lastInsertId());
        }

        $statement = $this->pdo->prepare('
            UPDATE items
            SET sku = :sku,
                name = :name,
                barcode = :barcode,
                unit = :unit,
                current_stock_qty = :current_stock_qty,
                is_active = :is_active,
                updated_at = NOW()
            WHERE id = :id
        ');
        $statement->execute([
            'id' => $item->id(),
            'sku' => $item->sku()->value(),
            'name' => $item->name(),
            'barcode' => $barcode,
            'unit' => $item->unit(),
            'current_stock_qty' => $item->currentStock()->toFloat(),
            'is_active' => $item->isActive() ? 1 : 0,
        ]);

        return $item;
    }

    public function updateStock(int $id, Quantity $stock): void
    {
        $statement = $this->pdo->prepare('
            UPDATE items
            SET current_stock_qty = :current_stock_qty,
                updated_at = NOW()
            WHERE id = :id
        ');
        $statement->execute([
            'id' => $id,
            'current_stock_qty' => $stock->toFloat(),
        ]);
    }

    public function delete(int $id): void
    {
        $statement = $this->pdo->prepare('
            UPDATE items
            SET is_active = 0,
                updated_at = NOW()
            WHERE id = :id
        ');
        $statement->execute(['id' => $id]);
    }

    private function hydrate(array $row): Item
    {
        return Item::reconstitute(
            (int) $row['id'],
            ItemCode::fromString((string) $row['sku']),
            (string) $row['name'],
            $row['barcode'] !== null ? (string) $row['barcode'] : null,
            (string) $row['unit'],
            Quantity::fromFloat((float) $row['current_stock_qty']),
            (int) $row['is_active'] === 1,
            (new DateTimeImmutable((string) $row['created_at']))->format('Y-m-d H:i:s')
        );
    }
}
