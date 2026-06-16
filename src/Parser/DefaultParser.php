<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Parser;

use Vasoft\Joke\Templator\Container\DirectiveCollection;
use Vasoft\Joke\Templator\Container\DirectiveType;
use Vasoft\Joke\Templator\Contracts\Parser\NodeInterface;
use Vasoft\Joke\Templator\Contracts\Parser\ParserInterface;
use Vasoft\Joke\Templator\Contracts\TokenInterface;
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
 * Стандартный синтаксический анализатор.
 *
 * Преобразует последовательность токенов, полученных от лексера,
 * в древовидную структуру (AST), состоящую из узлов TextNode и TagNode.
 *
 * Основные возможности:
 * - Построение иерархии узлов (TextNode, PrintNode, StatementNode, BlockNode).
 * - Валидация структуры: проверка парности открывающих/закрывающих тегов.
 * - Обработка ветвлений (elseif, else) внутри блочных директив.
 * - Генерация понятных исключений при обнаружении синтаксических ошибок.
 */
class DefaultParser implements ParserInterface
{
    /**
     * Коллекция правил синтаксиса директив.
     * Используется для определения типа директивы (BEGIN, END, BRANCH, SINGLE).
     */
    private DirectiveCollection $directiveCollection;

    /**
     * Создает новый экземпляр парсера.
     *
     * @param TemplatorConfig $config конфигурация шаблонизатора, содержащая правила грамматики
     */
    public function __construct(
        TemplatorConfig $config,
    ) {
        $this->directiveCollection = $config->directiveCollection;
    }

    /**
     * {@inheritDoc}
     *
     * Выполняет синтаксический анализ массива токенов и возвращает корневые узлы AST.
     *
     * @param list<TokenInterface> $tokens массив токенов, полученный от лексера
     *
     * @return list<NodeInterface> массив корневых узлов абстрактного синтаксического дерева
     *
     * @throws ParserException если обнаружены ошибки структуры (незакрытые теги, неверная вложенность)
     */
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
            $unclosed = implode(', ', array_map(static fn($n) => $n->directive ?? 'unknown', $stack));

            throw new ParserException("Unclosed tag(s): '{$unclosed}'.");
        }

        return $rootNodes;
    }

    /**
     * Обрабатывает токен инструкции (StatementToken) и выполняет соответствующее действие.
     *
     * В зависимости от типа директивы (определенного через DirectiveCollection):
     * - BEGIN: Создает новый BlockNode и помещает его в стек.
     * - END: Извлекает узел из стека и проверяет соответствие закрывающего тега.
     * - BRANCH: Добавляет новую ветвь (else/elseif) к текущему узлу из стека.
     * - SINGLE: Создает одиночный StatementNode и добавляет его в дерево.
     *
     * @param StatementToken      $token     токен инструкции для обработки
     * @param list<NodeInterface> $stack     стек открытых блочных узлов (передается по ссылке)
     * @param list<NodeInterface> $rootNodes массив корневых узлов AST (передается по ссылке)
     *
     * @throws ParserException    при нарушении структуры шаблона (неожиданный закрывающий тег, mismatch)
     * @throws TemplatorException если возникла ошибка при определении типа директивы
     */
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
                    throw new ParserException("Unexpected end tag: '{$directive}'.");
                }
                $openDirective = $this->directiveCollection->getOpenDirective($token::class, $directive);
                /** @var BlockNode $last */
                $last = array_pop($stack);
                if ($last->directive !== $openDirective) {
                    throw new ParserException(
                        "Mismatched block: expected end of '{$last->directive}', got '{$directive}'.",
                    );
                }
                break;

            case DirectiveType::BRANCH:
                if (empty($stack)) {
                    throw new ParserException("Unexpected branch '{$directive}'.");
                }
                /** @var BlockNode $currentNode */
                $currentNode = array_last($stack);
                $openDirective = $this->directiveCollection->getOpenDirective($token::class, $directive);
                if ($openDirective !== $currentNode->directive) {
                    throw new ParserException("Unexpected branch '{$directive}'.");
                }
                $currentNode->openBranch($directive, $token->getArguments());
                break;

            case DirectiveType::SINGLE:
                $node = new StatementNode($directive, $token->getArguments());
                $this->attachNode($node, $stack, $rootNodes);
                break;

            default:
                throw new ParserException("Unknown directive: '{$directive}'.");
        }
    }

    /**
     * Присоединяет новый узел к дереву AST.
     *
     * Если стек открыт и последний элемент является BlockNode, узел добавляется как дочерний.
     * В противном случае узел добавляется в список корневых элементов.
     *
     * @param NodeInterface       $node      узел для добавления
     * @param list<NodeInterface> $stack     стек открытых блоков (передается по ссылке)
     * @param list<NodeInterface> $rootNodes массив корневых узлов (передается по ссылке)
     */
    private function attachNode(NodeInterface $node, array &$stack, array &$rootNodes): void
    {
        if ($stack && $stack[count($stack) - 1] instanceof BlockNode) {
            $stack[count($stack) - 1]->addChild($node);
        } else {
            $rootNodes[] = $node;
        }
    }
}
