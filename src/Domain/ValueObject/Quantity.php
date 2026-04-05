<?php
declare(strict_types=1);

namespace MareDiSakai\Inventory\Domain\ValueObject;

use MareDiSakai\Inventory\Domain\InventoryDomainException;

final class Quantity
{
    private float $value;

    private function __construct(float $value)
    {
        $this->value = round($value, 3);
    }

    public static function fromString(string $value): self
    {
        $normalized = trim(str_replace(',', '.', $value));
        if ($normalized === '' || !is_numeric($normalized)) {
            throw new InventoryDomainException('数量は数値で入力してください。');
        }

        $floatValue = round((float) $normalized, 3);
        if ($floatValue < 0) {
            throw new InventoryDomainException('数量は0以上で入力してください。');
        }

        return new self($floatValue);
    }

    public static function fromFloat(float $value): self
    {
        if ($value < 0) {
            throw new InventoryDomainException('数量は0以上で入力してください。');
        }

        return new self($value);
    }

    public static function zero(): self
    {
        return new self(0.0);
    }

    public function toFloat(): float
    {
        return $this->value;
    }

    public function add(self $other): self
    {
        return new self($this->value + $other->value);
    }

    public function subtract(self $other): self
    {
        $result = $this->value - $other->value;
        if ($result < 0) {
            throw new InventoryDomainException('計算結果の数量をマイナスにすることはできません。');
        }

        return new self($result);
    }

    public function isZero(): bool
    {
        return abs($this->value) < 0.0005;
    }

    public function __toString(): string
    {
        return format_quantity($this->value);
    }
}
