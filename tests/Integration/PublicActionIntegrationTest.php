<?php

namespace GlpiPlugin\Mailaprove\Tests\Integration;

use GlpiPlugin\Mailaprove\PublicAction;
use GlpiPlugin\Mailaprove\Token;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testes de integração para PublicAction — requerem banco de dados GLPI ativo.
 *
 * Cobrem: isTicketRequester com dados reais e tokenErrorContent (já coberto em unit,
 * confirmado aqui para garantir que o GLPI __ não quebre as strings).
 */
class PublicActionIntegrationTest extends AbstractGlpiTestCase
{
    // ------------------------------------------------------------------
    // isTicketRequester
    // ------------------------------------------------------------------

    #[Test]
    public function isTicketRequesterReturnsTrueWhenUserIsRequesterInTicketsUsers(): void
    {
        $this->insertTicketUser(self::TEST_TICKET_ID, self::TEST_USER_ID, type: 1);

        $this->assertTrue(PublicAction::isTicketRequester(self::TEST_TICKET_ID, self::TEST_USER_ID));
    }

    #[Test]
    public function isTicketRequesterReturnsFalseWhenUserIsAssignedNotRequester(): void
    {
        // Tipo 2 = atribuído, não solicitante
        $this->insertTicketUser(self::TEST_TICKET_ID, self::TEST_USER_ID, type: 2);

        $this->assertFalse(PublicAction::isTicketRequester(self::TEST_TICKET_ID, self::TEST_USER_ID));
    }

    #[Test]
    public function isTicketRequesterReturnsFalseWhenUserHasNoRelationToTicket(): void
    {
        // Nenhum registro inserido para este ticket
        $this->assertFalse(PublicAction::isTicketRequester(self::TEST_TICKET_ID, self::TEST_USER_ID));
    }

    #[Test]
    public function isTicketRequesterReturnsFalseForDifferentUserOnSameTicket(): void
    {
        // Insere usuário 2 como solicitante
        $this->insertTicketUser(self::TEST_TICKET_ID, 2, type: 1);

        // Usuário 4 (tech) não é solicitante deste ticket de teste
        $this->assertFalse(PublicAction::isTicketRequester(self::TEST_TICKET_ID, 4));
    }

    // ------------------------------------------------------------------
    // tokenErrorContent — confirmação com GLPI __ carregado
    // ------------------------------------------------------------------

    #[Test]
    public function tokenErrorContentStringsAreNonEmptyWithGlpiTranslationsLoaded(): void
    {
        foreach (['used', 'expired', 'invalid_action', null] as $code) {
            $result = PublicAction::tokenErrorContent($code);
            $this->assertNotEmpty($result['title'],   "title vazio para código: " . var_export($code, true));
            $this->assertNotEmpty($result['message'], "message vazio para código: " . var_export($code, true));
        }
    }

    // ------------------------------------------------------------------
    // applyRecipientLocale
    // ------------------------------------------------------------------

    #[Test]
    public function applyRecipientLocaleDoesNotThrowForNullTokenData(): void
    {
        // Não deve lançar exceção
        PublicAction::applyRecipientLocale(null);
        $this->assertTrue(true);
    }

    #[Test]
    public function applyRecipientLocaleDoesNotThrowForZeroUserId(): void
    {
        PublicAction::applyRecipientLocale(['users_id' => 0]);
        $this->assertTrue(true);
    }

    #[Test]
    public function applyRecipientLocaleDoesNotThrowForNonExistentUserId(): void
    {
        PublicAction::applyRecipientLocale(['users_id' => 999999]);
        $this->assertTrue(true);
    }

    // ------------------------------------------------------------------
    // ticketSummary
    // ------------------------------------------------------------------

    #[Test]
    public function ticketSummaryReturnsArrayWithIdAndSubtitleForMissingTicket(): void
    {
        $result = PublicAction::ticketSummary(999999);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('subtitle', $result);
        $this->assertSame(999999, $result['id']);
    }

    #[Test]
    public function ticketSummaryReturnsCorrectIdForExistingTicket(): void
    {
        // Ticket 295 foi criado durante os testes de configuração do ambiente
        $result = PublicAction::ticketSummary(295);

        $this->assertIsArray($result);
        $this->assertSame(295, $result['id']);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('subtitle', $result);
        $this->assertStringContainsString('295', $result['subtitle']);
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function insertTicketUser(int $ticketId, int $userId, int $type): void
    {
        $this->db()->insert('glpi_tickets_users', [
            'tickets_id'        => $ticketId,
            'users_id'          => $userId,
            'type'              => $type,
            'use_notification'  => 1,
            'alternative_email' => '',
        ]);
    }
}
