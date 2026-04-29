<?php

namespace Vasoft\Joke\Templator\Tests\Unit\Render\Handlers;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Templator\Ast\TagNode;
use Vasoft\Joke\Templator\Exceptions\RenderingException;
use Vasoft\Joke\Templator\Render\Handlers\EachHandler;
use Vasoft\Joke\Templator\Tests\Fixtures\MockRenderer;

#[Group("skip")]
class EachHandlerTest extends TestCase
{
    private EachHandler $handler;
    private MockRenderer $renderer;

    protected function setUp(): void
    {
        $this->handler = new EachHandler();
        $this->renderer = new MockRenderer();
    }

    public function testEachRendersChildrenForEachItem(): void
    {
        $node = new TagNode('each', 'j-each', ['items' => 'users', 'as' => 'user'], ['dummy']);
        $context = [
            'users' => [
                ['name' => 'Alice'],
                ['name' => 'Bob']
            ]
        ];

        $result = $this->handler->handle($node, $context, $this->renderer);
        self::assertSame('[RENDERED_CHILDREN][RENDERED_CHILDREN]', $result);
        self::assertCount(2, $this->renderer->renderedContexts);

        // Проверяем, что каждый раз передаётся правильный контекст
        self::assertArrayHasKey('user', $this->renderer->renderedContexts[0]);
        self::assertSame(['name' => 'Alice'], $this->renderer->renderedContexts[0]['user']);

        self::assertArrayHasKey('user', $this->renderer->renderedContexts[1]);
        self::assertSame(['name' => 'Bob'], $this->renderer->renderedContexts[1]['user']);
    }

    public function testEachRequiresItemsAndAsAttributes(): void
    {
        self::expectException(RenderingException::class);
        self::expectExceptionMessage("Attribute 'items' is required for <j-each>");

        $node = new TagNode('each', 'j-each', ['as' => 'item'], []);
        $this->handler->handle($node, [], $this->renderer);
    }

    public function testEachThrowsExceptionIfItemsIsNotArray(): void
    {
        self::expectException(RenderingException::class);
        self::expectExceptionMessage("Value at path 'data' is not an array");

        $node = new TagNode('each', 'j-each', ['items' => 'data', 'as' => 'item'], []);
        $context = ['data' => 'not an array'];
        $this->handler->handle($node, $context, $this->renderer);
    }

    public function testEachHandlesNestedItemsPath(): void
    {
        $node = new TagNode('each', 'j-each', ['items' => 'list.items', 'as' => 'item'], ['dummy']);
        $context = [
            'list' => [
                'items' => [['id' => 1], ['id' => 2]]
            ]
        ];

        $result = $this->handler->handle($node, $context, $this->renderer);
        self::assertSame('[RENDERED_CHILDREN][RENDERED_CHILDREN]', $result);
        self::assertCount(2, $this->renderer->renderedContexts);
    }
}
