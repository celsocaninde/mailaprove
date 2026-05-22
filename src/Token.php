<?php

namespace GlpiPlugin\Mailaprove;

use CommonDBTM;
use CronTask;

class Token extends CommonDBTM
{
    public static $table = 'glpi_plugin_mailaprove_tokens';
    public static $rightname = 'plugin_mailaprove_token';

    // Action type constants
    const ACTION_VALIDATION_APPROVE = 'validation_approve';
    const ACTION_VALIDATION_REJECT  = 'validation_reject';
    const ACTION_SOLUTION_APPROVE   = 'solution_approve';
    const ACTION_SOLUTION_REJECT    = 'solution_reject';
    const ACTION_SATISFACTION       = 'satisfaction';

    /**
     * Generate a new token for an action.
     *
     * @param string $actionType One of the ACTION_* constants
     * @param int    $ticketId   The ticket ID
     * @param int    $itemId     The target item ID (validation ID, satisfaction ID, etc.)
     * @param int    $userId     The user authorized to perform the action
     *
     * @return string|false The raw token string, or false on failure
     */
    public static function generateToken(
        string $actionType,
        int $ticketId,
        int $itemId,
        int $userId
    ) {
        self::ensureSchema();

        // Generate cryptographically secure random token
        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);

        // Get expiration from config
        $config = Config::getConfig();
        $expirationHours = $config['token_expiration_hours'] ?? 72;
        $dateExpiration = date('Y-m-d H:i:s', strtotime("+{$expirationHours} hours"));

        $token = new self();
        $result = $token->add([
            'token_hash'      => $tokenHash,
            'action_type'     => $actionType,
            'tickets_id'      => $ticketId,
            'items_id'        => $itemId,
            'users_id'        => $userId,
            'is_used'         => 0,
            'date_creation'   => date('Y-m-d H:i:s'),
            'date_expiration' => $dateExpiration,
        ]);

        if ($result === false) {
            return false;
        }

        self::invalidatePreviousTokens($actionType, $ticketId, $itemId, $userId, (int) $result);

        AuditLog::record('token_created', 'success', [
            'token_id'    => (int) $result,
            'action_type' => $actionType,
            'tickets_id'  => $ticketId,
            'items_id'    => $itemId,
            'users_id'    => $userId,
        ]);

