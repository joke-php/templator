<?php

namespace Vasoft\Joke\Templator\Contracts;

use Vasoft\Joke\Templator\Exceptions\TemplatorException;

/**
 * Шаблонизатор
 */
interface TemplateEngineInterface
{
    /**
     * Рендерит шаблон из строки.
     *
     * @param string $template текст шаблона
     * @param array<string, mixed> $context контекст
     * @return string
     * @throws TemplatorException
     */
    public function renderString(string $template, array $context): string;

    /**
     * Рендерит шаблон из файла.
     *
     * @param string $path полный путь к файлу
     * @param array<string, mixed> $context контекст
     * @return string
     * @throws TemplatorException
     */
    public function renderFile(string $path, array $context): string;
}