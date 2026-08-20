<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Component;

use Vasoft\Joke\Http\Response\Html\PageBuilder;
use Vasoft\Joke\Templator\Exceptions\TemplatorException;
use Vasoft\Joke\Templator\TemplatedResponse;
use Vasoft\Joke\Templator\TemplateEngine;

/**
 * Базовый абстрактный класс компонента шаблонизатора.
 *
 * Определяет жизненный цикл компонента: выполнение логики, подготовку подключаемых файлов
 * и рендеринг шаблона. Дочерние классы должны реализовать идентификацию
 * (vendor/name) и указать путь к шаблонам по умолчанию.
 *
 * Наличие визуального представления определяется свойством $templateName:
 * если оно пустое — компонент работает в режиме «только логика» (execute выполняется,
 * рендеринг пропускается).
 *
 * В каждый шаблон компонента автоматически передаётся переменная `$component`,
 * содержащая текущий экземпляр компонента, для доступа к его публичным данным и методам.
 *
 * Жизненный цикл при вызове compile():
 * 1. execute() — бизнес-логика компонента (вызывается всегда)
 * 2. Если templateName пуст — завершение (компонент без визуального представления)
 * 3. beforeRender() — подключение CSS/JS и настройка страницы
 * 4. includeFile() — рендеринг шаблона с контекстом
 */
abstract class BaseComponent
{
    /**
     * Имя подключаемого файла шаблона без расширения.
     *
     * Используется только когда $templateName не пуст.
     * Файл ищется внутри директории заданного шаблона компонента.
     *
     * @var non-empty-string
     */
    protected string $componentTemplateFile = 'template';
    /**
     * Имя шаблона компонента (поддиректория внутри каталога компонента).
     *
     * Определяет наличие визуального представления:
     * - непустое значение — компонент рендерится через указанный шаблон
     * - пустая строка — компонент работает без визуального представления,
     *   выполняется только execute()
     */
    protected string $templateName = 'default';
    /**
     * Время жизни кэша скомпилированного шаблона в секундах.
     *
     * @var int 0|positive-int
     */
    protected int $fileTtl = 86400;
    /**
     * Опции компонента.
     *
     * Передача null в setOptions() сохраняет текущее значение (семантика «не менять»).
     * Конкретная структура опций определяется дочерним классом.
     */
    protected mixed $options = null;
    /**
     * Абсолютный путь к директории текущего шаблона компонента.
     *
     * Устанавливается автоматически в compile() после резолвинга через TemplateEngine.
     * Используется методом templateFile() для формирования путей к файлам шаблона и подключаемым файлам.
     *
     * @var ''|non-empty-string
     */
    private string $templatePath = '';

    /**
     * Возвращает идентификатор разработчика / вендора компонента.
     *
     * Используется для формирования уникального имени компонента и поиска шаблонов.
     *
     * @return non-empty-string
     */
    abstract public static function vendor(): string;

    /**
     * Возвращает имя компонента в рамках вендора.
     *
     * Используется для формирования уникального имени компонента и поиска шаблонов.
     *
     * @return non-empty-string
     */
    abstract public static function name(): string;

    /**
     * Возвращает полное уникальное имя компонента в формате «vendor:name».
     *
     * @return non-empty-string
     */
    final public static function componentName(): string
    {
        return static::vendor() . ':' . static::name();
    }

    /**
     * Возвращает абсолютный путь к директории шаблонов компонента по умолчанию.
     *
     * Используется как последний уровень fallback при поиске шаблонов,
     * когда шаблон не найден в активной теме или теме по умолчанию.
     * Обычно указывает на каталог внутри пакета (для vendor-компонентов)
     * или внутри проекта (для локальных компонентов).
     *
     * Если возвращает пустую строку — шаблон по умолчанию не предусмотрен,
     * и отсутствие шаблона в темах приведёт к исключению.
     *
     * Рекомендуемая структура путей:
     * - Для проектов: /templates/components/{vendor}/{component}/
     * - Для пакетов: /templates/components/{component}/
     */
    abstract public function getDefaultTemplatePath(): string;

