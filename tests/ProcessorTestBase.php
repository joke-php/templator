<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Tests;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Templator\Lexer\PrintToken;
use Vasoft\Joke\Templator\Lexer\StatementToken;
use Vasoft\Joke\Templator\Lexer\TextToken;
use Vasoft\Joke\Templator\Parser\Node\BlockNode;
use Vasoft\Joke\Templator\Parser\Node\PrintNode;
use Vasoft\Joke\Templator\Parser\Node\TextNode;
use Vasoft\Joke\Templator\TemplatorConfig;
use Vasoft\Joke\Templator\TemplatorProvider;

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
        $provider = new TemplatorProvider($this->container);
        $provider->boot();
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
        $ifBlock = new BlockNode(StatementToken::class, 'if', 'expression1');
        $ifBlock->addChild(new TextNode(TextToken::class, 'branch1'));
        $ifBlock->openBranch('elseif', 'expression2');
        $ifBlock->addChild(new TextNode(TextToken::class, 'branch2'));
        $foreachBlock = new BlockNode(StatementToken::class, 'foreach', 'id, item in list');
        $foreachBlock->addChild(new PrintNode(PrintToken::class, 'id'));
        $foreachBlock->addChild(new TextNode(TextToken::class, ':'));
        $foreachBlock->addChild(new PrintNode(PrintToken::class, 'item'));
        $foreachBlock->addChild(new TextNode(TextToken::class, "\n"));

        return [
            new PrintNode(PrintToken::class, 'testVariable'),
            new TextNode(TextToken::class, "\nsingle text\n"),
            $ifBlock,
            new TextNode(TextToken::class, "\n"),
            $foreachBlock,
        ];
    }
}
