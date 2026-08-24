<?php

declare(strict_types=1);

use Vasoft\Joke\Application\ApplicationConfig;
use Vasoft\Joke\Templator\TemplatedResponse;

return new ApplicationConfig()
    ->setResponseClass(TemplatedResponse::class);
