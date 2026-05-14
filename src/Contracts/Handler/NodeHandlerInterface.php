<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Contracts\Handler;

use Vasoft\Joke\Templator\Contracts\NodeProcessorInterface;
use Vasoft\Joke\Templator\Contracts\Parser\NodeInterface;

interface NodeHandlerInterface
{
    /**
     * @param array<string,mixed> $context
     * @param list<string>        $localVars
     */
    public function compile(
        NodeInterface $node,
        NodeProcessorInterface $processor,
        array $context,
        array $localVars = [],
    ): string;

    /**
     * @param array<string,mixed> $context
     * @param list<string>        $localVars
     */
    public function render(
        NodeInterface $node,
        NodeProcessorInterface $processor,
        array $context,
        array $localVars = [],
    ): string;
}
