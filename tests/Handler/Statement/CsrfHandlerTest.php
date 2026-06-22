<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Tests\Handler\Statement;

use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Http\Csrf\CsrfTokenManager;
use Vasoft\Joke\Http\HttpRequest;
use Vasoft\Joke\Templator\Handler\Statement\CsrfHandler;
use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Templator\Lexer\StatementToken;
use Vasoft\Joke\Templator\Parser\Node\StatementNode;
use Vasoft\Joke\Templator\Render\DefaultRenderer;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Templator\Handler\Statement\CsrfHandler
 */
final class CsrfHandlerTest extends TestCase
{
    private static DefaultRenderer $renderer;
    private static ServiceContainer $container;

    public static function setUpBeforeClass(): void
    {
        self::$renderer = self::getStubBuilder(DefaultRenderer::class)
            ->disableOriginalConstructor()
            ->getStub();
        self::$container = self::getStubBuilder(ServiceContainer::class)
            ->disableOriginalConstructor()
            ->getStub();
        $tokenManager = self::getStubBuilder(CsrfTokenManager::class)
            ->disableOriginalConstructor()
            ->getStub();
        $tokenManager
            ->method('getServerToken')
            ->willReturn('token');
        self::$container->method('get')->willReturnCallback(static function (string $className) use ($tokenManager) {
            return match ($className) {
                CsrfTokenManager::class => $tokenManager,
                HttpRequest::class => new HttpRequest(),
            };
        });
    }

    public function testCompile(): void
    {
        $handler = new CsrfHandler(self::$container);
        $node = new StatementNode(StatementToken::class, 'csrf', '');
        self::assertSame(
            <<<'PHP'
                    <?php
                        use Vasoft\Joke\Http\Csrf\CsrfTokenManager;
                        use Vasoft\Joke\Http\HttpRequest;
                        $tokenManager = $container->get(CsrfTokenManager::class);
                        $request = $container->get(HttpRequest::class);
                        if ($tokenManager !== null && $request !== null) {
                            echo $tokenManager->getServerToken($request);
                        }
                    ?>
                PHP,
            $handler->compile($node, self::$renderer, []),
        );
    }

    public function testRender(): void
    {
        $handler = new CsrfHandler(self::$container);
        $node = new StatementNode(StatementToken::class, 'csrf', '');
        self::assertSame(
            'token',
            $handler->render($node, self::$renderer, []),
        );
    }
}
