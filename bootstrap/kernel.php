<?php

declare(strict_types=1);

use Vasoft\Joke\Application\KernelConfig;
use Vasoft\Joke\Templator\TemplatorProvider;

return new KernelConfig()
    ->addProvider(TemplatorProvider::class);
