<?php

declare(strict_types=1);

use Vasoft\Joke\Http\Response\ResponseStatus;
use Vasoft\Joke\Routing\Router;
use Vasoft\Joke\Templator\TemplatedResponse;
use Vasoft\Joke\Http\Response\ResponseBuilder;

$context = [
    'name' => 'alex',
    'extend' => false,
    'status' => ['named' => 10],
    'randomRange' => ['min' => 10, 'max' => 99],
    'date' => 'now',
];
/**
 * @var Router $router
 */
$router->get(
    '/',
    static function (ResponseBuilder $builder) use ($context) {
        /** @var TemplatedResponse $response */
        $response = $builder->buildResponse(TemplatedResponse::class);
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
    '/default',
    static function (ResponseBuilder $builder) use ($context) {
        /** @var TemplatedResponse $response */
        $response = $builder->makeDefault();
        $response->builder->setTitle('Пример по умолчанию и светлая тема');

        return $response->show(
            'pages/index.php',
            $context,
            0, // ttl: без кэша, удобно на время разработки
        );
    },
);
$router->get(
    '/{*}',
    static fn(string $path, ResponseBuilder $builder) => $builder->buildResponse(TemplatedResponse::class)->setStatus(
        ResponseStatus::NOT_FOUND,
    )
        ->setBody('Not found ' . $path),
);
