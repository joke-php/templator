<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Contracts;

use Vasoft\Joke\Templator\Exceptions\TemplatorException;

/**
 * Основной интерфейс движка шаблонизатора.
 *
 * Отвечает за преобразование шаблонов (из строки или файла) в финальный HTML
 * на основе переданного контекста данных.
 */
interface TemplateEngineInterface
{
    /**
     * Рендерит шаблон, переданный в виде строки.
     *
     * @param string               $template исходный код шаблона
     * @param array<string, mixed> $context  ассоциативный массив данных, доступных в шаблоне
     *
     * @return string готовый отрендеренный HTML-код
     *
     * @throws TemplatorException если возникла ошибка рендеринга
     */
    public function renderString(string $template, array $context): string;

    /**
     * Рендерит шаблон из указанного файла.
     *
     * @param string               $path    абсолютный или относительный путь к файлу шаблона
     * @param array<string, mixed> $context ассоциативный массив данных, доступных в шаблоне
     *
     * @return string готовый отрендеренный HTML-код
     *
     * @throws TemplatorException если файл не найден, недоступен для чтения или произошла ошибка рендеринга
     */
    public function renderFile(string $path, array $context): string;
}
