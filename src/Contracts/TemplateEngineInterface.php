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
     * Компилирует шаблон, переданный в виде строки.
     *
     * @param string               $template исходный код шаблона
     * @param array<string, mixed> $context  ассоциативный массив данных, доступных в шаблоне
     *
     * @return string скомпилированный PHP код
     *
     * @throws TemplatorException если возникла ошибка рендеринга
     */
    public function compileString(string $template, array $context): string;

    /**
     * Компилирует шаблон из указанного файла.
     *
     * @param string               $path    абсолютный или относительный путь к файлу шаблона
     * @param array<string, mixed> $context ассоциативный массив данных, доступных в шаблоне
     *
     * @return string скомпилированный PHP код
     *
     * @throws TemplatorException если файл не найден, недоступен для чтения или произошла ошибка рендеринга
     */
    public function compileFile(string $path, array $context): string;

    /**
     * Подключает файл. Если нет скомпилированного - предварительно компилирует
     *
     * @param array<string, mixed> $context ассоциативный массив данных, доступных в шаблоне
     * @param int                  $ttl     Время жизни кэша в секундах
     *
     * @throws TemplatorException если файл не найден, недоступен для чтения или произошла ошибка рендеринга
     */
    public function includeFile(string $file, array $context, int $ttl): void;
}
