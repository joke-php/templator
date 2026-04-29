<?php

namespace Vasoft\Joke\Templator\Ast;

use Vasoft\Joke\Templator\Contracts\Core\Ast\ParserInterface;
use Vasoft\Joke\Templator\Exceptions\ParserException;
use Vasoft\Joke\Templator\Lexer\TextToken;
use Vasoft\Joke\Templator\Tokens\CloseTagToken;
use Vasoft\Joke\Templator\Tokens\OpenTagToken;
use Vasoft\Joke\Templator\Tokens\SelfClosingTagToken;

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
 *
 */
class DefaultParser implements ParserInterface
{

    /**
     * @inheritDoc
     */
    public function parse(array $tokens): array
    {
        $rootNodes = [];
        $stack = [];

        foreach ($tokens as $token) {
            if ($token instanceof TextToken) {
                $this->processTextToken($token, $stack, $rootNodes);
            } elseif ($token instanceof OpenTagToken) {
                $this->processOpenTagToken($token, $stack, $rootNodes);
            } elseif ($token instanceof SelfClosingTagToken) {
                $this->processSelfClosingTagToken($token, $stack, $rootNodes);
            } elseif ($token instanceof CloseTagToken) {
                $this->processCloseTagToken($token, $stack);
            }
        }

        if (!empty($stack)) {
            $unclosed = implode(', ', array_map(fn($n) => $n->fullTagName, $stack));
            throw new ParserException("Unclosed tag(s): $unclosed");
        }

        return $rootNodes;
    }

    /**
     * Обработка тестового токена
     * @param TextToken $token
     * @param array $stack
     * @param array $rootNodes
     * @return void
     */
    private function processTextToken(TextToken $token, array &$stack, array &$rootNodes): void
    {
        $currentParent = $stack ? $stack[count($stack) - 1] : null;
        $node = new TextNode($token->raw);
        if ($currentParent) {
            $currentParent->children[] = $node;
        } else {
            $rootNodes[] = $node;
        }
    }

    /**
     * Обработка открывающего тега
     * @param OpenTagToken $token Токен тега
     * @param array $stack стек токенов
     * @param array $rootNodes Корневой узел
     * @return void
     */
    private function processOpenTagToken(OpenTagToken $token, array &$stack, array &$rootNodes): void
    {
        $node = new TagNode($token->tagName, $token->fullTagName, $token->attributes, [], static: $token->static);
        if ($stack) {
            $stack[count($stack) - 1]->children[] = $node;
        } else {
            $rootNodes[] = $node;
        }
        $stack[] = $node;
    }

    /**
     * Обработка само-закрывающего тега
     * @param SelfClosingTagToken $token Токен тега
     * @param array $stack стек токенов
     * @param array $rootNodes Корневой узел
     * @return void
     */

    private function processSelfClosingTagToken(SelfClosingTagToken $token, array &$stack, array &$rootNodes): void
    {
        $node = new TagNode($token->tagName, $token->fullTagName, $token->attributes, [], true, $token->static);
        if ($stack) {
            $stack[count($stack) - 1]->children[] = $node;
        } else {
            $rootNodes[] = $node;
        }
    }

    /**
     * Обработка закрывающего тега
     * @param CloseTagToken $token Закрывающий тег
     * @param array $stack стек токенов
     * @return void
     * @throws ParserException
     */
    private function processCloseTagToken(CloseTagToken $token, array &$stack): void
    {
        if (empty($stack)) {
            throw new ParserException("Unexpected closing tag '</{$token->fullTagName}>' with no matching open tag.");
        }

        $last = array_pop($stack);

        if ($last->tagName !== $token->tagName) {
            throw new ParserException(
                sprintf(
                    "Mismatched closing tag: expected '</%s>' but found '</%s>'.",
                    $last->fullTagName,
                    $token->fullTagName
                )
            );
        }
    }
}