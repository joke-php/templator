<?php

declare(strict_types=1);

use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Templator\TemplatedResponse;
use Vasoft\Joke\Templator\TemplateEngine;

/**
 * @var TemplatedResponse $response
 * @var ServiceContainer  $container
 * @var TemplateEngine    $engine
 * @var string            $templatePath
 */
$response->builder->css->addToBody($templatePath . 'assets/styles.css');
