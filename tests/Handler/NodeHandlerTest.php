<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Tests\Handler;

use Vasoft\Joke\Templator\Handler\NodeHandler;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Templator\Handler\NodeHandler
 */
final class NodeHandlerTest extends TestCase
{
    public function testToPhpArrayAccess(): void
    {
        $handler = new class extends NodeHandler {
            public function testAccess(string $path): string
            {
                return $this->toPhpArrayAccess($path);
            }

            public function compile(...$args): string
            {
                return '';
            }

            public function render(...$args): string
            {
                return '';
            }
        };

        self::assertSame("\$context['user']['name']", $handler->testAccess('user.name'));
    }

    public function testResolveValueReturnsDefaultIfKeyMissing(): void
    {
        $handler = new class extends NodeHandler {
            public function getVal(array $c, string $p, mixed $d): mixed
            {
                return $this->resolveValue($c, $p, $d, 'test');
            }

            public function compile(...$args): string
            {
                return '';
            }

            public function render(...$args): string
            {
                return '';
            }
        };

        $context = ['user' => ['name' => 'Alex']];

        self::assertNull($handler->getVal($context, 'user.age', null));
        self::assertSame('N/A', $handler->getVal($context, 'missing.key', 'N/A'));
        self::assertSame('Alex', $handler->getVal($context, 'user.name', 'N/A'));
    }
}
