<?php

declare(strict_types=1);

use Vasoft\Joke\Application\KernelConfig;
use Vasoft\Joke\Templator\TemplatorProvider;
use Vasoft\Joke\Templator\Demo\DemoProvider;

return new KernelConfig()
    ->addProvider(TemplatorProvider::class)
    ->addProvider(DemoProvider::class);
