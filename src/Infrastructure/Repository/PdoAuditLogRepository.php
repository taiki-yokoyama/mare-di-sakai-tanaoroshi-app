<?php
declare(strict_types=1);

namespace MareDiSakai\Inventory\Infrastructure\Repository;

use PDO;

final class PdoAuditLogRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function record(string $action, ?int $actorUserId, string $subjectType, ?int $subjectId, array $payload = []): void
    {
        $statement = $this->pdo->prepare('
            INSERT INTO audit_logs (
                action,
                actor_user_id,
                subject_type,
                subject_id,
                payload_json,
                created_at
            )
            VALUES (
                :action,
                :actor_user_id,
                :subject_type,
                :subject_id,
                :payload_json,
                NOW()
            )
        ');
        $statement->execute([
            'action' => $action,
            'actor_user_id' => $actorUserId,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }
}