    /**
     * Устанавливает имя шаблона компонента.
     *
     * Шаблон определяет поддиректорию внутри каталога компонента,
     * из которой будут загружаться файлы шаблона и ассетов.
     * Пустая строка отключает визуальное представление компонента.
     *
     * @param string $name Имя шаблона или пустая строка для режима без рендеринга
     *
     * @return $this
     */
    public function setTemplateName(string $name): static
    {
        $this->templateName = $name;

        return $this;
    }

    /**
     * Устанавливает опции компонента.
     *
     * Передача null означает «оставить текущие/дефолтные опции без изменений».
     * Это позволяет шаблонизатору вызывать setOptions($context['options'] ?? null)
     * без риска сбросить значения по умолчанию.
     *
     * Реализация/тип опций определяется разработчиком конкретного компонента
     *
     * @param mixed $options Опции компонента или null для сохранения текущих
     *
     * @return $this
     */
    public function setOptions(mixed $options): static
    {
        if (null !== $options) {
            $this->options = $options;
        }

        return $this;
    }

    /**
     * Выполняет бизнес-логику компонента.
     *
     * Вызывается ВСЕГДА при компиляции, даже если компонент не имеет визуального представления.
     * Предназначен для подготовки данных, изменения заголовка страницы и других операций,
     * не зависящих от наличия шаблона.
     *
     * Переопределяйте в дочерних классах для реализации логики компонента.
     *
     * @param PageBuilder $pageBuilder Билдер страницы для управления мета-данными
     */
    protected function execute(PageBuilder $pageBuilder): void {}

    /**
     * Подготавливает страницу перед рендерингом шаблона.
     *
     * Вызывается ТОЛЬКО если $templateName не пуст.
     * Предназначен для подключения CSS/JS, настройки мета-тегов и других операций,
     * зависящих от наличия шаблона.
     *
     * На момент вызова $this->templatePath уже установлен,
     * метод templateFile() доступен для формирования путей к подключаемым файлам.
     *
     * Переопределяйте в дочерних классах для подключения ресурсов компонента.
     *
     * @param PageBuilder $pageBuilder Билдер страницы для управления ресурсами
     */
    protected function beforeRender(PageBuilder $pageBuilder): void {}

    /**
     * Формирует абсолютный путь к файлу внутри директории текущего шаблона компонента.
     *
     * Доступен только после установки $this->templatePath в методе compile().
     * Используется в beforeRender() для подключения ассетов и в compile() для указания файла шаблона.
     *
     * @param non-empty-string $fileName Имя файла (например, 'script.css', 'template.php')
     *
     * @return non-empty-string Абсолютный путь к файлу
     */
    protected function templateFile(string $fileName): string
    {
        return $this->templatePath . \DIRECTORY_SEPARATOR . $fileName;
    }

    /**
     * Возвращает данные контекста для передачи в шаблон компонента.
     *
     * Массив будет передан в includeFile() как основной контекст шаблона.
     * Переопределяйте в дочерних классах для передачи переменных в шаблон.
     *
     * @return array<string, mixed>
     */
    protected function getContext(): array
    {
        return [];
    }

    /**
     * Компилирует и рендерит компонент.
     *
     * Жизненный цикл:
     * 1. execute($pageBuilder) — выполняется всегда
     * 2. Если templateName пуст — завершение (компонент без визуального представления)
     * 3. Резолвинг пути к директории шаблона через TemplateEngine
     * 4. beforeRender($pageBuilder) — подготовка подключаемых файлов
     * 5. includeFile() — рендеринг шаблона с контекстом и TTL
     *
     * В шаблон автоматически передаётся переменная `component`, содержащая
     * текущий экземпляр компонента, для доступа к его публичным данным и методам.
     *
     * @param TemplateEngine    $engine   Движок шаблонизатора для резолвинга путей и рендеринга
     * @param TemplatedResponse $response Ответ, содержащий PageBuilder для настройки страницы
     *
     * @throws TemplatorException если файл шаблона не найден или ошибка компиляции
     */
    public function compile(TemplateEngine $engine, TemplatedResponse $response): void
    {
        $this->execute($response->builder);
        if ('' === $this->templateName) {
            return;
        }
        $this->templatePath = $engine->getComponentTemplateDir($this, $this->templateName);
        $this->beforeRender($response->builder);
        $engine->includeFile(
            $this->templateFile($this->componentTemplateFile . '.php'),
            $this->getContext(),
            $this->fileTtl,
            ['component' => $this],
        );
    }
}
