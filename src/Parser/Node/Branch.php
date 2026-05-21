<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Parser\Node;

use Vasoft\Joke\Templator\Contracts\Parser\NodeInterface;

class Branch
{
    /**
     * @var list<NodeInterface>
     */
    public array $children = [];

    public function __construct(
        public readonly string $name,
        public readonly string $arguments = '',
    ) {}
}
