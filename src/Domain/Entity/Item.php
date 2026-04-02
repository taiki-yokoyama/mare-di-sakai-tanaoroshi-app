<?php
declare(strict_types=1);

namespace MareDiSakai\Inventory\Domain\Entity;

use MareDiSakai\Inventory\Domain\InventoryDomainException;
use MareDiSakai\Inventory\Domain\ValueObject\ItemCode;
use MareDiSakai\Inventory\Domain\ValueObject\Quantity;

final class Item
{
    private ?int $id;
    private ItemCode $sku;
    private string $name;
    private ?string $barcode;
    private string $unit;
    private Quantity $currentStock;
    private bool $active;
    private string $createdAt;

    private function __construct(
        ?int $id,
        ItemCode $sku,
        string $name,
        ?string $barcode,
        string $unit,
        Quantity $currentStock,
        bool $active,
        string $createdAt
    ) {
        $this->id = $id;
        $this->sku = $sku;
        $this->name = $name;
        $this->barcode = $barcode;
        $this->unit = $unit;
        $this->currentStock = $currentStock;
        $this->active = $active;
        $this->createdAt = $createdAt;
    }

    public static function create(
        ItemCode $sku,
        string $name,
        ?string $barcode,
        string $unit,
        Quantity $currentStock
    ): self {
        $name = trim($name);
        $unit = trim($unit);

        if ($name === '') {
            throw new InventoryDomainException('Item name is required.');
        }

        if ($unit === '') {
            throw new InventoryDomainException('Item unit is required.');
        }

        $barcode = $barcode !== null ? trim($barcode) : null;
        if ($barcode === '') {
            $barcode = null;
        }

        return new self(null, $sku, $name, $barcode, $unit, $currentStock, true, date('Y-m-d H:i:s'));
    }

    public static function reconstitute(
        int $id,
        ItemCode $sku,
        string $name,
        ?string $barcode,
        string $unit,
        Quantity $currentStock,
        bool $active,
        string $createdAt
    ): self {
        return new self($id, $sku, $name, $barcode, $unit, $currentStock, $active, $createdAt);
    }

    public function withId(int $id): self
    {
        return new self($id, $this->sku, $this->name, $this->barcode, $this->unit, $this->currentStock, $this->active, $this->createdAt);
    }

    public function update(string $name, ?string $barcode, string $unit, Quantity $currentStock): void
    {
        $name = trim($name);
        $unit = trim($unit);
        $barcode = $barcode !== null ? trim($barcode) : null;

        if ($name === '') {
            throw new InventoryDomainException('Item name is required.');
        }

        if ($unit === '') {
            throw new InventoryDomainException('Item unit is required.');
        }

        if ($barcode === '') {
            $barcode = null;
        }

        $this->name = $name;
        $this->barcode = $barcode;
        $this->unit = $unit;
        $this->currentStock = $currentStock;
    }

    public function adjustStock(Quantity $quantity): void
    {
        $this->currentStock = $quantity;
    }

    public function deactivate(): void
    {
        $this->active = false;
    }

    public function activate(): void
    {
        $this->active = true;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function sku(): ItemCode
    {
        return $this->sku;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function barcode(): ?string
    {
        return $this->barcode;
    }

    public function unit(): string
    {
        return $this->unit;
    }

    public function currentStock(): Quantity
    {
        return $this->currentStock;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function createdAt(): string
    {
        return $this->createdAt;
    }
}
