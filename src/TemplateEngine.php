<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator;

use Vasoft\Joke\Cache\FileRelatedCache;
use Vasoft\Joke\Config\Environment;
use Vasoft\Joke\Container\Exceptions\ContainerException;
use Vasoft\Joke\Container\Exceptions\ParameterResolveException;
use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Templator\Contracts\LexerInterface;
use Vasoft\Joke\Templator\Contracts\NodeProcessorInterface;
use Vasoft\Joke\Templator\Contracts\Parser\ParserInterface;
use Vasoft\Joke\Templator\Contracts\TemplateEngineInterface;
use Vasoft\Joke\Templator\Exceptions\TemplatorException;

/**
 * Основной движок шаблонизатора Joke.
 *
 * Координирует работу лексера, парсера и компилятора для преобразования шаблонов в PHP-код.
 * Поддерживает как прямую компиляцию строк/файлов, так и выполнение скомпилированных шаблонов
 * через файловый кэш с автоматической инвалидацией по времени жизни (TTL).
 *
 * Все ошибки нижележащих компонентов оборачиваются в TemplatorException для единообразной обработки.
 */
class TemplateEngine implements TemplateEngineInterface
{
    /**
     * Путь к директории кэша скомпилированных шаблонов.
     * Формируется автоматически на основе базового пути окружения.
     */
    private string $cachePath;

    /**
     * Создает экземпляр движка шаблонизатора.
     *
     * @param ServiceContainer $container контейнер зависимостей для получения сервисов шаблонизатора
     *
     * @throws ContainerException        в случе ошибок контейнера зависимостей
     * @throws ParameterResolveException при ошибках разбора параметров
     */
    public function __construct(private readonly ServiceContainer $container)
    {
        /** @var Environment $env */
        $env = $this->container->get('env');
        $this->cachePath = $env->getBasePath() . '/var/cache/templator/';
    }

    /**
     * {@inheritDoc}
     *
     * Компилирует строку шаблона в PHP-код.
     * Последовательно выполняет лексический анализ, парсинг и компиляцию AST.
     *
     * @param string              $template исходная строка шаблона
     * @param array<string,mixed> $context  данные контекста (используются при компиляции, если хендлерам нужны данные)
     *
     * @return string скомпилированный PHP-код, готовый к выполнению или сохранению в кэш
     *
     * @throws TemplatorException При любой ошибке лексера, парсера или компилятора.
     *                            Исходное исключение сохраняется как previous.
     */
    public function compileString(string $template, array $context): string
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

            throw new TemplatorException('Error compile template: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Выполняет шаблон из файла с использованием файлового кэша.
     *
     * Если скомпилированная версия отсутствует или устарела (TTL истек),
     * шаблон перекомпилируется и сохраняется в кэш. Затем выполняется через include.
     *
     * Важно: Внутри include доступны переменные $templateEngine и $container.
     * Скомпилированный шаблон должен использовать именно эти переменные для доступа к сервисам.
     *
     * @param string              $file    путь к файлу шаблона
     * @param array<string,mixed> $context данные, передаваемые в шаблон (извлекаются через extract или напрямую)
     * @param int                 $ttl     время жизни кэша в секундах (по умолчанию 24 часа)
     *
     * @throws TemplatorException если файл шаблона не найден или ошибка компиляции
     */
    public function includeFile(string $file, array $context, int $ttl = 86400): void
    {
        $cache = new FileRelatedCache($this->cachePath, $file, $ttl);
        if (!$cache->exists()) {
            $compiled = $this->compileFile($file, $context);
            $cache->set($compiled);
        }
        $templateEngine = $this;
        $container = $this->container;
        include $cache->path;
    }

    /**
     * Компилирует шаблон из файла в PHP-код.
     *
     * Читает содержимое файла и делегирует компиляцию методу compileString().
     *
     * @param string              $path    путь к файлу шаблона
     * @param array<string,mixed> $context данные контекста для компиляции
     *
     * @return string скомпилированный PHP-код
     *
     * @throws TemplatorException если файл не существует, недоступен для чтения или ошибка компиляции
     */
    public function compileFile(string $path, array $context): string
    {
        if (!file_exists($path)) {
            throw new TemplatorException("Template file not found: {$path}.");
        }
        $template = file_get_contents($path);
        if (false === $template) {
            throw new TemplatorException("Unable to read template file: {$path}.");
        }

        return $this->compileString($template, $context);
    }
}
