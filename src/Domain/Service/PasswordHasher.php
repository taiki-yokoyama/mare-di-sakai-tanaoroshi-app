<?php
declare(strict_types=1);

namespace MareDiSakai\Inventory\Domain\Service;

final class PasswordHasher
{
    public function generateSalt(): string
    {
        return bin2hex(random_bytes(16));
    }

    public function hash(string $password, string $salt): string
    {
        $config = app_config();
        $iterations = (int) ($config['security']['password_iterations'] ?? 100000);

        return hash_pbkdf2('sha256', $password, $salt, $iterations, 64, false);
    }

    public function verify(string $password, string $salt, string $expectedHash): bool
    {
        return hash_equals($expectedHash, $this->hash($password, $salt));
    }
}
