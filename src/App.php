<?php
declare(strict_types=1);

namespace MareDiSakai\Inventory;

use MareDiSakai\Inventory\Application\AuthService;
use MareDiSakai\Inventory\Application\InventoryAppService;
use MareDiSakai\Inventory\Domain\Entity\User;
use MareDiSakai\Inventory\Domain\Service\PasswordHasher;
use MareDiSakai\Inventory\Infrastructure\Repository\PdoAuditLogRepository;
use MareDiSakai\Inventory\Infrastructure\Repository\PdoInventorySessionRepository;
use MareDiSakai\Inventory\Infrastructure\Repository\PdoItemRepository;
use MareDiSakai\Inventory\Infrastructure\Repository\PdoUserRepository;
use MareDiSakai\Inventory\Support\Database;
use Throwable;

final class App
{
    private AuthService $authService;
    private InventoryAppService $inventoryService;

    public function __construct()
    {
        $pdo = Database::pdo();

        $userRepository = new PdoUserRepository($pdo);
        $itemRepository = new PdoItemRepository($pdo);
        $sessionRepository = new PdoInventorySessionRepository($pdo);
        $auditRepository = new PdoAuditLogRepository($pdo);

        $this->authService = new AuthService($userRepository, new PasswordHasher());
        $this->inventoryService = new InventoryAppService($itemRepository, $sessionRepository, $userRepository, $auditRepository);
    }

    public function run(): void
    {
        $page = request_string('page', 'dashboard');
        $action = request_string('action', '');

        $this->handlePostAction($page, $action);

        $currentUser = $this->authService->currentUser();
        if ($currentUser === null && $page !== 'login') {
            redirect_to(url('login'));
        }

        if ($currentUser !== null && $page === 'login') {
            redirect_to(url('dashboard'));
        }

        $currentUserView = $currentUser ? user_view($currentUser) : null;

        switch ($page) {
            case 'login':
                $title = 'ログイン';
                $content = $this->renderTemplate(__DIR__ . '/Pages/login.php', [
                    'appName' => app_name(),
                ]);
                break;
            case 'items':
                $title = '商品';
                $content = $this->renderTemplate(__DIR__ . '/Pages/items.php', [
                    'inventoryService' => $this->inventoryService,
                    'currentUser' => $currentUser,
                ]);
                break;
            case 'sessions':
                $title = 'セッション';
                $content = $this->renderTemplate(__DIR__ . '/Pages/sessions.php', [
                    'inventoryService' => $this->inventoryService,
                    'currentUser' => $currentUser,
                ]);
                break;
            case 'users':
                $title = 'ユーザー';
                $content = $this->renderTemplate(__DIR__ . '/Pages/users.php', [
                    'inventoryService' => $this->inventoryService,
                    'currentUser' => $currentUser,
                ]);
                break;
            default:
                $title = 'ダッシュボード';
                $content = $this->renderTemplate(__DIR__ . '/Pages/dashboard.php', [
                    'inventoryService' => $this->inventoryService,
                    'currentUser' => $currentUser,
                ]);
                $page = 'dashboard';
                break;
        }

        render_layout($title, $page, $currentUserView, $content);
    }

    private function handlePostAction(string $page, string $action): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || $action === '') {
            return;
        }

        try {
            verify_csrf();

            switch ($action) {
                case 'login':
                    $user = $this->authService->attemptLogin(
                        request_string('email'),
                        request_string('password'),
                        request_bool('remember')
                    );
                    flash_set('success', 'おかえりなさい、' . $user->name() . 'さん。');
                    redirect_to(url('dashboard'));
                    break;
                case 'logout':
                    $this->authService->logout();
                    flash_set('success', 'ログアウトしました。');
                    redirect_to(url('login'));
                    break;
                case 'item-save':
                    $this->ensureAuthenticated();
                    $this->inventoryService->saveItem(
                        request_int('item_id', 0) > 0 ? request_int('item_id') : null,
                        request_string('sku'),
                        request_string('name'),
                        request_string('barcode') !== '' ? request_string('barcode') : null,
                        request_string('unit', '個'),
                        request_string('current_stock_qty', '0'),
                        $this->currentUserId()
                    );
                    flash_set('success', '商品を保存しました。');
                    redirect_to(url('items'));
                    break;
                case 'item-delete':
                    $this->ensureAuthenticated();
                    $this->inventoryService->deleteItem(request_int('item_id'), $this->currentUserId());
                    flash_set('success', '商品を削除しました。');
                    redirect_to(url('items'));
                    break;
                case 'session-create':
                    $this->ensureAuthenticated();
                    $session = $this->inventoryService->createSession(
                        request_string('name'),
                        request_string('location_name', '本店'),
                        request_string('memo'),
                        $this->currentUserId()
                    );
                    flash_set('success', 'セッションを作成しました。');
                    redirect_to(url('sessions', ['id' => $session->id()]));
                    break;
                case 'count-save':
                    $this->ensureAuthenticated();
                    $sessionId = request_int('session_id');
                    $this->inventoryService->recordCount(
                        $sessionId,
                        request_int('item_id'),
                        request_string('counted_qty', '0'),
                        $this->currentUserId()
                    );
                    flash_set('success', '数量を保存しました。');
                    redirect_to(url('sessions', ['id' => $sessionId]));
                    break;
                case 'session-close':
                    $this->ensureAuthenticated();
                    $sessionId = request_int('session_id');
                    $this->inventoryService->closeSession($sessionId, $this->currentUserId());
                    flash_set('success', 'セッションを終了しました。');
                    redirect_to(url('sessions', ['id' => $sessionId]));
                    break;
                case 'user-save':
                    $this->ensureAuthenticated();
                    $this->inventoryService->createUser(
                        request_string('name'),
                        request_string('email'),
                        request_string('password'),
                        'staff',
                        $this->currentUserId()
                    );
                    flash_set('success', 'ユーザーを作成しました。');
                    redirect_to(url('users'));
                    break;
                case 'user-delete':
                    $this->ensureAuthenticated();
                    $userId = request_int('user_id');
                    if ($userId === $this->currentUserId()) {
                        throw new \RuntimeException('現在のアカウントは削除できません。');
                    }
                    $this->inventoryService->deleteUser($userId, $this->currentUserId());
                    flash_set('success', 'ユーザーを削除しました。');
                    redirect_to(url('users'));
                    break;
                default:
                    throw new \RuntimeException('未対応の操作です。');
            }
        } catch (Throwable $throwable) {
            flash_set('error', $throwable->getMessage());

            switch ($action) {
                case 'login':
                    redirect_to(url('login'));
                    break;
                case 'count-save':
                case 'session-close':
                    redirect_to(url('sessions', ['id' => request_int('session_id')]));
                    break;
                default:
                    redirect_to(url($page));
            }
        }
    }

    private function ensureAuthenticated(): void
    {
        if ($this->authService->currentUser() === null) {
            throw new \RuntimeException('先にログインしてください。');
        }
    }

    private function currentUserId(): int
    {
        $user = $this->authService->currentUser();
        if ($user === null || $user->id() === null) {
            throw new \RuntimeException('先にログインしてください。');
        }

        return $user->id();
    }

    /**
     * @param array<string, mixed> $variables
     */
    private function renderTemplate(string $templatePath, array $variables): string
    {
        ob_start();
        extract($variables, EXTR_SKIP);
        require $templatePath;

        return (string) ob_get_clean();
    }
}
