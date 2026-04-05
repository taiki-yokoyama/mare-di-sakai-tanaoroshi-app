<?php
declare(strict_types=1);

namespace MareDiSakai\Inventory\Domain\ValueObject;

use MareDiSakai\Inventory\Domain\InventoryDomainException;

final class EmailAddress
{
    private string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function fromString(string $value): self
    {
        $normalized = strtolower(trim($value));
        if ($normalized === '' || filter_var($normalized, FILTER_VALIDATE_EMAIL) === false) {
            throw new InventoryDomainException('メールアドレスの形式が正しくありません。');
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
