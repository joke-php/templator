<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Tests\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Templator\Container\DeferService;
use Vasoft\Joke\Templator\TemplatorConfig;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Templator\Container\DeferService
 */
#[TestDox('DeferService - служба работы с отложенным выводом контента')]
#[CoversClass(DeferService::class)]
final class DeferServiceTest extends TestCase
{
    #[TestDox('Генерирует плейсхолдер и заменяет значения с экранированием')]
    public function testDefer(): void
    {
        $expectedPlaceholder1 = '##___DEFER___page.title##';
        $expectedPlaceholder2 = '##___DEFER___page.number##';
        $preparedBody = $expectedPlaceholder1 . '--##--' . $expectedPlaceholder2;
        $expectedBody = 'test &lt;smal&gt;title&lt;/smal&gt;--##--1';

        $handler = new DeferService(new TemplatorConfig());
        $placeholder1 = $handler->register('page.title', 'test <smal>title</smal>');
        $placeholder2 = $handler->register('page.number', '1');
        $body = $handler->flush($preparedBody);

        self::assertSame($expectedPlaceholder1, $placeholder1);
        self::assertSame($expectedPlaceholder2, $placeholder2);
        self::assertSame($expectedBody, $body);
    }

    #[TestDox('Генерирует плейсхолдер и заменяет значения без экранирования')]
    public function testDeferRaw(): void
    {
        $expectedPlaceholder1 = '##___DEFER_RAW___page.title##';
        $expectedPlaceholder2 = '##___DEFER_RAW___page.number##';
        $preparedBody = $expectedPlaceholder1 . '--##--' . $expectedPlaceholder2;
        $expectedBody = 'test <smal>title</smal>--##--1';

        $handler = new DeferService(new TemplatorConfig());
        $placeholder1 = $handler->registerRaw('page.title', 'test <smal>title</smal>');
        $placeholder2 = $handler->registerRaw('page.number', '1');
        $body = $handler->flush($preparedBody);

        self::assertSame($expectedPlaceholder1, $placeholder1);
        self::assertSame($expectedPlaceholder2, $placeholder2);
        self::assertSame($expectedBody, $body);
    }

    #[TestDox('Перезаписывает ранее добавленное значение')]
    public function testDeferReset(): void
    {
        $expectedPlaceholder = '##___DEFER___page.title##';
        $expectedPlaceholderRaw = '##___DEFER_RAW___page.title##';
        $preparedBody = $expectedPlaceholder . '--##--' . $expectedPlaceholderRaw;
        $expectedBody1 = '&lt;smal&gt;title 1&lt;/smal&gt;--##--<smal>title1</smal>';
        $expectedBody2 = '&lt;smal&gt;title 2&lt;/smal&gt;--##--<smal>title2</smal>';

        $handler = new DeferService(new TemplatorConfig());
        $placeholder = $handler->register('page.title', '<smal>title 1</smal>');
        $placeholderRaw = $handler->registerRaw('page.title', '<smal>title1</smal>');
        $body = $handler->flush($preparedBody);
        self::assertSame($expectedPlaceholder, $placeholder);
        self::assertSame($expectedPlaceholderRaw, $placeholderRaw);
        self::assertSame($expectedBody1, $body);

        $placeholder = $handler->register('page.title', '<smal>title 2</smal>');
        $placeholderRaw = $handler->registerRaw('page.title', '<smal>title2</smal>');
        $body = $handler->flush($preparedBody);
        self::assertSame($expectedPlaceholder, $placeholder);
        self::assertSame($expectedPlaceholderRaw, $placeholderRaw);
        self::assertSame($expectedBody2, $body);
    }

    #[TestDox('clean очищает сохраненные значения, flush не замещает отсутствующие значения')]
    public function testDeferClean(): void
    {
        $expectedPlaceholder = '##___DEFER___page.title##';
        $expectedPlaceholderRaw = '##___DEFER_RAW___page.title##';
        $preparedBody = $expectedPlaceholder . '--##--' . $expectedPlaceholderRaw;
        $expectedBody1 = '&lt;smal&gt;title 1&lt;/smal&gt;--##--<smal>title1</smal>';

        $handler = new DeferService(new TemplatorConfig());
        $handler->register('page.title', '<smal>title 1</smal>');
        $handler->registerRaw('page.title', '<smal>title1</smal>');
        $body = $handler->flush($preparedBody);
        self::assertSame($expectedBody1, $body);

        $handler->clean();

        $body = $handler->flush($preparedBody);
        self::assertSame($preparedBody, $body);
    }
}
