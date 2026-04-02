<?php
declare(strict_types=1);

namespace MareDiSakai\Inventory\Application;

use MareDiSakai\Inventory\Domain\Entity\InventorySession;
use MareDiSakai\Inventory\Domain\Entity\Item;
use MareDiSakai\Inventory\Domain\Entity\User;
use MareDiSakai\Inventory\Domain\InventoryDomainException;
use MareDiSakai\Inventory\Domain\Service\PasswordHasher;
use MareDiSakai\Inventory\Domain\ValueObject\EmailAddress;
use MareDiSakai\Inventory\Domain\ValueObject\ItemCode;
use MareDiSakai\Inventory\Domain\ValueObject\Quantity;
use MareDiSakai\Inventory\Infrastructure\Repository\PdoAuditLogRepository;
use MareDiSakai\Inventory\Infrastructure\Repository\PdoInventorySessionRepository;
use MareDiSakai\Inventory\Infrastructure\Repository\PdoItemRepository;
use MareDiSakai\Inventory\Infrastructure\Repository\PdoUserRepository;
use MareDiSakai\Inventory\Support\Database;
use Throwable;

final class InventoryAppService
{
    private PdoItemRepository $items;
    private PdoInventorySessionRepository $sessions;
    private PdoUserRepository $users;
    private PdoAuditLogRepository $auditLogs;

    public function __construct(
        PdoItemRepository $items,
        PdoInventorySessionRepository $sessions,
        PdoUserRepository $users,
        PdoAuditLogRepository $auditLogs
    ) {
        $this->items = $items;
        $this->sessions = $sessions;
        $this->users = $users;
        $this->auditLogs = $auditLogs;
    }

    /**
     * @return array<string, mixed>
     */
    public function dashboard(): array
    {
        $sessions = $this->sessions->allSessions();

        return [
            'item_count' => $this->items->countActive(),
            'open_session_count' => $this->sessions->countOpenSessions(),
            'session_count' => count($sessions),
            'recent_sessions' => array_slice($sessions, 0, 5),
            'items' => array_slice($this->items->allActive(), 0, 5),
        ];
    }

    /**
     * @return Item[]
     */
    public function listItems(string $search = ''): array
    {
        return $this->items->all($search);
    }

    public function saveItem(
        ?int $id,
        string $sku,
        string $name,
        ?string $barcode,
        string $unit,
        string $currentStock,
        int $actorUserId
    ): Item {
        $quantity = Quantity::fromString($currentStock);

        if ($id === null) {
            $item = Item::create(ItemCode::fromString($sku), $name, $barcode, $unit, $quantity);
        } else {
            $item = $this->items->findById($id);
            if ($item === null) {
                throw new InventoryDomainException('Item not found.');
            }
            $item->update($name, $barcode, $unit, $quantity);
        }

        $saved = $this->items->save($item);
        $this->auditLogs->record('item.saved', $actorUserId, 'item', $saved->id(), [
            'sku' => $saved->sku()->value(),
            'name' => $saved->name(),
            'barcode' => $saved->barcode(),
            'unit' => $saved->unit(),
            'current_stock_qty' => $saved->currentStock()->toFloat(),
        ]);

        return $saved;
    }

