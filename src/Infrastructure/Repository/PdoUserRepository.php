<?php
declare(strict_types=1);

namespace MareDiSakai\Inventory\Infrastructure\Repository;

use DateTimeImmutable;
use MareDiSakai\Inventory\Domain\Entity\User;
use MareDiSakai\Inventory\Domain\ValueObject\EmailAddress;
use PDO;

final class PdoUserRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @return User[]
     */
    public function all(): array
    {
        $statement = $this->pdo->query('
            SELECT id, name, email, role, password_salt, password_hash, created_at
            FROM users
            ORDER BY id ASC
        ');

        $users = [];
        foreach ($statement->fetchAll() as $row) {
            $users[] = $this->hydrate($row);
        }

        return $users;
    }

    public function findById(int $id): ?User
    {
        $statement = $this->pdo->prepare('
            SELECT id, name, email, role, password_salt, password_hash, created_at
            FROM users
            WHERE id = :id
            LIMIT 1
        ');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function findByEmail(string $email): ?User
    {
        $statement = $this->pdo->prepare('
            SELECT id, name, email, role, password_salt, password_hash, created_at
            FROM users
            WHERE email = :email
            LIMIT 1
        ');
        $statement->execute(['email' => strtolower(trim($email))]);
        $row = $statement->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function save(User $user): User
    {
        if ($user->id() === null) {
            $statement = $this->pdo->prepare('
                INSERT INTO users (name, email, role, password_salt, password_hash, created_at, updated_at)
                VALUES (:name, :email, :role, :password_salt, :password_hash, :created_at, :created_at)
            ');
            $statement->execute([
                'name' => $user->name(),
                'email' => $user->email()->value(),
                'role' => $user->role(),
                'password_salt' => $user->passwordSalt(),
                'password_hash' => $user->passwordHash(),
                'created_at' => $user->createdAt(),
            ]);

            return $user->withId((int) $this->pdo->lastInsertId());
        }

        $statement = $this->pdo->prepare('
            UPDATE users
            SET name = :name,
                email = :email,
                role = :role,
                password_salt = :password_salt,
                password_hash = :password_hash,
                updated_at = NOW()
            WHERE id = :id
        ');
        $statement->execute([
            'id' => $user->id(),
            'name' => $user->name(),
            'email' => $user->email()->value(),
            'role' => $user->role(),
            'password_salt' => $user->passwordSalt(),
            'password_hash' => $user->passwordHash(),
        ]);

        return $user;
    }

    public function delete(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM users WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    public function storeRememberToken(int $userId, string $tokenHash, string $expiresAt): void
    {
        $statement = $this->pdo->prepare('
            INSERT INTO remember_tokens (user_id, token_hash, expires_at, created_at, last_used_at)
            VALUES (:user_id, :token_hash, :expires_at, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                user_id = VALUES(user_id),
                expires_at = VALUES(expires_at),
                last_used_at = VALUES(last_used_at)
        ');
        $statement->execute([
            'user_id' => $userId,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
        ]);
    }

    public function findUserIdByRememberToken(string $tokenHash): ?int
    {
        $statement = $this->pdo->prepare('
            SELECT user_id
            FROM remember_tokens
            WHERE token_hash = :token_hash
              AND expires_at > NOW()
            LIMIT 1
        ');
        $statement->execute(['token_hash' => $tokenHash]);
        $row = $statement->fetch();

        return $row ? (int) $row['user_id'] : null;
    }

    public function deleteRememberToken(string $tokenHash): void
    {
        $statement = $this->pdo->prepare('DELETE FROM remember_tokens WHERE token_hash = :token_hash');
        $statement->execute(['token_hash' => $tokenHash]);
    }

    public function purgeExpiredRememberTokens(): void
    {
        $this->pdo->exec('DELETE FROM remember_tokens WHERE expires_at <= NOW()');
    }

    private function hydrate(array $row): User
    {
        return User::reconstitute(
            (int) $row['id'],
            (string) $row['name'],
            EmailAddress::fromString((string) $row['email']),
            (string) $row['role'],
            (string) $row['password_salt'],
            (string) $row['password_hash'],
            (new DateTimeImmutable((string) $row['created_at']))->format('Y-m-d H:i:s')
        );
    }
}
