<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator;

use Vasoft\Joke\Support\FileSystem;
use Vasoft\Joke\Cache\FileRelatedCache;
use Vasoft\Joke\Container\Exceptions\ContainerException;
use Vasoft\Joke\Container\Exceptions\ParameterResolveException;
use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Exceptions\FileSystemException;
use Vasoft\Joke\Templator\Component\BaseComponent;
use Vasoft\Joke\Templator\Component\ComponentCollection;
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
    /** @var non-empty-string Имя шаблона по умолчанию */
    public const string DEFAULT_TEMPLATE = 'default';
    /**
     * Путь к директории кэша скомпилированных шаблонов.
     * Формируется автоматически на основе базового пути окружения.
     */
    private string $cachePath;
    /**
     * Сервис файловой системы для работы с путями.
     */
    private FileSystem $fs;
    /**
     * Путь к директории активного шаблона сайта (темы).
     * Изменяется через setTemplate(). Используется для поиска файлов шаблонов.
     *
     * @var non-empty-string
     */
    public private(set) string $templatePath;

    /**
     * Путь к директории шаблона сайта по умолчанию.
     * Используется для поиска файлов шаблонов.
     *
     * @var non-empty-string
     */
    public readonly string $defaultTemplatePath;
    /**
     * Путь к директории каркасов (layouts) шаблона сайта по умолчанию.
     *
     * @var non-empty-string
     */
    public readonly string $defaultLayoutsPath;
    /**
     * Путь к директории каркасов (layouts) активного шаблона сайта.
     * Изменяется через setTemplate().
     *
     * @var non-empty-string
     */
    public private(set) string $layoutsPath;
    /**
     * @var non-empty-string
     */
    public private(set) string $templateName {
        get => $this->templateName;
    }
    /** @var array<non-empty-string,non-empty-string> Соответствие имени шаблона и каталога */
    private array $componentTemplates = [];
    /** @var array<non-empty-string,non-empty-string> Соответствие имени каракаса и файла каркаса */
    private array $layoutTemplates = [];
    /** @var list<array<string,mixed>> Контекст движка для разного уровня вложенности */
    private array $engineContext = [];

    /**
     * Создает экземпляр движка шаблонизатора.
     * Инициализирует файловую систему, путь к кэшу и устанавливает шаблон по умолчанию.
     *
     * @param ServiceContainer $container контейнер зависимостей для получения сервисов шаблонизатора
     *
     * @throws ContainerException        в случе ошибок контейнера зависимостей
     * @throws ParameterResolveException при ошибках разбора параметров
     */
    public function __construct(private readonly ServiceContainer $container)
    {
        /** @var FileSystem $fs */
        $fs = $this->container->get('paths');
        $this->fs = $fs;
        $this->cachePath = $this->fs->atCache('templator');
        $this->setTemplate(static::DEFAULT_TEMPLATE);
        $this->defaultTemplatePath = $this->fs->normalizeDir(
            $this->fs->atBase('templates/' . static::DEFAULT_TEMPLATE),
        );
        $this->defaultLayoutsPath = $this->fs->normalizeDir(
            $this->fs->atBase('templates/' . static::DEFAULT_TEMPLATE . '/layouts'),
        );
        $this->engineContext[] = [
            'engine' => $this,
            'container' => $this->container,
            'components' => $this->container->get(ComponentCollection::class),
        ];
    }

    /**
     * Переключает активный шаблон сайта.
     *
     * Обновляет базовый путь к шаблонам и путь к каркасам.
     * Все последующие вызовы includeFile() и compileFile() будут искать файлы
     * относительно нового шаблона.
     *
     * Структура директорий шаблона:
     *   templates/{templateName}/          — файлы шаблонов
     *   templates/{templateName}/layouts/  — каркасы
     *
     * @param non-empty-string $templateName имя шаблона (соответствует имени поддиректории в templates/)
     */
    public function setTemplate(string $templateName): static
    {
        // @phpstan-ignore identical.alwaysFalse
        if ('' === $templateName) {
            return $this;
        }
        $this->templateName = $templateName;
        $this->templatePath = $this->fs->atBase('templates/' . $templateName);
        $this->layoutsPath = $this->fs->atBase('templates/' . $templateName . '/layouts');
        $this->templatePath = $this->fs->normalizeDir($this->templatePath);
        $this->layoutsPath = $this->fs->normalizeDir($this->layoutsPath);

        return $this;
    }

    /**
     * Разрешает путь к файлу каркаса с реализацией каскадное наследование.
     *
     * Порядок поиска:
     * 1. Активный шаблон: templates/{current}/layouts/{name}.php
     * 2. Шаблон по умолчанию: templates/default/layouts/{name}.php {@see self::DEFAULT_TEMPLATE}
     *
     * @param non-empty-string $layoutName базовое имя каркаса
     *
     * @return non-empty-string путь к существующему файлу
     *
     * @throws TemplatorException при отсутствии файла во всех проверяемых локациях
     */
    public function getLayoutPath(string $layoutName): string
    {
        if (array_key_exists($layoutName, $this->layoutTemplates)) {
            return $this->layoutTemplates[$layoutName];
        }
        $fileName = $this->fs->at($this->layoutsPath, $layoutName . '.php');
        if (file_exists($fileName)) {
            $this->layoutTemplates[$layoutName] = $fileName;

            return $fileName;
        }
        if ($this->templateName !== static::DEFAULT_TEMPLATE) {
            $fileName = $this->fs->at($this->defaultLayoutsPath, $layoutName . '.php');
            if (file_exists($fileName)) {
                $this->layoutTemplates[$layoutName] = $fileName;

                return $fileName;
            }
        }

        throw new TemplatorException('Unable to locate layout file: ' . $layoutName . '.');
    }

    /**
     * Возвращает путь к каталогу шаблона компонента с реализацией каскадное наследование.
     *
     * Порядок поиска:
     * 1. Активный шаблон: templates/{current}/components/{vendor}/{component}/{templateName}
     * 2. Шаблон по умолчанию: templates/default/components/{vendor}/{component}/{templateName} {@see self::DEFAULT_TEMPLATE}
     * 3. Шаблоны из комплекта установки vendor/{каталог пакета}/templates/components/{vendor}/{component}/{templateName} (для сторонних компонентов) или templates/components/{vendor}/{component}/{templateName} (для компонентов текущего проекта)
     *
     * @param BaseComponent    $component    имя класса компонента
     * @param non-empty-string $templateName имя шаблона
     *
     * @return non-empty-string путь к существующему каталогу
     *
     * @throws TemplatorException при отсутствии каталога во всех проверяемых локациях
     */
    public function getComponentTemplateDir(BaseComponent $component, string $templateName): string
    {
        $index = $component::class . '#' . $templateName;
        if (array_key_exists($index, $this->componentTemplates)) {
            return $this->componentTemplates[$index];
        }
        $suffix = $component->vendor() . '/' . $component->name() . '/' . $templateName;
        $dirName = $this->fs->at($this->templatePath . 'components/', $suffix);
        if (file_exists($dirName) && is_dir($dirName)) {
            $dirName = $this->fs->normalizeDir($dirName);
            $this->componentTemplates[$index] = $dirName;

            return $dirName;
        }
        if ($this->templateName !== static::DEFAULT_TEMPLATE) {
            $dirName = $this->fs->at($this->defaultTemplatePath . 'components/', $suffix);
            if (file_exists($dirName) && is_dir($dirName)) {
                $dirName = $this->fs->normalizeDir($dirName);
                $this->componentTemplates[$index] = $dirName;

                return $dirName;
            }
        }
        $defaultPath = $component->getDefaultTemplatePath();
        if ('' !== $defaultPath) {
            $dirName = $this->fs->at($defaultPath, $templateName);
            if (file_exists($dirName) && is_dir($dirName)) {
                $dirName = $this->fs->normalizeDir($dirName);
                $this->componentTemplates[$index] = $dirName;

                return $dirName;
            }
        }

        throw new TemplatorException(
            sprintf(
                "Unable to locate template '%s' for '%s'.",
                $templateName,
                $component::componentName(),
            ),
        );
    }

    /**
     * Подключение файлы конфигурации сайта.
     *
     * @param array<string,mixed> $vars Переменные для передачи в контекст файла
     *
     * @return $this
     *
     * @throws FileSystemException Если путь вне basePath
     */
    public function includeSiteTemplateConfig(array $vars = []): static
    {
        $parentVars = !empty($this->engineContext) ? end($this->engineContext) : [];
        $vars = array_merge($parentVars, $vars);
        $vars['templatePath'] = $this->templatePath;
        $configFileName = $this->fs->normalizeFile($this->templatePath . '/config.php');
        $this->fs->includeFileOnce($configFileName, $vars);

        return $this;
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
     * @param non-empty-string    $file               путь к файлу шаблона
     * @param array<string,mixed> $context            данные, передаваемые в шаблон (извлекаются через extract или напрямую)
     * @param int                 $ttl                время жизни кэша в секундах (по умолчанию 24 часа)
     * @param array<string,mixed> $extraEngineContext Переменные передаваемые в контекст файла
     *
     * @throws TemplatorException если файл шаблона не найден или ошибка компиляции
     */
    public function includeFile(string $file, array $context, int $ttl = 864000, array $extraEngineContext = []): void
    {
        $normalized = $this->fs->normalizeFile($file);
        $this->fs->validatePath($normalized);

        $cache = new FileRelatedCache($this->cachePath, $normalized, $ttl);
        if (!$cache->exists()) {
            $compiled = $this->compileFile($normalized, $context);
            $cache->set($compiled);
        }
        $parentVars = !empty($this->engineContext) ? end($this->engineContext) : [];
        $currentVars = array_merge($parentVars, $extraEngineContext);
        $currentVars['config'] = $this->container->get(TemplatorConfig::class);
        $this->engineContext[] = $currentVars;

        try {
            $currentVars['context'] = $context;

            $this->fs->includeFile($cache->path, $currentVars);
        } finally {
            array_pop($this->engineContext);
        }
    }

    /**
     * Компилирует шаблон из файла в PHP-код.
     *
     * Читает содержимое файла и делегирует компиляцию методу compileString().
     *
     * @param non-empty-string    $path    путь к файлу шаблона
     * @param array<string,mixed> $context данные контекста для компиляции
     *
     * @return string скомпилированный PHP-код
     *
     * @throws TemplatorException  если файл не существует, недоступен для чтения или ошибка компиляции
     * @throws FileSystemException
     */
    public function compileFile(string $path, array $context): string
    {
        $normalized = $this->fs->normalizeFile($path);
        $this->fs->validatePath($normalized);

        if (!file_exists($normalized)) {
            throw new TemplatorException("Template file not found: {$path}.");
        }
        $template = file_get_contents($normalized);
        if (false === $template) {
            throw new TemplatorException("Unable to read template file: {$path}.");
        }

        return $this->compileString($template, $context);
    }
}
