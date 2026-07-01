<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Tests;

use Vasoft\Joke\Config\Exceptions\ConfigException;
use Vasoft\Joke\Templator\Exceptions\TemplatorException;
use Vasoft\Joke\Templator\TemplatorConfig;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Templator\TemplatorConfig;
 */
final class TemplatorConfigTest extends TestCase
{
    public function testDefaults(): void
    {
        $config = new TemplatorConfig();
        self::assertSame('UTF-8', $config->encoding);
    }

    public function testNodeHandlerNotRegistered(): void
    {
        $config = new TemplatorConfig();
        self::expectException(TemplatorException::class);
        self::expectExceptionMessage("Handler for 'stdClass' not found.");
        $config->getNodeHandler(\stdClass::class);
    }

    public function testDirectiveHandlerNotRegistered(): void
    {
        $config = new TemplatorConfig();
        self::expectException(TemplatorException::class);
        self::expectExceptionMessage("Handler for directive 'unknown' not found.");
        $config->getDirectiveHandler('unknown');
    }

    public function testCustomEncoding(): void
    {
        $config = new TemplatorConfig();
        $config->setEncoding('windows-1251');
        self::assertSame('windows-1251', $config->encoding);
        $config->freeze();
        self::expectException(ConfigException::class);
        self::expectExceptionMessage('Cannot modify frozen configuration of [Vasoft\Joke\Templator\TemplatorConfig].');
        $config->setEncoding('UTF-8');
    }
}
