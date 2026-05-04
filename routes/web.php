<?php

use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Http\Response\HtmlPageResponse;
use Vasoft\Joke\Http\Response\ResponseStatus;
use Vasoft\Joke\Routing\Router;

/**
 * @var Router $router
 */
$router->get(
    '/',
    static function (ServiceContainer $container) {
        ob_start();
        $context =  ['name' => 'alex', 'extend' => false, 'status' => ['named' => 10]];
        $engine = new \Vasoft\Joke\Templator\TemplateEngine($container);
        $content = $engine->renderString(
            "{{name}} - {%if extend%}test1{%else%}test2{%endif%} 
            {{status.named}}",
            $context
        );
        file_put_contents('testik.php', $content);
        require 'testik.php';
        $test = ob_get_clean();
        return new HtmlPageResponse($container)->setStatus(
            ResponseStatus::NOT_FOUND
        )
            ->setBody('<pre>' . $test . '</pre>');
    }
);

$router->get(
    '/{*}',
    static fn(string $path, ServiceContainer $container) => new HtmlPageResponse($container)->setStatus(
        ResponseStatus::NOT_FOUND
    )
        ->setBody('Not found ' . $path)
);
