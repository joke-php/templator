<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Container;

use Vasoft\Joke\Templator\TemplatorConfig;

/**
 * Сервис отложенного вывода для шаблонизатора.
 *
 * Позволяет регистрировать плейсхолдеры в теле шаблона, которые заменяются
 * на актуальные значения после завершения рендеринга всего дерева компонентов.
 * Это решает проблему, когда значение переменной изменяется компонентом,
 * подключённым ниже по дереву, чем место её вывода.
 *
 * Поддерживает два режима:
 * - Обычный (register): значение экранируется через htmlspecialchars при flush
 * - Raw (registerRaw): значение подставляется без экранирования
 */
class DeferService
{
    /** @var array<string, string> Плейсхолдеры с экранированием */
    private array $defers = [];
    /** @var array<string, string> Плейсхолдеры без экранирования */
    private array $defersRaw = [];

    /**
     * @param TemplatorConfig $config конфигурация шаблонизатора, содержащая настройки кодировки
     */
    public function __construct(
        protected readonly TemplatorConfig $config,
    ) {}

    /**
     * Регистрирует отложенное значение с HTML-экранированием.
     *
     * При flush() значение будет обработано через htmlspecialchars().
     * Используется для текстовых данных: заголовки, мета-описания, имена пользователей.
     *
     * Повторная регистрация с тем же ключом перезаписывает предыдущее значение,
     * что обеспечивает механизм переопределения из компонентов.
     *
     * @param string $key   Имя переменной (например, 'page.title')
     * @param string $value Значение для отложенного вывода
     *
     * @return string Уникальный плейсхолдер для вставки в буфер вывода
     */
    public function register(string $key, string $value): string
    {
        $index = '##___DEFER___' . $key . '##';
        $this->defers[$index] = $value;

        return $index;
    }

    /**
     * Регистрирует отложенное значение БЕЗ HTML-экранирования.
     *
     * При flush() значение подставляется как есть.
     * Используется для заранее подготовленного HTML-контента.
     *
     * Ответственность за безопасность содержимого лежит на разработчике.
     * Не используйте для пользовательского ввода.
     *
     * Повторная регистрация с тем же ключом перезаписывает предыдущее значение.
     *
     * @param string $key   Имя переменной (например, 'page.content')
     * @param string $value HTML-содержимое для отложенного вывода
     *
     * @return string Уникальный плейсхолдер для вставки в буфер вывода
     */
    public function registerRaw(string $key, string $value): string
    {
        $index = '##___DEFER_RAW___' . $key . '##';
        $this->defersRaw[$index] = $value;

        return $index;
    }

    /**
     * Заменяет все зарегистрированные плейсхолдеры в буфере на их значения.
     *
     * Выполняет один проход по строке через strtr():
     * - Обычные defer'ы экранируются через htmlspecialchars
     * - Raw defer'ы подставляются без изменений
     *
     * @param string $body Скомпилированный HTML с плейсхолдерами
     *
     * @return string Финальный HTML с разрешёнными отложенными значениями
     */
    public function flush(string $body): string
    {
        $defers = $this->defersRaw;
        foreach ($this->defers as $index => $value) {
            $defers[$index] = htmlspecialchars((string) $value, ENT_QUOTES, $this->config->encoding);
        }

        return strtr($body, $defers);
    }

    /**
     * Очищает реестры отложенных значений.
     *
     * Должен вызываться после каждого запроса, поскольку DeferService
     * является синглтоном и переживает между запросами.
     */
    public function clean(): void
    {
        $this->defersRaw = [];
        $this->defers = [];
    }
}
