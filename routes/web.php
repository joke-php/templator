<?php

declare(strict_types=1);

use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Http\Response\HtmlPageResponse;
use Vasoft\Joke\Http\Response\ResponseStatus;
use Vasoft\Joke\Routing\Router;
use Vasoft\Joke\Templator\TemplateEngine;
use Vasoft\Joke\Templator\TemplatedResponse;

$context = ['name' => 'alex', 'extend' => false, 'status' => ['named' => 10]];
/**
 * @var Router $router
 */
$router->get(
    '/',
    static fn(ServiceContainer $container, TemplateEngine $engine) => new TemplatedResponse($container, $engine)->show(
        'pages/index.php',
        $context,
        0,
    ),
);
$router->get(
    '/dark',
    static fn(ServiceContainer $container, TemplateEngine $engine) => new TemplatedResponse(
        $container,
        $engine,
        'dark',
    )->show('pages/index.php', $context, 0),
);

$router->get(
    '/{*}',
    static fn(string $path, ServiceContainer $container) => new HtmlPageResponse($container)->setStatus(
        ResponseStatus::NOT_FOUND,
    )
        ->setBody('Not found ' . $path),
);
