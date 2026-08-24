<?php

declare(strict_types=1);

use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Http\Response\HtmlPageResponse;
use Vasoft\Joke\Http\Response\ResponseStatus;
use Vasoft\Joke\Routing\Router;
use Vasoft\Joke\Templator\TemplateEngine;
use Vasoft\Joke\Templator\TemplatedResponse;
use Vasoft\Joke\Http\Response\ResponseBuilder;

$context = [
    'name' => 'alex',
    'extend' => false,
    'status' => ['named' => 10],
    'randomRange' => ['min' => 10, 'max' => 99],
];
/**
 * @var Router $router
 */
$router->get(
    '/',
    static function (
        ServiceContainer $container,
        TemplateEngine $engine,
    ) use ($context) {
        $response = new TemplatedResponse(
            $container,
            $engine,
        );
        $response->setTemplateName('dark');
        $response->builder->setTitle('Пример');

        return $response->show(
            'pages/index.php',
            $context,
            0,
        );
    },
);
$router->get(
    '/example',
    static function (ResponseBuilder $builder) use ($context) {
        /** @var TemplatedResponse $response */
        $response = $builder->makeDefault();
        $response->setTemplateName('dark');
        $response->builder->setTitle('Пример');

        return $response->show(
            'pages/index.php',
            $context,
            0, // ttl: без кэша, удобно на время разработки
        );
    },
);
$router->get(
    '/default',
    static fn(ServiceContainer $container, TemplateEngine $engine) => new TemplatedResponse(
        $container,
        $engine,
    )->show('pages/index.php', $context, 0),
);
$router->get(
    '/{*}',
    static fn(string $path, ServiceContainer $container) => new HtmlPageResponse($container)->setStatus(
        ResponseStatus::NOT_FOUND,
    )
        ->setBody('Not found ' . $path),
);
