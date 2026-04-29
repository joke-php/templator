<?php

namespace Vasoft\Joke\Templator\Tests\Unit\Render;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Templator\Ast\TagNode;
use Vasoft\Joke\Templator\Ast\TextNode;
use Vasoft\Joke\Templator\Exceptions\RenderingException;
use Vasoft\Joke\Templator\Render\DefaultRenderer;
use Vasoft\Joke\Templator\Tests\Fixtures\DummyTagHandler;

#[Group("skip")]
class DefaultRendererTest extends TestCase
{
    private DefaultRenderer $renderer;
    private DummyTagHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = new DefaultRenderer();
        $this->handler = new DummyTagHandler();
    }

    public function testRenderTextNodes(): void
    {
        $nodes = [
            new TextNode('Hello '),
            new TextNode('World!')
        ];

        $result = $this->renderer->optimizeStaticNodes($nodes, []);
        self::assertSame($nodes, $result);
    }

    public function testRenderMixedNodes(): void
    {
        $this->renderer->registerTag('test', $this->handler);

        $nodes = [
            new TagNode('test', 'j-test'),
            new TagNode('test', 'j-test', static: true)
        ];

        $context = ['key' => 'value'];
        $result = $this->renderer->optimizeStaticNodes($nodes, $context);

        self::assertCount(1, $this->handler->calls);
        self::assertSame($context, $this->handler->calls[0]['context']);
        self::assertInstanceOf(DefaultRenderer::class, $this->handler->calls[0]['renderer']);

        self::assertInstanceOf(TextNode::class, $result[1]);
        self::assertSame('[HANDLED:test]', $result[1]->content);
    }

    public function testRenderStaticNodes(): void
    {
        $this->renderer->registerTag('test', $this->handler);

        $nodes = [
            new TagNode('test', 'j-test', static: true),
            new TagNode('test', 'j-test', static: true)
        ];

        $context = ['key' => 'value'];
        $result = $this->renderer->optimizeStaticNodes($nodes, $context);

        self::assertCount(2, $this->handler->calls);
        self::assertSame($context, $this->handler->calls[0]['context']);
        self::assertSame($context, $this->handler->calls[1]['context']);
        self::assertInstanceOf(DefaultRenderer::class, $this->handler->calls[0]['renderer']);
        self::assertInstanceOf(DefaultRenderer::class, $this->handler->calls[1]['renderer']);

        self::assertInstanceOf(TextNode::class, $result[0]);
        self::assertSame('[HANDLED:test]', $result[0]->content);
        self::assertInstanceOf(TextNode::class, $result[1]);
        self::assertSame('[HANDLED:test]', $result[1]->content);
    }

//    public function testRenderMixedNodes1(): void
//    {
//        $this->renderer->registerTag('echo', $this->handler);
//
//        $nodes = [
//            new TextNode('Start'),
//            new TagNode('echo', 'j-echo', [], []),
//            new TextNode('End')
//        ];
//
//        $result = $this->renderer->optimizeStaticNodes($nodes, []);
//        self::assertSame('Start[HANDLED:echo]End', $result);
//    }

    public function testThrowsExceptionForUnregisteredTag(): void
    {
        self::expectException(RenderingException::class);
        self::expectExceptionMessage("No handler registered for tag 'j-unknown'");

        $nodes = [new TagNode('unknown', 'j-unknown', static: true)];
        $this->renderer->optimizeStaticNodes($nodes, []);
    }

//    public function testThrowsExceptionForUnknownNodeType(): void
//    {
//        self::expectException(RenderingException::class);
//        self::expectExceptionMessage('Unknown node type');
//
//        $fakeNode = new class ( ) implements NodeInterface { public bool $static = true; };
//        $this->renderer->optimizeStaticNodes([$fakeNode], []);
//    }

    public function testRegisterTagReturnsSelfForFluentInterface(): void
    {
        $result = $this->renderer->registerTag('test', $this->handler);
        self::assertSame($this->renderer, $result);
    }

    public function testRenderEmptyNodeList(): void
    {
        $result = $this->renderer->optimizeStaticNodes([], []);
        self::assertSame([], $result);
    }
}
