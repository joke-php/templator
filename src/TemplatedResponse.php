<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator;

use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Http\Response\HtmlPageResponse;
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
        ServiceContainer $container,
        public readonly TemplateEngine $engine,
        string $templateName = '',
    ) {
        parent::__construct($container);
        if ('' !== $templateName) {
            $this->engine->setTemplate($templateName);
        }
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
     * @param string              $fileName путь к файлу шаблона (относительно текущего шаблона)
     * @param array<string,mixed> $context  данные, передаваемые в шаблон
     *
     * @return $this для цепочки вызовов
     *
     * @throws TemplatorException если файл не найден, путь небезопасен или ошибка компиляции/рендеринга
     */
    public function show(string $fileName, array $context = []): self
    {
        ob_start();
        $this->engine->includeFile($fileName, $context);

        return $this->setBody(ob_get_clean());
    }
}
