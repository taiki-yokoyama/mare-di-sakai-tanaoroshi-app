<?php
declare(strict_types=1);

namespace MareDiSakai\Inventory\Infrastructure\Repository;

use DateTimeImmutable;
use MareDiSakai\Inventory\Domain\Entity\InventorySession;
use MareDiSakai\Inventory\Domain\ValueObject\Quantity;
use PDO;

final class PdoInventorySessionRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function allSessions(): array
    {
        $statement = $this->pdo->query('
            SELECT
                s.*,
                (SELECT COUNT(*) FROM stock_snapshots ss WHERE ss.session_id = s.id) AS snapshot_total,
                (SELECT COUNT(*) FROM inventory_counts ic WHERE ic.session_id = s.id) AS counted_total
            FROM inventory_sessions s
            ORDER BY s.created_at DESC
        ');

        return $statement->fetchAll();
    }

    public function countOpenSessions(): int
    {
        return (int) $this->pdo->query("SELECT COUNT(*) FROM inventory_sessions WHERE status = 'open'")->fetchColumn();
    }

    public function findById(int $id): ?InventorySession
    {
        $statement = $this->pdo->prepare('
            SELECT id, name, location_name, status, created_by_user_id, memo, started_at, closed_at
            FROM inventory_sessions
            WHERE id = :id
            LIMIT 1
        ');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        if (!$row) {
            return null;
        }

        return InventorySession::reconstitute(
            (int) $row['id'],
            (string) $row['name'],
            (string) $row['location_name'],
            (string) $row['status'],
            (int) $row['created_by_user_id'],
            (string) $row['memo'],
            (new DateTimeImmutable((string) $row['started_at']))->format('Y-m-d H:i:s'),
            $row['closed_at'] !== null ? (new DateTimeImmutable((string) $row['closed_at']))->format('Y-m-d H:i:s') : null
        );
    }

    public function save(InventorySession $session): InventorySession
    {
        if ($session->id() === null) {
            $statement = $this->pdo->prepare('
                INSERT INTO inventory_sessions (name, location_name, status, created_by_user_id, memo, started_at, closed_at, created_at, updated_at)
                VALUES (:name, :location_name, :status, :created_by_user_id, :memo, :started_at, :closed_at, :created_at, :created_at)
            ');
            $statement->execute([
                'name' => $session->name(),
                'location_name' => $session->locationName(),
                'status' => $session->status(),
                'created_by_user_id' => $session->createdByUserId(),
                'memo' => $session->memo(),
                'started_at' => $session->startedAt(),
                'closed_at' => $session->closedAt(),
                'created_at' => $session->startedAt(),
            ]);

            return $session->withId((int) $this->pdo->lastInsertId());
        }

        $statement = $this->pdo->prepare('
            UPDATE inventory_sessions
            SET name = :name,
                location_name = :location_name,
                status = :status,
                created_by_user_id = :created_by_user_id,
                memo = :memo,
                started_at = :started_at,
                closed_at = :closed_at,
                updated_at = NOW()
            WHERE id = :id
        ');
        $statement->execute([
            'id' => $session->id(),
            'name' => $session->name(),
            'location_name' => $session->locationName(),
            'status' => $session->status(),
            'created_by_user_id' => $session->createdByUserId(),
            'memo' => $session->memo(),
            'started_at' => $session->startedAt(),
            'closed_at' => $session->closedAt(),
        ]);

        return $session;
    }

    public function createSnapshotsFromItems(int $sessionId): int
    {
        $this->pdo->prepare('DELETE FROM stock_snapshots WHERE session_id = :session_id')->execute(['session_id' => $sessionId]);

        $items = $this->pdo->query('
            SELECT id, current_stock_qty
            FROM items
            WHERE is_active = 1
            ORDER BY name ASC
        ')->fetchAll();

        $insert = $this->pdo->prepare('
            INSERT INTO stock_snapshots (session_id, item_id, expected_qty, created_at)
            VALUES (:session_id, :item_id, :expected_qty, NOW())
        ');

        $count = 0;
        foreach ($items as $item) {
            $insert->execute([
                'session_id' => $sessionId,
                'item_id' => (int) $item['id'],
                'expected_qty' => (float) $item['current_stock_qty'],
            ]);
            $count++;
        }

        return $count;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function snapshotRows(int $sessionId): array
    {
        $statement = $this->pdo->prepare('
            SELECT
                ss.session_id,
                ss.item_id,
                ss.expected_qty,
                i.sku,
                i.name,
                i.barcode,
                i.unit,
                i.current_stock_qty,
                ic.counted_qty,
                ic.counted_by_user_id,
                ic.counted_at
            FROM stock_snapshots ss
            INNER JOIN items i ON i.id = ss.item_id
            LEFT JOIN inventory_counts ic
                ON ic.session_id = ss.session_id
               AND ic.item_id = ss.item_id
            WHERE ss.session_id = :session_id
            ORDER BY i.name ASC
        ');
        $statement->execute(['session_id' => $sessionId]);

        return $statement->fetchAll();
    }

    public function snapshotCount(int $sessionId): int
    {
        $statement = $this->pdo->prepare('SELECT COUNT(*) FROM stock_snapshots WHERE session_id = :session_id');
        $statement->execute(['session_id' => $sessionId]);

        return (int) $statement->fetchColumn();
    }

    public function countedCount(int $sessionId): int
    {
        $statement = $this->pdo->prepare('SELECT COUNT(*) FROM inventory_counts WHERE session_id = :session_id');
        $statement->execute(['session_id' => $sessionId]);

        return (int) $statement->fetchColumn();
    }

    public function upsertCount(int $sessionId, int $itemId, Quantity $countedQty, int $userId): void
    {
        $statement = $this->pdo->prepare('
            INSERT INTO inventory_counts (session_id, item_id, counted_qty, counted_by_user_id, counted_at, created_at, updated_at)
            VALUES (:session_id, :item_id, :counted_qty, :counted_by_user_id, NOW(), NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                counted_qty = VALUES(counted_qty),
                counted_by_user_id = VALUES(counted_by_user_id),
                counted_at = VALUES(counted_at),
                updated_at = VALUES(updated_at)
        ');
        $statement->execute([
            'session_id' => $sessionId,
            'item_id' => $itemId,
            'counted_qty' => $countedQty->toFloat(),
            'counted_by_user_id' => $userId,
        ]);
    }

    public function storeAdjustment(
        int $sessionId,
        int $itemId,
        float $expectedQty,
        float $countedQty,
        float $differenceQty
    ): void {
        $statement = $this->pdo->prepare('
            INSERT INTO adjustments (
                session_id,
                item_id,
                expected_qty,
                counted_qty,
                difference_qty,
                created_at,
                updated_at
            )
            VALUES (
                :session_id,
                :item_id,
                :expected_qty,
                :counted_qty,
                :difference_qty,
                NOW(),
                NOW()
            )
            ON DUPLICATE KEY UPDATE
                expected_qty = VALUES(expected_qty),
                counted_qty = VALUES(counted_qty),
                difference_qty = VALUES(difference_qty),
                updated_at = VALUES(updated_at)
        ');
        $statement->execute([
            'session_id' => $sessionId,
            'item_id' => $itemId,
            'expected_qty' => $expectedQty,
            'counted_qty' => $countedQty,
            'difference_qty' => $differenceQty,
        ]);
    }

    public function markClosed(int $sessionId): void
    {
        $statement = $this->pdo->prepare('
            UPDATE inventory_sessions
            SET status = :status,
                closed_at = NOW(),
                updated_at = NOW()
            WHERE id = :id
        ');
        $statement->execute([
            'id' => $sessionId,
            'status' => InventorySession::STATUS_CLOSED,
        ]);
    }
}
