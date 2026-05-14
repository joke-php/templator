<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator;

use Vasoft\Joke\Config\AbstractConfig;
use Vasoft\Joke\Templator\Container\DirectiveCollection;
use Vasoft\Joke\Templator\Container\TokenCollection;
use Vasoft\Joke\Templator\Exceptions\TemplatorException;
use Vasoft\Joke\Templator\Handler\Directive\EachHandler;
use Vasoft\Joke\Templator\Handler\Directive\IfHandler;
use Vasoft\Joke\Templator\Handler\Node\BlockNodeHandler;
use Vasoft\Joke\Templator\Handler\Node\PrintNodeHandler;
use Vasoft\Joke\Templator\Handler\Node\StatementNodeHandler;
use Vasoft\Joke\Templator\Handler\Node\TextNodeHandler;
use Vasoft\Joke\Templator\Lexer\PrintToken;
use Vasoft\Joke\Templator\Lexer\StatementToken;
use Vasoft\Joke\Templator\Lexer\TokenDescriptor;
use Vasoft\Joke\Templator\Parser\Node\BlockNode;
use Vasoft\Joke\Templator\Parser\Node\PrintNode;
use Vasoft\Joke\Templator\Parser\Node\StatementNode;
use Vasoft\Joke\Templator\Parser\Node\TextNode;

class TemplatorConfig extends AbstractConfig
{
    public private(set) readonly TokenCollection $tokenCollection;
    public private(set) readonly DirectiveCollection $directiveCollection;

    private array $directiveHandler = [];
    private array $nodeHandler = [];

    public function __construct()
    {
        $this->tokenCollection = new TokenCollection();
        $this->directiveCollection = new DirectiveCollection();
        $this->initDefaults();
    }

    protected function initDefaults(): void
    {
        $this->tokenCollection->upsert(new TokenDescriptor('{{', '}}', PrintToken::class));
        $this->tokenCollection->upsert(new TokenDescriptor('{%', '%}', StatementToken::class));

        $this->addNodeHandler(TextNode::class, TextNodeHandler::class);
        $this->addNodeHandler(PrintNode::class, PrintNodeHandler::class);
        $this->addNodeHandler(BlockNode::class, BlockNodeHandler::class);
        $this->addNodeHandler(StatementNode::class, StatementNodeHandler::class);

        $this->directiveCollection->upsert(StatementToken::class, 'if', '/if', ['else', 'elseif']);
        $this->directiveCollection->upsert(StatementToken::class, 'foreach', '/foreach');

        $this->addDirectiveHandler('if', IfHandler::class);
        $this->addDirectiveHandler('foreach', EachHandler::class);
    }

    public function addDirectiveHandler(string $directive, string $handler): static
    {
        $this->directiveHandler[$directive] = $handler;

        return $this;
    }

    public function addNodeHandler(string $nodeClass, string $handler): static
    {
        $this->nodeHandler[$nodeClass] = $handler;

        return $this;
    }

    public function getNodeHandler(string $nodeClass): string
    {
        if (!isset($this->nodeHandler[$nodeClass])) {
            throw new TemplatorException("Handler for '{$nodeClass}' not found");
        }

        return $this->nodeHandler[$nodeClass];
    }

    public function getDirectiveHandler(string $directive): string
    {
        if (!isset($this->directiveHandler[$directive])) {
            throw new TemplatorException("Handler for directive '{$directive}' not found");
        }

        return $this->directiveHandler[$directive];
    }
}