    public function deleteItem(int $id, int $actorUserId): void
    {
        $item = $this->items->findById($id);
        if ($item === null) {
            throw new InventoryDomainException('Item not found.');
        }

        $this->items->delete($id);
        $this->auditLogs->record('item.deleted', $actorUserId, 'item', $id, [
            'sku' => $item->sku()->value(),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listSessions(): array
    {
        return $this->sessions->allSessions();
    }

    public function createSession(string $name, string $locationName, string $memo, int $actorUserId): InventorySession
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();

        try {
            $session = InventorySession::open($name, $locationName, $actorUserId, $memo);
            $session = $this->sessions->save($session);
            $snapshotCount = $this->sessions->createSnapshotsFromItems((int) $session->id());

            $this->auditLogs->record('session.created', $actorUserId, 'inventory_session', $session->id(), [
                'snapshot_count' => $snapshotCount,
            ]);

            $pdo->commit();

            return $session;
        } catch (Throwable $throwable) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $throwable;
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function sessionDetail(int $sessionId): array
    {
        $session = $this->sessions->findById($sessionId);
        if ($session === null) {
            throw new InventoryDomainException('Inventory session not found.');
        }

        $rows = $this->sessions->snapshotRows($sessionId);
        $mappedRows = [];
        $counted = 0;
        foreach ($rows as $row) {
            $expected = (float) $row['expected_qty'];
            $countedQty = $row['counted_qty'] !== null ? (float) $row['counted_qty'] : null;
            if ($countedQty !== null) {
                $counted++;
            }

            $mappedRows[] = [
                'item_id' => (int) $row['item_id'],
                'sku' => (string) $row['sku'],
                'name' => (string) $row['name'],
                'barcode' => $row['barcode'] !== null ? (string) $row['barcode'] : null,
                'unit' => (string) $row['unit'],
                'expected_qty' => $expected,
                'counted_qty' => $countedQty,
                'difference_qty' => $countedQty === null ? null : round($countedQty - $expected, 3),
                'counted_by_user_id' => $row['counted_by_user_id'] !== null ? (int) $row['counted_by_user_id'] : null,
                'counted_at' => $row['counted_at'] !== null ? (new \DateTimeImmutable((string) $row['counted_at']))->format('Y-m-d H:i:s') : null,
            ];
        }

        return [
            'session' => $session,
            'rows' => $mappedRows,
            'snapshot_count' => count($rows),
            'counted_count' => $counted,
            'is_complete' => $counted === count($rows),
        ];
    }

    public function recordCount(int $sessionId, int $itemId, string $countedQty, int $actorUserId): void
    {
        $session = $this->sessions->findById($sessionId);
        if ($session === null) {
            throw new InventoryDomainException('Inventory session not found.');
        }

        $session->ensureOpen();

        $item = $this->items->findById($itemId);
        if ($item === null) {
            throw new InventoryDomainException('Item not found.');
        }

        $quantity = Quantity::fromString($countedQty);
        $this->sessions->upsertCount($sessionId, $itemId, $quantity, $actorUserId);
        $this->auditLogs->record('count.recorded', $actorUserId, 'inventory_count', $itemId, [
            'session_id' => $sessionId,
            'item_id' => $itemId,
            'counted_qty' => $quantity->toFloat(),
        ]);
    }

    public function closeSession(int $sessionId, int $actorUserId): void
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();

        try {
            $session = $this->sessions->findById($sessionId);
            if ($session === null) {
                throw new InventoryDomainException('Inventory session not found.');
            }

            $session->ensureOpen();
            $rows = $this->sessions->snapshotRows($sessionId);
            $missing = [];

            foreach ($rows as $row) {
                if ($row['counted_qty'] === null) {
                    $missing[] = (string) $row['name'];
                    continue;
                }

                $expected = (float) $row['expected_qty'];
                $counted = (float) $row['counted_qty'];
                $difference = round($counted - $expected, 3);

                $this->sessions->storeAdjustment($sessionId, (int) $row['item_id'], $expected, $counted, $difference);
                $this->items->updateStock((int) $row['item_id'], Quantity::fromFloat($counted));
            }

            if ($missing !== []) {
                throw new InventoryDomainException('Please record all counts before closing: ' . implode(', ', $missing));
            }

            $this->sessions->markClosed($sessionId);
            $this->auditLogs->record('session.closed', $actorUserId, 'inventory_session', $sessionId, [
                'row_count' => count($rows),
            ]);

            $pdo->commit();
        } catch (Throwable $throwable) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $throwable;
        }
    }

    /**
     * @return User[]
     */
    public function listUsers(): array
    {
        return $this->users->all();
    }

    public function createUser(string $name, string $email, string $password, string $role, int $actorUserId): User
    {
        $hasher = new PasswordHasher();
        $salt = $hasher->generateSalt();
        $hash = $hasher->hash($password, $salt);

        $user = User::register($name, EmailAddress::fromString($email), $role, $salt, $hash);
        $saved = $this->users->save($user);
        $this->auditLogs->record('user.created', $actorUserId, 'user', $saved->id(), [
            'email' => $saved->email()->value(),
            'role' => $saved->role(),
        ]);

        return $saved;
    }

    public function deleteUser(int $id, int $actorUserId): void
    {
        $user = $this->users->findById($id);
        if ($user === null) {
            throw new InventoryDomainException('User not found.');
        }

        $this->users->delete($id);
        $this->auditLogs->record('user.deleted', $actorUserId, 'user', $id, [
            'email' => $user->email()->value(),
        ]);
    }
}
