<?php

namespace Vasoft\Joke\Templator\Tests\Unit\Render\Handlers;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Templator\Ast\TagNode;
use Vasoft\Joke\Templator\Exceptions\RenderingException;
use Vasoft\Joke\Templator\Render\Handlers\IfHandler;
use Vasoft\Joke\Templator\Tests\Fixtures\MockRenderer;

#[Group("skip")]
class IfHandlerTest extends TestCase
{
    private IfHandler $handler;
    private MockRenderer $renderer;

    protected function setUp(): void
    {
        $this->handler = new IfHandler();
        $this->renderer = new MockRenderer();
    }

    public function testIfRendersChildrenWhenConditionIsTrue(): void
    {
        $node = new TagNode('if', 'j-if', ['condition' => 'show'], ['dummy']);
        $context = ['show' => true];

        $result = $this->handler->handle($node, $context, $this->renderer);
        self::assertSame('[RENDERED_CHILDREN]', $result);
        self::assertCount(1, $this->renderer->renderedContexts);
        self::assertSame($context, $this->renderer->renderedContexts[0]);
    }

    public function testIfReturnsEmptyWhenConditionIsFalse(): void
    {
        $node = new TagNode('if', 'j-if', ['condition' => 'show'], ['dummy']);
        $context = ['show' => false];

        $result = $this->handler->handle($node, $context, $this->renderer);
        self::assertSame('', $result);
        self::assertEmpty($this->renderer->renderedContexts);
    }

    public function testIfRequiresConditionAttribute(): void
    {
        self::expectException(RenderingException::class);
        self::expectExceptionMessage("Attribute 'condition' is required for <j-if>");

        $node = new TagNode('if', 'j-if', [], []);
        $this->handler->handle($node, [], $this->renderer);
    }

    public function testIfHandlesNestedConditionPath(): void
    {
        $node = new TagNode('if', 'j-if', ['condition' => 'user.active'], ['dummy']);
        $context = ['user' => ['active' => true]];

        $result = $this->handler->handle($node, $context, $this->renderer);
        self::assertSame('[RENDERED_CHILDREN]', $result);
    }
}
