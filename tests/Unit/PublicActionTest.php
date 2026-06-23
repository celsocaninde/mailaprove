<?php

namespace GlpiPlugin\Mailaprove\Tests\Unit;

use GlpiPlugin\Mailaprove\PublicAction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Testes unitários de PublicAction — sem acesso ao banco de dados.
 *
 * Cobrem: mensagens de erro de token, preview de texto, e guards de ID zero.
 */
class PublicActionTest extends TestCase
{
    // ------------------------------------------------------------------
    // tokenErrorContent
    // ------------------------------------------------------------------

    #[Test]
    #[DataProvider('tokenErrorCodes')]
    public function tokenErrorContentReturnsArrayWithTitleAndMessage(string $errorCode): void
    {
        $result = PublicAction::tokenErrorContent($errorCode);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertNotEmpty($result['title']);
        $this->assertNotEmpty($result['message']);
    }

    public static function tokenErrorCodes(): array
    {
        return [
            'used'           => ['used'],
            'expired'        => ['expired'],
            'invalid_action' => ['invalid_action'],
            'null'           => [''],        // default branch
        ];
    }

    #[Test]
    public function tokenErrorContentForNullMatchesDefaultBranch(): void
    {
        $fromNull    = PublicAction::tokenErrorContent(null);
        $fromUnknown = PublicAction::tokenErrorContent('erro_desconhecido_xyz');

        $this->assertSame($fromNull['title'],   $fromUnknown['title']);
        $this->assertSame($fromNull['message'], $fromUnknown['message']);
    }

    #[Test]
    public function tokenErrorContentUsedIsDifferentFromExpired(): void
    {
        $used    = PublicAction::tokenErrorContent('used');
        $expired = PublicAction::tokenErrorContent('expired');

        // Cada erro deve ter mensagem distinta para orientar corretamente o usuário
        $this->assertNotSame($used['message'], $expired['message']);
    }

    #[Test]
    public function tokenErrorContentInvalidActionIsDifferentFromInvalid(): void
    {
        $invalidAction = PublicAction::tokenErrorContent('invalid_action');
        $invalid       = PublicAction::tokenErrorContent(null);

        $this->assertNotSame($invalidAction['message'], $invalid['message']);
    }

    // ------------------------------------------------------------------
    // textPreview (private — testado via ReflectionMethod)
    // ------------------------------------------------------------------

    #[Test]
    #[DataProvider('textPreviewCases')]
    public function textPreviewStripsHtmlAndTruncatesCorrectly(
        string $input,
        int $maxLength,
        string $expected
    ): void {
        $method = new ReflectionMethod(PublicAction::class, 'textPreview');

        $result = $method->invoke(null, $input, $maxLength);

        $this->assertSame($expected, $result);
    }

    public static function textPreviewCases(): array
    {
        return [
            'texto curto permanece intacto' => [
                'Olá mundo', 100, 'Olá mundo',
            ],
            'texto longo é truncado com reticências' => [
                'Hello world extra', 10, 'Hello wor...',
            ],
            'tags HTML são removidas' => [
                '<p><strong>Olá</strong> mundo</p>', 100, 'Olá mundo',
            ],
            'HTML removido e depois truncado' => [
                '<p>' . str_repeat('a', 30) . '</p>', 10, str_repeat('a', 9) . '...',
            ],
            'string vazia retorna vazia' => [
                '', 100, '',
            ],
            'apenas tags HTML retorna vazio' => [
                '<p><br/><span></span></p>', 100, '',
            ],
            'espaços e quebras de linha são normalizados' => [
                "Texto  \n  com  espaços", 100, 'Texto com espaços',
            ],
            'texto exatamente no limite não é truncado' => [
                str_repeat('x', 10), 10, str_repeat('x', 10),
            ],
        ];
    }

    // ------------------------------------------------------------------
    // isTicketRequester — retornos antecipados sem DB
    // ------------------------------------------------------------------

    #[Test]
    public function isTicketRequesterReturnsFalseWhenTicketIdIsZero(): void
    {
        // Retorno antecipado antes de tocar o DB
        $this->assertFalse(PublicAction::isTicketRequester(0, 2));
    }

    #[Test]
    public function isTicketRequesterReturnsFalseWhenUserIdIsZero(): void
    {
        $this->assertFalse(PublicAction::isTicketRequester(295, 0));
    }

    #[Test]
    public function isTicketRequesterReturnsFalseWhenBothIdsAreZero(): void
    {
        $this->assertFalse(PublicAction::isTicketRequester(0, 0));
    }

    #[Test]
    public function isTicketRequesterReturnsFalseForNegativeIds(): void
    {
        $this->assertFalse(PublicAction::isTicketRequester(-1, -1));
    }
}
