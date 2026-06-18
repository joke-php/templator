<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Tests;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Templator\Parser\Node\BlockNode;
use Vasoft\Joke\Templator\Parser\Node\PrintNode;
use Vasoft\Joke\Templator\Parser\Node\TextNode;
use Vasoft\Joke\Templator\TemplatorConfig;

/**
 * @internal
 */
#[CoversNothing]
abstract class ProcessorTestBase extends TestCase
{
    protected ServiceContainer $container;
    protected TemplatorConfig $config;

    protected function setUp(): void
    {
        $this->config = new TemplatorConfig();
        $this->container = new ServiceContainer();
        $this->container->registerSingleton(TemplatorConfig::class, $this->config);
    }

    public function getDefaultContext(): array
    {
        return [
            'testVariable' => 'testVariableValue',
            'expression1' => true,
            'expression2' => true,
            'list' => ['a', 'b'],
        ];
    }

    public function getDefaultAst(): array
    {
        $ifBlock = new BlockNode('if', 'expression1');
        $ifBlock->addChild(new TextNode('branch1'));
        $ifBlock->openBranch('elseif', 'expression2');
        $ifBlock->addChild(new TextNode('branch2'));
        $foreachBlock = new BlockNode('foreach', 'id, item in list');
        $foreachBlock->addChild(new PrintNode('id'));
        $foreachBlock->addChild(new TextNode(':'));
        $foreachBlock->addChild(new PrintNode('item'));
        $foreachBlock->addChild(new TextNode("\n"));

        return [
            new PrintNode('testVariable'),
            new TextNode("\nsingle text\n"),
            $ifBlock,
            new TextNode("\n"),
            $foreachBlock,
        ];
    }
}
