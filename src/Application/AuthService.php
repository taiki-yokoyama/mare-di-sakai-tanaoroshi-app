<?php
declare(strict_types=1);

namespace MareDiSakai\Inventory\Application;

use MareDiSakai\Inventory\Domain\Entity\User;
use MareDiSakai\Inventory\Domain\Service\PasswordHasher;
use MareDiSakai\Inventory\Domain\ValueObject\EmailAddress;
use MareDiSakai\Inventory\Infrastructure\Repository\PdoUserRepository;
use RuntimeException;

final class AuthService
{
    private PdoUserRepository $users;
    private PasswordHasher $hasher;

    public function __construct(PdoUserRepository $users, PasswordHasher $hasher)
    {
        $this->users = $users;
        $this->hasher = $hasher;
    }

    public function currentUser(): ?User
    {
        $userId = $_SESSION['user_id'] ?? null;
        if (is_numeric($userId)) {
            $user = $this->users->findById((int) $userId);
            if ($user !== null) {
                return $user;
            }

            unset($_SESSION['user_id']);
        }

        $rememberCookieName = (string) (app_config()['remember_cookie']['name'] ?? 'mare_di_sakai_remember');
        $token = $_COOKIE[$rememberCookieName] ?? null;
        if (!is_string($token) || trim($token) === '') {
            return null;
        }

        $tokenHash = hash('sha256', $token);
        $userId = $this->users->findUserIdByRememberToken($tokenHash);
        if ($userId === null) {
            clear_cookie($rememberCookieName);
            return null;
        }

        $user = $this->users->findById($userId);
        if ($user === null) {
            $this->users->deleteRememberToken($tokenHash);
            clear_cookie($rememberCookieName);
            return null;
        }

        $_SESSION['user_id'] = $user->id();

        return $user;
    }

    public function attemptLogin(string $email, string $password, bool $remember): User
    {
        $user = $this->users->findByEmail($email);
        if ($user === null) {
            throw new RuntimeException('Invalid email or password.');
        }

        if (!$this->hasher->verify($password, $user->passwordSalt(), $user->passwordHash())) {
            throw new RuntimeException('Invalid email or password.');
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = $user->id();

        if ($remember) {
            $this->issueRememberToken($user);
        } else {
            $this->forgetRememberToken();
        }

        return $user;
    }

    public function logout(): void
    {
        $this->forgetRememberToken();
        unset($_SESSION['user_id']);
        session_regenerate_id(true);
    }

    public function createUser(string $name, string $email, string $password, string $role): User
    {
        $salt = $this->hasher->generateSalt();
        $hash = $this->hasher->hash($password, $salt);

        $user = User::register($name, EmailAddress::fromString($email), $role, $salt, $hash);

        return $this->users->save($user);
    }

    private function issueRememberToken(User $user): void
    {
        $rememberConfig = app_config()['remember_cookie'] ?? [];
        $cookieName = (string) ($rememberConfig['name'] ?? 'mare_di_sakai_remember');
        $days = (int) ($rememberConfig['days'] ?? 180);
        $expiresAt = time() + ($days * 86400);
        $token = bin2hex(random_bytes(32));

        $this->users->storeRememberToken($user->id() ?? 0, hash('sha256', $token), date('Y-m-d H:i:s', $expiresAt));
        set_cookie($cookieName, $token, $expiresAt);
    }

    private function forgetRememberToken(): void
    {
        $rememberCookieName = (string) (app_config()['remember_cookie']['name'] ?? 'mare_di_sakai_remember');
        $token = $_COOKIE[$rememberCookieName] ?? null;

        if (is_string($token) && trim($token) !== '') {
            $this->users->deleteRememberToken(hash('sha256', $token));
        }

        clear_cookie($rememberCookieName);
    }
}
