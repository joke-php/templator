<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Parser;

use Vasoft\Joke\Templator\Container\DirectiveCollection;
use Vasoft\Joke\Templator\Container\DirectiveType;
use Vasoft\Joke\Templator\Contracts\Parser\NodeInterface;
use Vasoft\Joke\Templator\Contracts\Parser\ParserInterface;
use Vasoft\Joke\Templator\Exceptions\ParserException;
use Vasoft\Joke\Templator\Exceptions\TemplatorException;
use Vasoft\Joke\Templator\Lexer\PrintToken;
use Vasoft\Joke\Templator\Lexer\StatementToken;
use Vasoft\Joke\Templator\Lexer\TextToken;
use Vasoft\Joke\Templator\Parser\Node\BlockNode;
use Vasoft\Joke\Templator\Parser\Node\PrintNode;
use Vasoft\Joke\Templator\Parser\Node\StatementNode;
use Vasoft\Joke\Templator\Parser\Node\TextNode;
use Vasoft\Joke\Templator\TemplatorConfig;

/**
 * Парсер AST-дерева шаблонизатора Joke.
 *
 * Преобразует последовательность токенов, полученных от лексера,
 * в древовидную структуру (AST), состоящую из узлов TextNode и TagNode.
 * Поддерживает вложенные теги, самозакрывающиеся теги и текстовый контент.
 *
 * Обеспечивает строгую проверку структуры шаблона:
 * - контроль соответствия открывающих и закрывающих тегов
 * - обнаружение незакрытых тегов
 * - генерацию понятных сообщений об ошибках с указанием полных имён тегов (включая префикс 'j-').
 */
class DefaultParser implements ParserInterface
{
    private DirectiveCollection $directiveCollection;

    public function __construct(
        TemplatorConfig $config,
    ) {
        $this->directiveCollection = $config->directiveCollection;
    }

    public function parse(array $tokens): array
    {
        $rootNodes = [];
        $stack = [];

        foreach ($tokens as $token) {
            if ($token instanceof TextToken) {
                $node = new TextNode($token->raw);
                $this->attachNode($node, $stack, $rootNodes);
            } elseif ($token instanceof PrintToken) {
                $node = new PrintNode($token->raw);
                $this->attachNode($node, $stack, $rootNodes);
            } elseif ($token instanceof StatementToken) {
                $this->prepareStatementToken($token, $stack, $rootNodes);
            }
        }

        if (!empty($stack)) {
            $unclosed = implode(', ', array_map(static fn($n) => $n->fullTagName, $stack));

            throw new ParserException("Unclosed tag(s): {$unclosed}");
        }

        return $rootNodes;
    }

    private function prepareStatementToken(StatementToken $token, array &$stack, array &$rootNodes): void
    {
        $directive = $token->getDirective();
        $type = $this->directiveCollection->getType($token::class, $directive);

        switch ($type) {
            case DirectiveType::BEGIN:
                $node = new BlockNode($directive, $token->getArguments());
                $this->attachNode($node, $stack, $rootNodes);
                $stack[] = $node;
                break;

            case DirectiveType::END:
                if (empty($stack)) {
                    throw new ParserException("Unexpected end directive: {$directive}");
                }
                $openDirective = $this->directiveCollection->getOpenDirective($token::class, $directive);
                $last = array_pop($stack);
                if ($last->directive !== $openDirective) {
                    throw new ParserException(
                        "Mismatched block: expected end of {$last->directive}, got {$directive}",
                    );
                }
                break;

            case DirectiveType::BRANCH:
                $currentNode = array_last($stack);
                $openDirective = $this->directiveCollection->getOpenDirective($token::class, $directive);
                if ($openDirective !== $currentNode->directive) {
                    throw new TemplatorException("Unexpected branch '{$directive}'");
                }
                $currentNode->openBranch($directive, $token->getArguments());
                break;

            case DirectiveType::SINGLE:
                $node = new StatementNode($directive, $token->getArguments());
                $this->attachNode($node, $stack, $rootNodes);
                break;

            default:
                throw new ParserException("Unknown directive: {$directive}");
        }
    }

    private function attachNode(NodeInterface $node, array &$stack, array &$rootNodes): void
    {
        if ($stack && $stack[count($stack) - 1] instanceof BlockNode) {
            $stack[count($stack) - 1]->addChild($node);
        } else {
            $rootNodes[] = $node;
        }
    }
}
