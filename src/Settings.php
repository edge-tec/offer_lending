<?php
declare(strict_types=1);

namespace App;

/**
 * Application Settings Manager
 */
class Settings
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get all settings as an associative key-value map
     */
    public function getAll(): array
    {
        $stmt = $this->db->query("SELECT setting_key, setting_value FROM settings");
        $results = $stmt->fetchAll();
        $map = [];
        foreach ($results as $row) {
            $map[$row['setting_key']] = $row['setting_value'];
        }
        return $map;
    }

    /**
     * Get single setting
     */
    public function get(string $key, ?string $default = null): ?string
    {
        $stmt = $this->db->prepare("SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1");
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();
        return $val !== false ? (string)$val : $default;
    }

    /**
     * Update settings array
     */
    public function updateMany(array $settings, int $userId): void
    {
        $existing = $this->getAll();
        $driver = Database::getInstance()->getDriver();
        if ($driver === 'sqlite') {
            $stmt = $this->db->prepare("
                INSERT INTO settings (setting_key, setting_value, updated_at) 
                VALUES (:key, :value, CURRENT_TIMESTAMP)
                ON CONFLICT(setting_key) DO UPDATE SET setting_value = :value, updated_at = CURRENT_TIMESTAMP
            ");
        } else {
            $stmt = $this->db->prepare("
                INSERT INTO settings (setting_key, setting_value, updated_at) 
                VALUES (:key, :value, CURRENT_TIMESTAMP)
                ON DUPLICATE KEY UPDATE setting_value = :value, updated_at = CURRENT_TIMESTAMP
            ");
        }

        foreach ($settings as $key => $val) {
            $stmt->execute([':key' => $key, ':value' => $val]);
        }

        (new AuditLog())->log(
            $userId,
            'UPDATE_SETTINGS',
            'settings',
            null,
            json_encode($existing),
            json_encode($settings)
        );
    }
}

