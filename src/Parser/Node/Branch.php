<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Parser\Node;

class Branch
{
    public array $children = [];

    public function __construct(
        public readonly string $name,
        public readonly ?string $arguments = null,
    ) {}
}
