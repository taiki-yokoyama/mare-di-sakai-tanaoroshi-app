<?php
declare(strict_types=1);

namespace MareDiSakai\Inventory\Domain\ValueObject;

use MareDiSakai\Inventory\Domain\InventoryDomainException;

final class ItemCode
{
    private string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function fromString(string $value): self
    {
        $normalized = strtoupper(trim($value));
        if ($normalized === '' || !preg_match('/^[A-Z0-9][A-Z0-9_-]{0,31}$/', $normalized)) {
            throw new InventoryDomainException('Invalid item code.');
        }

        return new self($normalized);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
