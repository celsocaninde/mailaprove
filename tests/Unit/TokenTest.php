<?php

namespace GlpiPlugin\Mailaprove\Tests\Unit;

use GlpiPlugin\Mailaprove\Token;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class TokenTest extends TestCase
{
    private array $savedCfgGlpi = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->savedCfgGlpi = $GLOBALS['CFG_GLPI'] ?? [];
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $GLOBALS['CFG_GLPI'] = $this->savedCfgGlpi;
    }

    #[Test]
    public function actionConstantsHaveExpectedStringValues(): void
    {
        $this->assertSame('validation_approve', Token::ACTION_VALIDATION_APPROVE);
        $this->assertSame('validation_reject',  Token::ACTION_VALIDATION_REJECT);
        $this->assertSame('solution_approve',   Token::ACTION_SOLUTION_APPROVE);
        $this->assertSame('solution_reject',    Token::ACTION_SOLUTION_REJECT);
        $this->assertSame('satisfaction',       Token::ACTION_SATISFACTION);
    }

    #[Test]
    public function allActionConstantsAreDistinct(): void
    {
        $constants = [
            Token::ACTION_VALIDATION_APPROVE,
            Token::ACTION_VALIDATION_REJECT,
            Token::ACTION_SOLUTION_APPROVE,
            Token::ACTION_SOLUTION_REJECT,
            Token::ACTION_SATISFACTION,
        ];

        $this->assertSame(count($constants), count(array_unique($constants)));
    }

    #[Test]
    #[DataProvider('validTokenFormats')]
    public function acceptsValidHexTokenFormat(string $token): void
    {
        $method = new ReflectionMethod(Token::class, 'hasValidRawTokenFormat');

        $this->assertTrue($method->invoke(null, $token));
    }

    public static function validTokenFormats(): array
    {
        return [
            'hex minúsculo 64 chars' => [str_repeat('a', 64)],
            'hex maiúsculo 64 chars' => [str_repeat('A', 64)],
            'hex misto'              => [str_repeat('0f', 32)],
            'token aleatório real'   => [bin2hex(random_bytes(32))],
        ];
    }

    #[Test]
    #[DataProvider('invalidTokenFormats')]
    public function rejectsInvalidHexTokenFormat(string $token): void
    {
        $method = new ReflectionMethod(Token::class, 'hasValidRawTokenFormat');

        $this->assertFalse($method->invoke(null, $token));
    }

    public static function invalidTokenFormats(): array
    {
        return [
            'muito curto (63 chars)'   => [str_repeat('a', 63)],
            'muito longo (65 chars)'   => [str_repeat('a', 65)],
            'caracteres não-hex'       => [str_repeat('g', 64)],
            'string vazia'             => [''],
            'termina com espaço'       => [str_repeat('a', 63) . ' '],
            'contém nova linha'        => [str_repeat('a', 63) . "\n"],
            'contém ponto'             => [str_repeat('a', 63) . '.'],
            'injeção SQL tentativa'    => ["'; DROP TABLE users;--" . str_repeat('0', 43)],
            'base64 (não hex)'         => [base64_encode(random_bytes(32))],
        ];
    }

    #[Test]
    #[DataProvider('actionUrlEndpoints')]
    public function buildActionUrlContainsCorrectEndpointForEachAction(
        string $actionType,
        string $expectedFile
    ): void {
        $GLOBALS['CFG_GLPI'] = ['url_base' => 'https://glpi.example.com'];

        $url = Token::buildActionUrl('abc123token', $actionType);

        $this->assertStringContainsString($expectedFile, $url);
        $this->assertStringContainsString('/plugins/mailaprove/front/', $url);
        $this->assertStringContainsString('https://glpi.example.com', $url);
        $this->assertStringContainsString('token=', $url);
    }

    public static function actionUrlEndpoints(): array
    {
        return [
            'aprovação de validação' => [Token::ACTION_VALIDATION_APPROVE, 'approve.php'],
            'recusa de validação'    => [Token::ACTION_VALIDATION_REJECT,  'reject.php'],
            'aceite de solução'      => [Token::ACTION_SOLUTION_APPROVE,   'solution_approve.php'],
            'recusa de solução'      => [Token::ACTION_SOLUTION_REJECT,    'solution_reject.php'],
            'satisfação'             => [Token::ACTION_SATISFACTION,        'satisfaction.php'],
        ];
    }

    #[Test]
    public function buildActionUrlProperlyUrlEncodesToken(): void
    {
        $GLOBALS['CFG_GLPI'] = ['url_base' => 'https://glpi.example.com'];
        $rawToken = bin2hex(random_bytes(32));

        $url = Token::buildActionUrl($rawToken, Token::ACTION_VALIDATION_APPROVE);

        $this->assertStringContainsString('token=' . urlencode($rawToken), $url);
    }

    #[Test]
    public function buildActionUrlStripsTrailingSlashFromBaseUrl(): void
    {
        $GLOBALS['CFG_GLPI'] = ['url_base' => 'https://glpi.example.com/'];

        $url = Token::buildActionUrl('abc', Token::ACTION_VALIDATION_APPROVE);

        $this->assertStringNotContainsString('//plugins', $url);
    }

    #[Test]
    public function buildActionUrlFallsBackToApprovePhpForUnknownActionType(): void
    {
        $GLOBALS['CFG_GLPI'] = ['url_base' => 'https://glpi.example.com'];

        $url = Token::buildActionUrl('abc', 'acao_desconhecida');

        $this->assertStringContainsString('approve.php', $url);
    }

    #[Test]
    public function usageMetadataContainsAllRequiredKeys(): void
    {
        $method = new ReflectionMethod(Token::class, 'usageMetadata');

        $result = $method->invoke(null, 'claimed');

        $this->assertArrayHasKey('use_reason', $result);
        $this->assertArrayHasKey('use_ip', $result);
        $this->assertArrayHasKey('use_user_agent', $result);
        $this->assertArrayHasKey('date_used', $result);
    }

    #[Test]
    public function usageMetadataTruncatesReasonToMaxThirtyTwoChars(): void
    {
        $method = new ReflectionMethod(Token::class, 'usageMetadata');

        $result = $method->invoke(null, str_repeat('x', 50));

        $this->assertLessThanOrEqual(32, strlen($result['use_reason']));
    }

    #[Test]
    public function usageMetadataDateUsedMatchesMysqlDatetimeFormat(): void
    {
        $method = new ReflectionMethod(Token::class, 'usageMetadata');

        $result = $method->invoke(null, 'test');

        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
            $result['date_used']
        );
    }

    #[Test]
    public function usageMetadataPreservesShortReason(): void
    {
        $method = new ReflectionMethod(Token::class, 'usageMetadata');

        $result = $method->invoke(null, 'claimed');

        $this->assertSame('claimed', $result['use_reason']);
    }
}
