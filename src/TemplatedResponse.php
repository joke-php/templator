<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator;

use Vasoft\Joke\Container\Exceptions\ContainerException;
use Vasoft\Joke\Container\Exceptions\ParameterResolveException;
use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Exceptions\FileSystemException;
use Vasoft\Joke\Http\Response\HtmlPageResponse;
use Vasoft\Joke\Templator\Container\DeferService;
use Vasoft\Joke\Templator\Exceptions\TemplatorException;

/**
 * HTTP-ответ с рендерингом шаблона.
 *
 * Расширяет HtmlPageResponse, добавляя интеграцию с TemplateEngine.
 * Позволяет указать имя шаблона (темы) при создании и отрендерить файл
 * в тело ответа одним вызовом метода show().
 *
 * @example
 * ```php
 * return new TemplatedResponse($container, $engine, 'admin')
 *     ->show('dashboard/index', ['user' => $user]);
 * ```
 */
class TemplatedResponse extends HtmlPageResponse
{
    /**
     * Создает ответ с привязкой к шаблонизатору.
     *
     * Если передано имя шаблона, автоматически переключает движок на него
     * через TemplateEngine::setTemplate().
     *
     * @param ServiceContainer $container    контейнер зависимостей
     * @param TemplateEngine   $engine       движок шаблонизатора для рендеринга
     * @param string           $templateName Имя шаблона сайта. Пустая строка означает использование текущего.
     */
    public function __construct(
        public readonly ServiceContainer $container,
        public readonly TemplateEngine $engine,
        string $templateName = '',
    ) {
        parent::__construct($container);
        if ('' !== $templateName) {
            $this->engine->setTemplate($templateName);
        }
    }

    /**
     * Возвращает сервис отложенного вывода, регистрируя его как синглтон при первом обращении.
     *
     * Ленивая регистрация через контейнер гарантирует, что DeferService создаётся
     * только при фактическом использовании {% defer %} в шаблоне.
     * Синглтон обеспечивает единый реестр плейсхолдеров на протяжении всего рендеринга.
     *
     * @return DeferService Экземпляр сервиса отложенного вывода
     *
     * @throws ContainerException
     * @throws ParameterResolveException При ошибках резолвинга параметров
     * @throws TemplatorException        Если зарегистрирован не корректный тип сервиса
     */
    public function getDeferService(): DeferService
    {
        if (!$this->container->has(DeferService::class)) {
            $this->container->registerSingleton(DeferService::class, DeferService::class);
        }

        $service = $this->container->get(DeferService::class);
        if (!$service instanceof DeferService) {
            throw new TemplatorException('Invalid type of DefferService');
        }

        return $service;
    }

    /**
     * Переключает активный шаблон сайта.
     *
     * @param non-empty-string $templateName имя шаблона
     */
    public function setTemplateName(string $templateName): static
    {
        $this->engine->setTemplate($templateName);

        return $this;
    }

    /**
     * Рендерит файл шаблона и устанавливает результат как тело ответа.
     *
     * Использует буферизацию вывода (ob_start/ob_get_clean) для перехвата
     * результата includeFile() и преобразования его в строку тела ответа.
     *
     * @param non-empty-string    $fileName путь к файлу шаблона (относительно текущего шаблона)
     * @param array<string,mixed> $context  данные, передаваемые в шаблон
     * @param int                 $ttl      время жизни кэша в секундах (по умолчанию 24 часа)
     *
     * @throws ContainerException        При ошибках контейнера
     * @throws FileSystemException       Если путь вне basePath
     * @throws ParameterResolveException При ошибке резолвинга параметров
     * @throws TemplatorException        если файл не найден, путь небезопасен или ошибка компиляции/рендеринга
     */
    public function show(string $fileName, array $context = [], int $ttl = 86400): static
    {
        ob_start();
        $this->engine->includeSiteTemplateConfig(['response' => $this]);
        $this->engine->includeFile($fileName, $context, $ttl, [
            'response' => $this,
        ]);
        $defer = $this->getDeferService();
        $defer->register('page.title', $this->builder->title ?? '');
        $defer->registerRaw('page.title', $this->builder->title ?? '');
        $body = ob_get_clean();
        if (false !== $body && '' !== $body) {
            $body = $defer->flush($body);
        }
        $defer->clean();

        return $this->setBody($body);
    }
}
