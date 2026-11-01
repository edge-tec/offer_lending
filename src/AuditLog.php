<?php
declare(strict_types=1);

namespace App;

/**
 * System & Campaign Audit Logging
 */
class AuditLog
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Record an audit event
     */
    public function log(
        ?int $userId,
        string $action,
        string $entityType,
        ?int $entityId,
        ?string $oldValue,
        ?string $newValue
    ): void {
        $ip = Helpers::getClientIp();
        $stmt = $this->db->prepare("
            INSERT INTO audit_logs (user_id, action, entity_type, entity_id, old_value, new_value, ip_address, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([$userId, $action, $entityType, $entityId, $oldValue, $newValue, $ip]);
    }

    /**
     * Retrieve recent audit logs
     */
    public function getRecent(int $limit = 50): array
    {
        $sql = "
            SELECT a.*, u.username 
            FROM audit_logs a
            LEFT JOIN users u ON u.id = a.user_id
            ORDER BY a.id DESC
            LIMIT " . (int)$limit;
        return $this->db->query($sql)->fetchAll();
    }
}