        return $rawToken;
    }

    /**
     * Validate a raw token and return the token data if valid.
     *
     * @param string $rawToken The raw token from the URL
     *
     * @return array|false Token data array on success, false on failure
     */
    public static function validateToken(string $rawToken)
    {
        global $DB;

        $tokenHash = hash('sha256', $rawToken);

        $iterator = $DB->request([
            'FROM'  => self::$table,
            'WHERE' => [
                'token_hash' => $tokenHash,
            ],
            'LIMIT' => 1,
        ]);

        if (count($iterator) === 0) {
            return false; // Token not found
        }

        $data = $iterator->current();

        // Check if already used
        if ((int)$data['is_used'] === 1) {
            return false;
        }

        // Check expiration
        if (strtotime($data['date_expiration']) < time()) {
            return false;
        }

        return $data;
    }

    /**
     * Validate token and return detailed status for error messages.
     *
     * @param string $rawToken
     *
     * @return array ['valid' => bool, 'error' => string|null, 'data' => array|null]
     */
    public static function validateTokenWithStatus(string $rawToken): array
    {
        global $DB;

        if (!self::hasValidRawTokenFormat($rawToken)) {
            AuditLog::record('token_invalid', 'error', [
                'message' => 'Invalid token format',
            ]);
            return [
                'valid' => false,
                'error' => 'invalid',
                'data'  => null,
            ];
        }

        $tokenHash = hash('sha256', $rawToken);

        $iterator = $DB->request([
            'FROM'  => self::$table,
            'WHERE' => [
                'token_hash' => $tokenHash,
            ],
            'LIMIT' => 1,
        ]);

        if (count($iterator) === 0) {
            AuditLog::record('token_invalid', 'error', [
                'message' => 'Token hash not found',
            ]);
            return [
                'valid' => false,
                'error' => 'invalid',
                'data'  => null,
            ];
        }

        $data = $iterator->current();

        if ((int)$data['is_used'] === 1) {
            AuditLog::record('token_already_used', 'warning', AuditLog::contextFromTokenRow((array) $data));
            return [
                'valid' => false,
                'error' => 'used',
                'data'  => $data,
            ];
        }

        if (strtotime($data['date_expiration']) < time()) {
            AuditLog::record('token_expired', 'warning', AuditLog::contextFromTokenRow((array) $data));
            return [
                'valid' => false,
                'error' => 'expired',
                'data'  => $data,
            ];
        }

        return [
            'valid' => true,
            'error' => null,
            'data'  => $data,
        ];
    }

    /**
     * Validate and atomically claim a token before performing an action.
     *
     * This prevents double clicks, mail security scanners, and concurrent tabs
     * from processing the same mail action more than once.
     *
     * @param string      $rawToken
     * @param string|null $expectedActionType
     *
     * @return array ['valid' => bool, 'error' => string|null, 'data' => array|null]
     */
    public static function claimTokenWithStatus(string $rawToken, ?string $expectedActionType = null): array
    {
        global $DB;

        self::ensureSchema();

        $result = self::validateTokenWithStatus($rawToken);
        if (!$result['valid']) {
            return $result;
        }

        $data = (array) $result['data'];

        if ($expectedActionType !== null && (string) $data['action_type'] !== $expectedActionType) {
            AuditLog::record('token_invalid_action', 'error', AuditLog::contextFromTokenRow($data, [
                'message' => 'Token action does not match requested endpoint',
                'payload' => [
                    'expected' => $expectedActionType,
                    'actual'   => (string) $data['action_type'],
                ],
            ]));
            return [
                'valid' => false,
                'error' => 'invalid_action',
                'data'  => $data,
            ];
        }

        $DB->update(self::$table, array_merge([
            'is_used' => 1,
        ], self::usageMetadata('claimed')), [
            'id'              => (int) $data['id'],
            'is_used'         => 0,
            'date_expiration' => ['>=', date('Y-m-d H:i:s')],
        ]);

        if ((int) $DB->affectedRows() !== 1) {
            AuditLog::record('token_claim_failed', 'warning', AuditLog::contextFromTokenRow($data, [
                'message' => 'Token was already claimed or expired while processing',
            ]));
            return [
                'valid' => false,
                'error' => 'used',
                'data'  => $data,
            ];
        }

        $iterator = $DB->request([
            'FROM'  => self::$table,
            'WHERE' => ['id' => (int) $data['id']],
            'LIMIT' => 1,
        ]);
        $claimed = count($iterator) > 0 ? (array) $iterator->current() : $data;

        AuditLog::record('token_claimed', 'success', AuditLog::contextFromTokenRow($claimed));

        return [
            'valid' => true,
            'error' => null,
            'data'  => $claimed,
        ];
    }

    public static function markRelatedAsUsed(array $actionTypes, int $ticketId, int $itemId): int
    {
        return self::markMatchingAsUsed([
            'tickets_id'  => $ticketId,
            'items_id'    => $itemId,
            'action_type' => array_values(array_filter(array_map('strval', $actionTypes))),
            'is_used'     => 0,
        ], 'related_invalidated', 'related_tokens_invalidated', [
            'action_type' => implode(',', array_values(array_filter(array_map('strval', $actionTypes)))),
            'tickets_id'  => $ticketId,
            'items_id'    => $itemId,
        ]);
    }

    private static function invalidatePreviousTokens(
        string $actionType,
        int $ticketId,
        int $itemId,
        int $userId,
        int $newTokenId
    ): int
    {
        return self::markMatchingAsUsed([
            'id'          => ['<>', $newTokenId],
            'action_type' => $actionType,
            'tickets_id'  => $ticketId,
            'items_id'    => $itemId,
            'users_id'    => $userId,
            'is_used'     => 0,
        ], 'replaced', 'token_replaced', [
            'action_type' => $actionType,
            'tickets_id'  => $ticketId,
            'items_id'    => $itemId,
            'users_id'    => $userId,
            'token_id'    => $newTokenId,
        ]);
    }

    private static function markMatchingAsUsed(array $where, string $reason, string $event, array $context): int
    {
        global $DB;

        if (isset($where['action_type']) && $where['action_type'] === []) {
            return 0;
        }

        self::ensureSchema();

        $DB->update(self::$table, array_merge([
            'is_used' => 1,
        ], self::usageMetadata($reason)), $where);

        $count = (int) $DB->affectedRows();
        if ($count > 0) {
            AuditLog::record($event, 'info', array_merge($context, [
                'message' => sprintf('%d token(s) marked as %s', $count, $reason),
                'payload' => [
                    'count'  => $count,
                    'reason' => $reason,
                ],
            ]));
        }

        return $count;
    }

    public static function ensureSchema(): void
    {
        global $DB;

        if (!$DB->tableExists(self::$table)) {
            return;
        }

        foreach ([
            'use_reason'     => 'VARCHAR(32) DEFAULT NULL',
            'use_ip'         => 'VARCHAR(64) DEFAULT NULL',
            'use_user_agent' => 'VARCHAR(255) DEFAULT NULL',
            'date_used'      => 'TIMESTAMP NULL DEFAULT NULL',
        ] as $column => $definition) {
            if (!$DB->fieldExists(self::$table, $column)) {
                $DB->doQuery("ALTER TABLE `" . self::$table . "` ADD COLUMN `{$column}` {$definition}");
            }
        }
    }

    private static function usageMetadata(string $reason): array
    {
        return [
            'use_reason'     => substr($reason, 0, 32),
            'use_ip'         => substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 64) ?: null,
            'use_user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255) ?: null,
            'date_used'      => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Mark a token as used.
     *
     * @param int $tokenId
     *
     * @return bool
     */
    public static function markAsUsed(int $tokenId): bool
    {
        self::ensureSchema();

        $token = new self();
        $updated = $token->update(array_merge([
            'id'      => $tokenId,
            'is_used' => 1,
        ], self::usageMetadata('used')));

        if ($updated && $token->getFromDB($tokenId)) {
            AuditLog::record('token_used', 'success', AuditLog::contextFromTokenRow($token->fields));
        }

        return $updated;
    }

    /**
     * Build the full action URL for a given token.
     *
     * @param string $rawToken   The raw token string
     * @param string $actionType The action type (determines the endpoint)
     *
     * @return string The full URL
     */
    public static function buildActionUrl(string $rawToken, string $actionType): string
    {
        global $CFG_GLPI;

        $baseUrl = rtrim($CFG_GLPI['url_base'] ?? '', '/');
        $pluginPath = '/plugins/mailaprove/front';

        $endpoints = [
            self::ACTION_VALIDATION_APPROVE => 'approve.php',
            self::ACTION_VALIDATION_REJECT  => 'reject.php',
            self::ACTION_SOLUTION_APPROVE   => 'solution_approve.php',
            self::ACTION_SOLUTION_REJECT    => 'solution_reject.php',
            self::ACTION_SATISFACTION       => 'satisfaction.php',
        ];

        $endpoint = $endpoints[$actionType] ?? 'approve.php';

        return "{$baseUrl}{$pluginPath}/{$endpoint}?token=" . urlencode($rawToken);
    }

    private static function hasValidRawTokenFormat(string $rawToken): bool
    {
        return preg_match('/\A[a-f0-9]{64}\z/i', $rawToken) === 1;
    }

    /**
     * CronTask: Clean expired tokens.
     *
     * @param CronTask $task
     *
     * @return int Number of cleaned tokens
     */
    public static function cronCleanExpiredTokens(CronTask $task): int
    {
        global $DB;

        $config = Config::getConfig();
        $usedTokenRetentionDays = max(1, (int) ($config['used_token_retention_days'] ?? 30));
        $auditRetentionDays = max(1, (int) ($config['audit_retention_days'] ?? 180));
        $now = date('Y-m-d H:i:s');
        $usedTokenCutoff = date('Y-m-d H:i:s', strtotime("-{$usedTokenRetentionDays} days"));
        $auditCutoff = date('Y-m-d H:i:s', strtotime("-{$auditRetentionDays} days"));
        $total = 0;

        self::ensureSchema();
        AuditLog::ensureTable();

        $DB->delete(self::$table, [
            'is_used'         => 0,
            'date_expiration' => ['<', $now],
        ]);
        $total += (int) $DB->affectedRows();

        $DB->delete(self::$table, [
            'is_used'  => 1,
            'date_used' => ['<', $usedTokenCutoff],
        ]);
        $total += (int) $DB->affectedRows();

        $DB->delete(self::$table, [
            'is_used'       => 1,
            'date_used'     => null,
            'date_creation' => ['<', $usedTokenCutoff],
        ]);
        $total += (int) $DB->affectedRows();

        $DB->delete(AuditLog::$table, [
            'date_creation' => ['<', $auditCutoff],
        ]);
        $total += (int) $DB->affectedRows();

        $task->addVolume($total);

        return ($total > 0) ? 1 : 0;
    }

    public static function getTypeName($nb = 0): string
    {
        return _n('Approval Token', 'Approval Tokens', $nb, 'mailaprove');
    }
}
