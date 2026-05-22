<?php

namespace GlpiPlugin\Mailaprove;

use CommonDBTM;
use Throwable;

class AuditLog extends CommonDBTM
{
    public static $table = 'glpi_plugin_mailaprove_auditlogs';
    public static $rightname = 'config';

    public static function getTypeName($nb = 0): string
    {
        return _n('Mail approval audit log', 'Mail approval audit logs', $nb, 'mailaprove');
    }

    public static function ensureTable(): void
    {
        global $DB;

        if ($DB->tableExists(self::$table)) {
            return;
        }

        $DB->doQuery("CREATE TABLE IF NOT EXISTS `" . self::$table . "` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `event` VARCHAR(64) NOT NULL,
            `status` VARCHAR(32) NOT NULL DEFAULT 'info',
            `token_id` INT UNSIGNED DEFAULT NULL,
            `action_type` VARCHAR(30) DEFAULT NULL,
            `tickets_id` INT UNSIGNED NOT NULL DEFAULT 0,
            `items_id` INT UNSIGNED NOT NULL DEFAULT 0,
            `users_id` INT UNSIGNED NOT NULL DEFAULT 0,
            `ip` VARCHAR(64) DEFAULT NULL,
            `user_agent` VARCHAR(255) DEFAULT NULL,
            `message` TEXT DEFAULT NULL,
            `payload` LONGTEXT DEFAULT NULL,
            `date_creation` TIMESTAMP NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `event` (`event`),
            KEY `status` (`status`),
            KEY `token_id` (`token_id`),
            KEY `tickets_id` (`tickets_id`),
            KEY `date_creation` (`date_creation`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC");
    }

    public static function record(string $event, string $status = 'info', array $context = []): void
    {
        try {
            self::ensureTable();

            $log = new self();
            $log->add([
                'event'         => substr($event, 0, 64),
                'status'        => substr($status, 0, 32),
                'token_id'      => isset($context['token_id']) ? (int) $context['token_id'] : null,
                'action_type'   => isset($context['action_type']) ? substr((string) $context['action_type'], 0, 30) : null,
                'tickets_id'    => (int) ($context['tickets_id'] ?? 0),
                'items_id'      => (int) ($context['items_id'] ?? 0),
                'users_id'      => (int) ($context['users_id'] ?? 0),
                'ip'            => substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 64) ?: null,
                'user_agent'    => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255) ?: null,
                'message'       => isset($context['message']) ? (string) $context['message'] : null,
                'payload'       => isset($context['payload']) ? json_encode($context['payload']) : null,
                'date_creation' => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $e) {
            // Audit must never block the approval flow.
        }
    }

    public static function contextFromTokenRow(array $row, array $extra = []): array
    {
        $payload = [];
        foreach (['use_reason', 'date_used'] as $key) {
            if (isset($row[$key]) && (string) $row[$key] !== '') {
                $payload[$key] = (string) $row[$key];
            }
        }
        if (isset($extra['payload']) && is_array($extra['payload'])) {
            $payload = array_merge($payload, $extra['payload']);
            unset($extra['payload']);
        }

        return array_merge([
            'token_id'    => (int) ($row['id'] ?? 0),
            'action_type' => (string) ($row['action_type'] ?? ''),
            'tickets_id'  => (int) ($row['tickets_id'] ?? 0),
            'items_id'    => (int) ($row['items_id'] ?? 0),
            'users_id'    => (int) ($row['users_id'] ?? 0),
            'payload'     => $payload !== [] ? $payload : null,
        ], $extra);
    }
}
