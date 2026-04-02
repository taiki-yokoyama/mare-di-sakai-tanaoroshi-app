<?php
declare(strict_types=1);

namespace MareDiSakai\Inventory\Domain\Entity;

use MareDiSakai\Inventory\Domain\ValueObject\EmailAddress;

final class User
{
    private ?int $id;
    private string $name;
    private EmailAddress $email;
    private string $role;
    private string $passwordSalt;
    private string $passwordHash;
    private string $createdAt;

    private function __construct(
        ?int $id,
        string $name,
        EmailAddress $email,
        string $role,
        string $passwordSalt,
        string $passwordHash,
        string $createdAt
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->role = $role;
        $this->passwordSalt = $passwordSalt;
        $this->passwordHash = $passwordHash;
        $this->createdAt = $createdAt;
    }

    public static function register(
        string $name,
        EmailAddress $email,
        string $role,
        string $passwordSalt,
        string $passwordHash
    ): self {
        $name = trim($name);
        if ($name === '') {
            throw new \InvalidArgumentException('User name is required.');
        }

        $role = trim($role) !== '' ? strtolower(trim($role)) : 'staff';

        return new self(null, $name, $email, $role, $passwordSalt, $passwordHash, date('Y-m-d H:i:s'));
    }

    public static function reconstitute(
        int $id,
        string $name,
        EmailAddress $email,
        string $role,
        string $passwordSalt,
        string $passwordHash,
        string $createdAt
    ): self {
        return new self($id, $name, $email, $role, $passwordSalt, $passwordHash, $createdAt);
    }

    public function withId(int $id): self
    {
        return new self($id, $this->name, $this->email, $this->role, $this->passwordSalt, $this->passwordHash, $this->createdAt);
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function email(): EmailAddress
    {
        return $this->email;
    }

    public function role(): string
    {
        return $this->role;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function passwordSalt(): string
    {
        return $this->passwordSalt;
    }

    public function passwordHash(): string
    {
        return $this->passwordHash;
    }

    public function createdAt(): string
    {
        return $this->createdAt;
    }
}
