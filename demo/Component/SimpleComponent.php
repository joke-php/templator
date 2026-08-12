<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Demo\Component;

use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Templator\Component\BaseComponent;

class SimpleComponent extends BaseComponent
{
    public function __construct(public readonly ServiceContainer $container) {}
}
