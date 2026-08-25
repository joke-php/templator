# Компоненты шаблонизатора

## Назначение

Компонент — это класс, инкапсулирующий и логику, и (опционально) собственное визуальное представление: карточка дня,
виджет со случайным числом, блок навигации и т.п. В отличие от `{% include %}`, который лишь регистрирует статический
CSS/JS-файл без какой-либо логики (см. [Подключаемые файлы](04-directives/06-include.md)), компонент — это
полноценный объект с жизненным циклом, собственным набором данных для шаблона (`getContext()`) и возможностью влиять на
страницу в целом (заголовок, подключаемые ресурсы) независимо от места вызова в разметке.

## Жизненный цикл компонента

`BaseComponent::compile(TemplateEngine $engine, TemplatedResponse $response)` выполняет строго определённую
последовательность:

1. **`execute(PageBuilder $pageBuilder)`** — выполняется **всегда**, независимо от того, есть ли у компонента шаблон.
   Здесь готовятся данные (например, случайное число, выборка из БД) и, при необходимости, меняются глобальные параметры
   страницы через `$pageBuilder` (заголовок, мета-теги).
2. Если `$templateName` — пустая строка, жизненный цикл на этом завершается: компонент отработал только логику, без
   вывода. Это осознанный режим «компонент без визуального представления».
3. Иначе резолвится каталог шаблона компонента через каскад тем (`TemplateEngine::getComponentTemplateDir()`).
4. **`beforeRender(PageBuilder $pageBuilder)`** — вызывается только если есть шаблон; типичное применение — подключить
   собственные CSS/JS компонента, лежащие рядом с его шаблоном.
5. **`includeFile()`** — рендерит `template.php` компонента, передавая туда `getContext()` как основной контекст и
   `$component` (сам экземпляр) отдельной переменной.

## Создание собственного компонента (пошагово)

### Шаг 1: Создание класса компонента

Компонент наследует `BaseComponent` и обязан реализовать `vendor()`, `name()` и `getDefaultTemplatePath()`:

```php
<?php

declare(strict_types=1);

namespace App\Templator\Component;

use Vasoft\Joke\Http\Response\Html\PageBuilder;
use Vasoft\Joke\Templator\Component\BaseComponent;

class GreetingComponent extends BaseComponent
{
    private string $text = '';

    public static function vendor(): string
    {
        return 'app';
    }

    public static function name(): string
    {
        return 'greeting';
    }

    public function getDefaultTemplatePath(): string
    {
        return dirname(__DIR__, 2) . '/templates/components/app/greeting/';
    }

    protected function execute(PageBuilder $pageBuilder): void
    {
        $hour = (int) date('H');
        $this->text = $hour < 12 ? 'Доброе утро' : 'Добрый день';
    }

    protected function getContext(): array
    {
        return ['text' => $this->text];
    }
}
```

`execute()` содержит только бизнес-логику компонента и не должен ничего выводить напрямую — результат передаётся в
шаблон через `getContext()`.

### Шаг 2: Создание шаблона компонента

Шаблон лежит в `templates/{тема}/components/{vendor}/{name}/{templateName}/template.php` (или в каталоге из
`getDefaultTemplatePath()`, если в темах его нет) и использует переменные из `getContext()`:

```html
<p class="greeting">{{ text }}</p>
```

Внутри доступна и переменная `$component` — например, для обращения к публичным методам компонента, если они понадобятся
напрямую в разметке.

### Шаг 3: Регистрация компонента

Регистрация выполняется в сервис-провайдере, в методе `boot()` — на реальном примере `DemoProvider` из демо:

```php
<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Demo;

use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Provider\AbstractProvider;
use Vasoft\Joke\Templator\Component\ComponentCollection;
use Vasoft\Joke\Templator\Demo\Component\DayComponent;
use Vasoft\Joke\Templator\Demo\Component\RandomComponent;

class DemoProvider extends AbstractProvider
{
    public function __construct(private readonly ServiceContainer $serviceContainer) {}

    public function register(): void {}

    public function boot(): void
    {
        $components = $this->serviceContainer->get(ComponentCollection::class);
        $components->set(RandomComponent::componentName(), RandomComponent::class);
        $components->set(DayComponent::componentName(), DayComponent::class);
    }

    public function provides(): array
    {
        return [];
    }
}
```

`ComponentCollection::set()` принимает готовый экземпляр, `class-string` (как здесь — компонент создаётся лениво через
DI-контейнер при первом обращении) или фабрику-`callable`. `componentName()` — это `vendor():name()`, то же значение,
что указывается в шаблоне. Провайдер регистрируется в `bootstrap/kernel.php` через `addProvider(DemoProvider::class)`.

## Использование компонента в шаблоне

```html
{% component app:greeting %}
```

Полный синтаксис с именем шаблона и опциями — `{% component vendor:name [templateName] [options] %}` — подробно описан
в [Директиве подключения компонента](04-directives/05-component.md). Каталог шаблона ищется по тому же каскаду, что и
каркасы: сначала `templates/{активная тема}/components/{vendor}/{name}/{templateName}/`, затем
`templates/default/components/...`, и только если ни там, ни там ничего нет — `getDefaultTemplatePath()` самого
компонента (обычно каталог внутри пакета/проекта, поставляемый вместе с классом). Если каталог не найден ни в одной из
трёх локаций — `TemplatorException: Unable to locate template`.

## Продвинутые возможности

### Передача параметров в компонент

`setOptions()` можно переопределить, чтобы принимать структурированные данные вместо `mixed`. Реальный пример из демо,
`RandomComponent`:

```php
public function setOptions(mixed $options): static
{
    if (isset($options['min'])) {
        $this->min = (int) $options['min'];
    }
    if (isset($options['max'])) {
        $this->max = (int) $options['max'];
    }

    return $this;
}
```

Опции передаются директивой третьим аргументом — путём к переменной контекста:
`{% component vasoft:random variant2 randomRange %}`.

### Взаимодействие с DeferService

Компонент не обязан работать с `DeferService` напрямую — достаточно вызвать `$pageBuilder->setTitle()` внутри
`execute()`, и `TemplatedResponse::show()` сам подставит это значение во все места страницы, где встречается
`{% defer page.title %}`, независимо от того, что компонент отрендерился ниже по разметке. Пример из демо,
`RandomComponent::execute()`:

```php
protected function execute(PageBuilder $pageBuilder): void
{
    $this->demo = (string) random_int($this->min, $this->max);
    $pageBuilder->setTitle('example<small> 1</small>');
}
```

Подробности механизма — в [Директиве отложенного вывода](04-directives/08-defer.md).

### Подключение собственных ресурсов

В `beforeRender()` удобно проверять существование файла перед подключением, чтобы не регистрировать несуществующий
ассет:

```php
protected function beforeRender(PageBuilder $pageBuilder): void
{
    $css = $this->templateFile('style.css');
    if (file_exists($css)) {
        $pageBuilder->css->addToBody($css);
    }
}
```

## Что дальше

[Каркасы и наследование шаблонов](05-components.md)
