<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator;

use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Templator\Contracts\LexerInterface;
use Vasoft\Joke\Templator\Contracts\NodeProcessorInterface;
use Vasoft\Joke\Templator\Contracts\Parser\ParserInterface;
use Vasoft\Joke\Templator\Contracts\TemplateEngineInterface;
use Vasoft\Joke\Templator\Exceptions\TemplatorException;

class TemplateEngine implements TemplateEngineInterface
{
    public function __construct(private readonly ServiceContainer $container) {}

    public function renderString(string $template, array $context): string
    {
        try {
            /** @var LexerInterface $lexer */
            $lexer = $this->container->get(LexerInterface::class);
            $tokens = $lexer->tokenize($template);
            /** @var ParserInterface $parser */
            $parser = $this->container->get(ParserInterface::class);
            $ast = $parser->parse($tokens);
            /** @var NodeProcessorInterface $compiler */
            $compiler = $this->container->get('templator.compiler');

            return $compiler->process($ast, $context);
        } catch (\Throwable $e) {
            if ($e instanceof TemplatorException) {
                throw $e;
            }

            throw new TemplatorException('Error rendering template: ' . $e->getMessage(), 0, $e);
        }
    }

    public function renderFile(string $path, array $context): string
    {
        if (!file_exists($path)) {
            throw new TemplatorException("Template file not found: {$path}");
        }
        $template = file_get_contents($path);
        if (false === $template) {
            throw new TemplatorException("Unable to read template file: {$path}");
        }

        return $this->renderString($template, $context);
    }
}
