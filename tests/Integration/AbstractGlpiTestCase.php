<?php

namespace GlpiPlugin\Mailaprove\Tests\Integration;

use GlpiPlugin\Mailaprove\Token;
use PHPUnit\Framework\TestCase;

abstract class AbstractGlpiTestCase extends TestCase
{
    protected const TEST_TICKET_ID = 99998;
    protected const TEST_ITEM_ID   = 99998;
    protected const TEST_USER_ID   = 2; // glpi admin — sempre existe

    protected function setUp(): void
    {
        parent::setUp();
        // The static $tokenCache persists across tests in the same process.
        // Reset it so each test gets a fresh DB-backed token, not a stale cached one.
        $ref = new \ReflectionProperty(Token::class, 'tokenCache');
        $ref->setAccessible(true);
        $ref->setValue(null, []);
        $this->cleanupTestTokens();
        $this->cleanupTestAuditLogs();
        $this->cleanupTestTicketUsers();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->cleanupTestTokens();
        $this->cleanupTestAuditLogs();
        $this->cleanupTestTicketUsers();
    }

    protected function db(): \DBmysql
    {
        global $DB;
        return $DB;
    }

    protected function makeToken(
        string $actionType = Token::ACTION_VALIDATION_APPROVE,
        int $ticketId = self::TEST_TICKET_ID,
        int $itemId   = self::TEST_ITEM_ID,
        int $userId   = self::TEST_USER_ID
    ): string {
        $raw = Token::generateToken($actionType, $ticketId, $itemId, $userId);

        $this->assertIsString($raw, 'generateToken deve retornar uma string');
        $this->assertNotFalse($raw, 'generateToken não deve retornar false');

        return $raw;
    }

    protected function insertExpiredToken(
        string $actionType = Token::ACTION_VALIDATION_APPROVE,
        bool $isUsed       = false,
        ?string $dateUsed  = null
    ): int {
        $rawToken  = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);

        $this->db()->insert(Token::$table, [
            'token_hash'      => $tokenHash,
            'action_type'     => $actionType,
            'tickets_id'      => self::TEST_TICKET_ID,
            'items_id'        => self::TEST_ITEM_ID,
            'users_id'        => self::TEST_USER_ID,
            'is_used'         => $isUsed ? 1 : 0,
            'use_reason'      => $isUsed ? 'test' : null,
            'date_creation'   => date('Y-m-d H:i:s', strtotime('-10 days')),
            'date_expiration' => date('Y-m-d H:i:s', strtotime('-1 day')), // já expirado
            'date_used'       => $dateUsed,
        ]);

        return (int) $this->db()->insertId();
    }

    private function cleanupTestTokens(): void
    {
        $this->db()->delete(Token::$table, [
            'tickets_id' => self::TEST_TICKET_ID,
        ]);
    }

    private function cleanupTestAuditLogs(): void
    {
        $this->db()->delete('glpi_plugin_mailaprove_auditlogs', [
            'tickets_id' => self::TEST_TICKET_ID,
        ]);
    }

    private function cleanupTestTicketUsers(): void
    {
        $this->db()->delete('glpi_tickets_users', [
            'tickets_id' => self::TEST_TICKET_ID,
        ]);
    }
}
