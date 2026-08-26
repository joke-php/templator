# Конфигурация шаблонизатора

## Назначение

Шаблонизатор настраивается в двух независимых местах: **`TemplateEngine`** отвечает за пути к активной теме,
**`TemplatorConfig`** — за кодировку вывода и реестр токенов/директив/обработчиков узлов AST. Отдельного
класса-конфигуратора с сеттерами вида `setTemplateDir()`/`setCacheDir()` в пакете нет — пути к темам и кэшу вычисляются
автоматически от базового пути приложения.

## Основные параметры конфигурации

- **Пути к шаблонам** — не задаются напрямую; вычисляются как `{basePath}/templates/{имя темы}/` через сервис
  `FileSystem`. Единственный управляемый параметр — само имя активной темы.
- **Путь к кэшу** — фиксированно `{basePath}/var/cache/templator/` (`FileSystem::atCache('templator')`), не
  настраивается отдельно для пакета (см. [Кеширование](08-caching.md)).
- **Кодировка вывода** — `TemplatorConfig::$encoding`, по умолчанию `UTF-8`, используется в `htmlspecialchars()` для
  `{{ }}` и обычного `{% defer %}`.
- **TTL каркаса по умолчанию** — `TemplatorConfig::$defaultTtl`, по умолчанию `86400`. Используется директивой
  `{% layout %}` для подключения файла каркаса (см. [Директива layout](04-directives/04-layout.md)) — не
  путать с TTL, который передаётся в `show()`/`includeFile()` для самой страницы (см. [Кеширование](08-caching.md)).

## Методы настройки (API конфигурации)

```php
use Vasoft\Joke\Templator\TemplateEngine;
use Vasoft\Joke\Templator\TemplatorConfig;

// переключение активной темы — единственный "путевой" сеттер движка
$engine->setTemplate('dark');

// кодировка — через TemplatorConfig, а не через TemplateEngine
$config = $container->get(TemplatorConfig::class);
$config->setEncoding('UTF-8');

// TTL кэша по умолчанию для файла каркаса, подключаемого директивой {% layout %}
$config->setDefaultTtl(3600);
```

`setEncoding()`/`setDefaultTtl()` доступны только пока конфигурация не «заморожена» (`freeze()`) — вызвать их после
того, как `TemplatorProvider::boot()` уже прочитал конфигурацию из контейнера, не получится (`ConfigException`).
Практический способ задать параметры заранее — положить PHP-файл в базовую директорию конфигурации приложения (
`config/`, читается `ConfigManager` до вызова `boot()` у всех провайдеров), который возвращает готовый экземпляр:

```php
// config/templator.php
use Vasoft\Joke\Templator\TemplatorConfig;

return new TemplatorConfig()->setEncoding('Windows-1251')->setDefaultTtl(3600);
```

Такой файл регистрируется в контейнере раньше, чем `TemplatorProvider` успевает получить конфигурацию по умолчанию,
поэтому кастомизация не конфликтует с заморозкой.

## Структура каталога шаблонов

```
templates/
├── default/                # тема по умолчанию, TemplateEngine::DEFAULT_TEMPLATE
│   ├── layouts/{имя}.php
│   └── components/{vendor}/{name}/{templateName}/template.php
├── {другая тема}/           # та же структура, каскад: тема → default
└── components/{vendor}/{name}/{templateName}/  # fallback для компонентов вне тем
```

Все файлы шаблонов, каркасов и компонентов — с расширением `.php` (шаблонизатор компилирует их в PHP и подключает через
`include`), других расширений (`.html`, `.tpl`) движок не ищет.

## Регистрация расширений через TemplatorConfig

Собственная директива регистрируется двумя вызовами: правило синтаксиса в `directiveCollection` и класс-обработчик:

```php
use Vasoft\Joke\Templator\Lexer\StatementToken;

// одиночная директива (без закрывающего тега), как csrf/defer/include
$config->directiveCollection->upsert(StatementToken::class, 'custom');
$config->addDirectiveHandler('custom', CustomHandler::class);
```

Для блочной директивы (с закрывающим тегом и, возможно, ветками вроде `elseif`) третий и четвёртый аргументы `upsert()`
задают имя закрывающей директивы и список веток. Обработчик своего узла AST регистрируется отдельно:

```php
$config->addNodeHandler(CustomNode::class, CustomNodeHandler::class);
```

Оба обработчика должны реализовать `NodeHandlerInterface` (`compile()` и `render()`) — подробнее
в [Создание собственных директив](10-extending/01-custom-directive.md).

Помимо директив, через `TokenCollection` можно зарегистрировать собственный маркер лексера — если стандартных `{{ }}`/
`{% %}` недостаточно (например, нужен третий тип разделителя для отдельной подсистемы):

```php
use Vasoft\Joke\Templator\Lexer\TokenDescriptor;
use Vasoft\Joke\Templator\Lexer\StatementToken;

$config->tokenCollection->upsert(
    new TokenDescriptor(open: '{#', close: '#}', tokenClass: StatementToken::class),
);
```

Открывающий разделитель (`open`) должен быть уникальным — повторная регистрация с тем же маркером выбрасывает
`TemplatorException`. Это низкоуровневая настройка: в большинстве случаев для новой директивы достаточно
`directiveCollection->upsert()` и `addDirectiveHandler()` в рамках уже существующих токенов `{{ }}`/`{% %}`.

## Полный пример конфигурации

```php
<?php

declare(strict_types=1);

use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Provider\AbstractProvider;
use Vasoft\Joke\Templator\Lexer\StatementToken;
use Vasoft\Joke\Templator\Lexer\TokenDescriptor;
use Vasoft\Joke\Templator\TemplatorConfig;

class DemoProvider extends AbstractProvider
{
    public function __construct(
        private readonly ServiceContainer $serviceContainer,
    ) {}

    public function register(): void {}

    public function boot(): void
    {
        /** @var TemplatorConfig $config */
        $config = $this->serviceContainer->get(TemplatorConfig::class);
        $config->tokenCollection->upsert(
            new TokenDescriptor(open: '{#', close: '#}', tokenClass: StatementToken::class),
        );
        $config->directiveCollection->upsert(StatementToken::class, 'bold');
        $config->addDirectiveHandler('bold', BoldRawHandler::class);
    }

    public function provides(): array
    {
        return [];
    }
}
```

```php
// в обработчике маршрута
$engine->setTemplate('dark');
```

## Что дальше

[Создание собственных директив](10-extending/01-custom-directive.md)
