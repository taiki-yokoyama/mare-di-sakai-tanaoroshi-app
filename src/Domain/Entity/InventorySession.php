<?php
declare(strict_types=1);

namespace MareDiSakai\Inventory\Domain\Entity;

use MareDiSakai\Inventory\Domain\InventoryDomainException;

final class InventorySession
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSED = 'closed';

    private ?int $id;
    private string $name;
    private string $locationName;
    private string $status;
    private int $createdByUserId;
    private string $memo;
    private string $startedAt;
    private ?string $closedAt;

    private function __construct(
        ?int $id,
        string $name,
        string $locationName,
        string $status,
        int $createdByUserId,
        string $memo,
        string $startedAt,
        ?string $closedAt
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->locationName = $locationName;
        $this->status = $status;
        $this->createdByUserId = $createdByUserId;
        $this->memo = $memo;
        $this->startedAt = $startedAt;
        $this->closedAt = $closedAt;
    }

    public static function open(string $name, string $locationName, int $createdByUserId, string $memo = ''): self
    {
        $name = trim($name);
        $locationName = trim($locationName);
        $memo = trim($memo);

        if ($name === '') {
            throw new InventoryDomainException('Session name is required.');
        }

        if ($locationName === '') {
            throw new InventoryDomainException('Location name is required.');
        }

        return new self(null, $name, $locationName, self::STATUS_OPEN, $createdByUserId, $memo, date('Y-m-d H:i:s'), null);
    }

    public static function reconstitute(
        int $id,
        string $name,
        string $locationName,
        string $status,
        int $createdByUserId,
        string $memo,
        string $startedAt,
        ?string $closedAt
    ): self {
        return new self($id, $name, $locationName, $status, $createdByUserId, $memo, $startedAt, $closedAt);
    }

    public function withId(int $id): self
    {
        return new self(
            $id,
            $this->name,
            $this->locationName,
            $this->status,
            $this->createdByUserId,
            $this->memo,
            $this->startedAt,
            $this->closedAt
        );
    }

    public function ensureOpen(): void
    {
        if ($this->status !== self::STATUS_OPEN) {
            throw new InventoryDomainException('Inventory session is already closed.');
        }
    }

    public function close(): void
    {
        $this->ensureOpen();
        $this->status = self::STATUS_CLOSED;
        $this->closedAt = date('Y-m-d H:i:s');
    }

    public function reopen(): void
    {
        if ($this->status === self::STATUS_CLOSED) {
            $this->status = self::STATUS_OPEN;
            $this->closedAt = null;
        }
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function locationName(): string
    {
        return $this->locationName;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function createdByUserId(): int
    {
        return $this->createdByUserId;
    }

    public function memo(): string
    {
        return $this->memo;
    }

    public function startedAt(): string
    {
        return $this->startedAt;
    }

    public function closedAt(): ?string
    {
        return $this->closedAt;
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }
}
