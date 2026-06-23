<?php

namespace GlpiPlugin\Mailaprove\Tests\Integration;

use GlpiPlugin\Mailaprove\Token;
use PHPUnit\Framework\Attributes\Test;

class TokenIntegrationTest extends AbstractGlpiTestCase
{
    #[Test]
    public function generateTokenReturnsA64CharHexString(): void
    {
        $raw = Token::generateToken(
            Token::ACTION_VALIDATION_APPROVE,
            self::TEST_TICKET_ID,
            self::TEST_ITEM_ID,
            self::TEST_USER_ID
        );

        $this->assertIsString($raw);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $raw);
    }

    #[Test]
    public function generateTokenCreatesRecordInDatabaseWithCorrectFields(): void
    {
        $raw  = $this->makeToken(Token::ACTION_VALIDATION_APPROVE);
        $hash = hash('sha256', $raw);

        $row = $this->db()->request([
            'FROM'  => Token::$table,
            'WHERE' => ['token_hash' => $hash],
            'LIMIT' => 1,
        ])->current();

        $this->assertNotFalse($row, 'Registro do token não encontrado no banco');
        $this->assertSame(Token::ACTION_VALIDATION_APPROVE, $row['action_type']);
        $this->assertSame(self::TEST_TICKET_ID, (int) $row['tickets_id']);
        $this->assertSame(self::TEST_ITEM_ID,   (int) $row['items_id']);
        $this->assertSame(self::TEST_USER_ID,   (int) $row['users_id']);
        $this->assertSame(0, (int) $row['is_used']);
        $this->assertNotEmpty($row['date_expiration']);
    }

    #[Test]
    public function generateTokenExpirationIsInTheFuture(): void
    {
        $raw  = $this->makeToken();
        $hash = hash('sha256', $raw);

        $row = $this->db()->request([
            'FROM'  => Token::$table,
            'WHERE' => ['token_hash' => $hash],
        ])->current();

        $this->assertGreaterThan(time(), strtotime($row['date_expiration']));
    }

    #[Test]
    public function generateTokenInvalidatesPreviousTokensForSameActionAndItem(): void
    {
        $raw1 = $this->makeToken(Token::ACTION_VALIDATION_APPROVE);

        // Reset the per-request cache so the second call generates a new token
        // (the cache is intentional in production to prevent multiple recipients
        // from getting different tokens in the same notification dispatch)
        $ref = new \ReflectionProperty(Token::class, 'tokenCache');
        $ref->setAccessible(true);
        $ref->setValue(null, []);

        $raw2 = $this->makeToken(Token::ACTION_VALIDATION_APPROVE);

        $this->assertNotSame($raw1, $raw2, 'Segunda chamada deve gerar token distinto após reset do cache');

        $hash1 = hash('sha256', $raw1);
        $row1  = $this->db()->request([
            'FROM'  => Token::$table,
            'WHERE' => ['token_hash' => $hash1],
        ])->current();

        $this->assertSame(1, (int) $row1['is_used'], 'Token anterior deve ser invalidado');
        $this->assertTrue(Token::validateTokenWithStatus($raw2)['valid']);
    }

    #[Test]
    public function generateTokenReturnsDifferentRawTokensEachTime(): void
    {
        $raw1 = $this->makeToken(Token::ACTION_VALIDATION_APPROVE);
        $raw2 = $this->makeToken(Token::ACTION_VALIDATION_REJECT);

        $this->assertNotSame($raw1, $raw2);
    }

    #[Test]
    public function validateTokenWithStatusReturnsTrueForFreshToken(): void
    {
        $raw = $this->makeToken();

        $result = Token::validateTokenWithStatus($raw);

        $this->assertTrue($result['valid']);
        $this->assertNull($result['error']);
        $this->assertIsArray($result['data']);
    }

    #[Test]
    public function validateTokenWithStatusReturnsInvalidForBadHexFormat(): void
    {
        $result = Token::validateTokenWithStatus('nao_e_hex_valido');

        $this->assertFalse($result['valid']);
        $this->assertSame('invalid', $result['error']);
        $this->assertNull($result['data']);
    }

    #[Test]
    public function validateTokenWithStatusReturnsInvalidForUnknownToken(): void
    {
        $result = Token::validateTokenWithStatus(bin2hex(random_bytes(32)));

        $this->assertFalse($result['valid']);
        $this->assertSame('invalid', $result['error']);
    }

    #[Test]
    public function validateTokenWithStatusReturnsUsedForAlreadyUsedToken(): void
    {
        $raw = $this->makeToken();

        // Marca como usado via claim
        Token::claimTokenWithStatus($raw, Token::ACTION_VALIDATION_APPROVE);

        $result = Token::validateTokenWithStatus($raw);

        $this->assertFalse($result['valid']);
        $this->assertSame('used', $result['error']);
        $this->assertNotNull($result['data']); // dados ainda retornados para auditoria
    }

    #[Test]
    public function validateTokenWithStatusReturnsExpiredForExpiredToken(): void
    {
        // Insere token expirado diretamente no banco
        $this->insertExpiredToken(Token::ACTION_VALIDATION_APPROVE, false);

        // Valida via hash de um token expirado — precisamos do rawToken para isso
        // Criamos via generateToken e depois atualizamos a data de expiração
        $raw  = $this->makeToken(Token::ACTION_VALIDATION_REJECT); // ação diferente
        $hash = hash('sha256', $raw);

        $this->db()->update(Token::$table, [
            'date_expiration' => date('Y-m-d H:i:s', strtotime('-1 hour')),
        ], ['token_hash' => $hash]);

        $result = Token::validateTokenWithStatus($raw);

        $this->assertFalse($result['valid']);
        $this->assertSame('expired', $result['error']);
    }

    #[Test]
    public function claimTokenWithStatusMarksTokenAsUsedOnSuccess(): void
    {
        $raw  = $this->makeToken(Token::ACTION_VALIDATION_APPROVE);
        $hash = hash('sha256', $raw);

        $result = Token::claimTokenWithStatus($raw, Token::ACTION_VALIDATION_APPROVE);

        $this->assertTrue($result['valid']);

        $row = $this->db()->request([
            'FROM'  => Token::$table,
            'WHERE' => ['token_hash' => $hash],
        ])->current();

        $this->assertSame(1, (int) $row['is_used']);
        $this->assertNotEmpty($row['date_used']);
        $this->assertSame('claimed', $row['use_reason']);
    }

    #[Test]
    public function claimTokenWithStatusRejectsAlreadyUsedToken(): void
    {
        $raw = $this->makeToken(Token::ACTION_VALIDATION_APPROVE);

        // Primeiro claim — deve funcionar
        $first = Token::claimTokenWithStatus($raw, Token::ACTION_VALIDATION_APPROVE);
        $this->assertTrue($first['valid']);

        // Segundo claim — deve rejeitar
        $second = Token::claimTokenWithStatus($raw, Token::ACTION_VALIDATION_APPROVE);
        $this->assertFalse($second['valid']);
        $this->assertSame('used', $second['error']);
    }

    #[Test]
    public function claimTokenWithStatusRejectsWrongActionType(): void
    {
        $raw = $this->makeToken(Token::ACTION_VALIDATION_APPROVE);

        $result = Token::claimTokenWithStatus($raw, Token::ACTION_VALIDATION_REJECT);

        $this->assertFalse($result['valid']);
        $this->assertSame('invalid_action', $result['error']);
        // Token NÃO deve ter sido marcado como usado
        $hash = hash('sha256', $raw);
        $row  = $this->db()->request([
            'FROM'  => Token::$table,
            'WHERE' => ['token_hash' => $hash],
        ])->current();
        $this->assertSame(0, (int) $row['is_used']);
    }

    #[Test]
    public function claimTokenWithStatusRejectsInvalidTokenFormat(): void
    {
        $result = Token::claimTokenWithStatus('formato_invalido', Token::ACTION_VALIDATION_APPROVE);

        $this->assertFalse($result['valid']);
        $this->assertSame('invalid', $result['error']);
    }

    #[Test]
    public function claimTokenWithStatusWithNullActionAcceptsAnyType(): void
    {
        $raw = $this->makeToken(Token::ACTION_SOLUTION_APPROVE);

        $result = Token::claimTokenWithStatus($raw, null);

        $this->assertTrue($result['valid']);
    }

    #[Test]
    public function markRelatedAsUsedInvalidatesAllMatchingActionTypes(): void
    {
        $rawApprove = $this->makeToken(Token::ACTION_VALIDATION_APPROVE);
        $rawReject  = $this->makeToken(Token::ACTION_VALIDATION_REJECT);

        $count = Token::markRelatedAsUsed(
            [Token::ACTION_VALIDATION_APPROVE, Token::ACTION_VALIDATION_REJECT],
            self::TEST_TICKET_ID,
            self::TEST_ITEM_ID
        );

        $this->assertSame(2, $count);

        foreach ([$rawApprove, $rawReject] as $raw) {
            $result = Token::validateTokenWithStatus($raw);
            $this->assertFalse($result['valid']);
            $this->assertSame('used', $result['error']);
        }
    }

    #[Test]
    public function markRelatedAsUsedDoesNotAffectDifferentActionTypes(): void
    {
        $rawApprove      = $this->makeToken(Token::ACTION_VALIDATION_APPROVE);
        $rawSatisfaction = $this->makeToken(Token::ACTION_SATISFACTION);

        Token::markRelatedAsUsed(
            [Token::ACTION_VALIDATION_APPROVE],
            self::TEST_TICKET_ID,
            self::TEST_ITEM_ID
        );

        // Satisfação não deve ter sido afetada
        $result = Token::validateTokenWithStatus($rawSatisfaction);
        $this->assertTrue($result['valid']);
    }

    #[Test]
    public function markRelatedAsUsedWithEmptyArrayReturnsZero(): void
    {
        $this->makeToken(Token::ACTION_VALIDATION_APPROVE);

        $count = Token::markRelatedAsUsed([], self::TEST_TICKET_ID, self::TEST_ITEM_ID);

        $this->assertSame(0, $count);
    }

    #[Test]
    public function cronCleanExpiredTokensRemovesExpiredUnusedTokens(): void
    {
        $this->insertExpiredToken(Token::ACTION_VALIDATION_APPROVE, false);

        $cronTask = $this->createMock(\CronTask::class);
        $cronTask->method('addVolume')->willReturn(null);

        $countBefore = $this->countTestTokens();
        $this->assertGreaterThan(0, $countBefore, 'Deve haver tokens de teste antes da limpeza');

        Token::cronCleanExpiredTokens($cronTask);

        $countAfter = $this->countTestTokens();
        $this->assertLessThan($countBefore, $countAfter, 'Tokens expirados devem ter sido removidos');
    }

    #[Test]
    public function cronCleanExpiredTokensRemovesOldUsedTokens(): void
    {
        // Token usado há 60 dias (acima do retention padrão de 30)
        $this->insertExpiredToken(
            Token::ACTION_VALIDATION_APPROVE,
            true,
            date('Y-m-d H:i:s', strtotime('-60 days'))
        );

        $cronTask = $this->createMock(\CronTask::class);
        $cronTask->method('addVolume')->willReturn(null);

        $countBefore = $this->countTestTokens();
        Token::cronCleanExpiredTokens($cronTask);
        $countAfter = $this->countTestTokens();

        $this->assertLessThan($countBefore, $countAfter);
    }

    #[Test]
    public function cronCleanExpiredTokensKeepsRecentValidTokens(): void
    {
        $raw = $this->makeToken(); // token válido, não expirado

        $cronTask = $this->createMock(\CronTask::class);
        $cronTask->method('addVolume')->willReturn(null);

        Token::cronCleanExpiredTokens($cronTask);

        // Token válido deve continuar existindo
        $result = Token::validateTokenWithStatus($raw);
        $this->assertTrue($result['valid']);
    }

    private function countTestTokens(): int
    {
        $rows = $this->db()->request([
            'FROM'   => Token::$table,
            'WHERE'  => ['tickets_id' => self::TEST_TICKET_ID],
            'COUNT'  => 'cnt',
        ]);

        return (int) ($rows->current()['cnt'] ?? 0);
    }
}
