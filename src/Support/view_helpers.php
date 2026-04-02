<?php
declare(strict_types=1);

use MareDiSakai\Inventory\Domain\Entity\InventorySession;
use MareDiSakai\Inventory\Domain\Entity\User;

function user_view(User $user): array
{
    return [
        'id' => $user->id(),
        'name' => $user->name(),
        'role' => $user->role(),
        'is_admin' => $user->isAdmin(),
    ];
}

function badge_class(string $status): string
{
    $normalized = strtolower($status);

    if ($normalized === InventorySession::STATUS_OPEN || $normalized === 'active') {
        return 'badge badge-success';
    }

    if ($normalized === InventorySession::STATUS_CLOSED) {
        return 'badge badge-muted';
    }

    return 'badge badge-warning';
}

function session_status_label(string $status): string
{
    switch (strtolower($status)) {
        case InventorySession::STATUS_OPEN:
            return 'Open';
        case InventorySession::STATUS_CLOSED:
            return 'Closed';
        default:
            return 'Draft';
    }
}

function progress_percent(int $counted, int $total): int
{
    if ($total <= 0) {
        return 100;
    }

    return (int) round(($counted / $total) * 100);
}
