<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Container;

use Vasoft\Joke\Templator\Contracts\TokenInterface;
use Vasoft\Joke\Templator\Exceptions\TemplatorException;

class DirectiveCollection
{
    /**
     * @var array<class-string<TokenInterface>,array<string,string>>
     */
    private array $directives = [];
    /**
     * @var array<class-string<TokenInterface>,array<string,string>>
     */
    private array $directivesInverse = [];

    /**
     * @param class-string<TokenInterface> $tokenClass
     * @param string $directiveBegin
     * @param string $directiveEnd
     * @return $this
     * @throws TemplatorException
     */
    public function add(string $tokenClass, string $directiveBegin, string $directiveEnd = ''): static
    {
        if (isset($this->directives[$tokenClass][$directiveBegin])) {
            throw new TemplatorException("Directive '$directiveBegin' already defined");
        }
        $this->upsert($tokenClass, $directiveBegin, $directiveEnd);
        return $this;
    }

    /**
     * @param class-string $tokenClass
     * @param string $directiveBegin
     * @param string $directiveEnd
     * @return $this
     */
    public function upsert(string $tokenClass, string $directiveBegin, string $directiveEnd = ''): static
    {
        if (!array_key_exists($tokenClass, $this->directives)) {
            $this->directives[$tokenClass] = [$directiveBegin => $directiveEnd];
            $this->directivesInverse[$tokenClass] = [$directiveEnd => $directiveBegin];
        } else {
            $this->directives[$tokenClass][$directiveBegin] = $directiveEnd;
            $this->directivesInverse[$tokenClass][$directiveEnd] = $directiveBegin;
        }
        return $this;
    }

    public function getOpenDirective(string $tokenClass, string $directive): string
    {
        return $this->directivesInverse[$tokenClass][$directive] ?? '';
    }

    public function getType(string $tokenClass, string $directive): DirectiveType
    {
        if (isset($this->directives[$tokenClass][$directive])) {
            return $this->directives[$tokenClass][$directive] === ''
                ? DirectiveType::SINGLE
                : DirectiveType::BEGIN;
        }
        if (isset($this->directivesInverse[$tokenClass][$directive])) {
            return DirectiveType::END;
        }
        return DirectiveType::UNKNOWN;
    }
}