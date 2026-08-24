# Установка и настройка

## Требования

- **PHP 8.5+** — используются свойства с хуками и `private(set)`, на более ранних версиях пакет не запустится.
- **Расширения PHP** — специальных расширений не требуется: экранирование выполняется через `htmlspecialchars()`, работа
  с файлами — средствами стандартной библиотеки.
- **Фреймворк** — `voral/joke` ^1.5. Пакет опирается на его сервисы: `ServiceContainer`, `FileSystem` (алиас `paths`),
  `FileRelatedCache`, `HtmlPageResponse`, `CsrfMiddleware`.
- **Composer** с автозагрузкой PSR-4 (`Vasoft\Joke\Templator\` → `src/`).
- Права на запись в каталог `var/` проекта — там размещается кэш скомпилированных шаблонов.

## Установка

```bash
composer require joke-php/templator
```

Затем зарегистрируйте `TemplatorProvider` в `bootstrap/kernel.php`:

```php
<?php

declare(strict_types=1);

use Vasoft\Joke\Application\KernelConfig;
use Vasoft\Joke\Templator\TemplatorProvider;

return new KernelConfig()
    ->addProvider(TemplatorProvider::class);
```

Провайдер регистрирует в контейнере `TemplateEngine`, лексер, парсер, компилятор, рендерер и `ComponentCollection`, а в
`boot()` — стандартные токены (`{{ }}`, `{% %}`), обработчики узлов AST и директивы.

## Базовая конфигурация

Отдельная инициализация обычно не нужна: движок создаётся контейнером как синглтон при первом обращении. Прямое создание
требует контейнера, в котором зарегистрирован сервис `paths`:

```php
use Vasoft\Joke\Templator\TemplateEngine;

$engine = new TemplateEngine($container);
$engine->setTemplate('dark'); // активная тема
```

### Пути к шаблонам

Пути вычисляются от базового каталога приложения (первый аргумент `Application`):

```
templates/
├── default/               # тема по умолчанию (TemplateEngine::DEFAULT_TEMPLATE)
│   ├── layouts/           # каркасы: {% layout main %} → layouts/main.php
│   └── components/        # шаблоны компонентов: {vendor}/{name}/
└── dark/                  # альтернативная тема, та же структура
pages/                     # файлы страниц, передаются в show()
```

Поиск каскадный: сначала активная тема, затем `default`, для компонентов — плюс путь из `getDefaultTemplatePath()`
самого компонента. Тема переключается через `TemplateEngine::setTemplate()` или третьим аргументом конструктора
`TemplatedResponse`.

Настройки, не связанные с путями, задаются через `TemplatorConfig` (например, кодировка экранирования):

```php
use Vasoft\Joke\Templator\TemplatorConfig;

$container->get(TemplatorConfig::class)->setEncoding('UTF-8');
```

### Кэш

Скомпилированные шаблоны сохраняются в `var/cache/templator/` (`FileSystem::atCache('templator')`) и инвалидируются по
времени изменения исходного файла и TTL. Каталог создаётся автоматически, если у процесса PHP есть права на запись:

```bash
mkdir -p var/cache
chmod -R 775 var
chown -R www-data:www-data var
```

TTL задаётся при рендеринге. На время разработки удобно значение `0` — компиляция при каждом запросе:

```php
$response->show('pages/index.php', $context, 0);      // без кэша
$response->show('pages/index.php', $context, 86400);  // сутки
```

## Интеграция с DI-контейнером Joke

Регистрация выполняется провайдером, вручную дублировать её не нужно:

```php
// TemplatorProvider::register()
$this->serviceContainer->registerSingleton(TemplateEngine::class, TemplateEngine::class);
$this->serviceContainer->registerSingleton(ComponentCollection::class, ComponentCollection::class);
```

В обработчике маршрута зависимости подставляются по типам параметров через `ParameterResolver`:

```php
use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Templator\TemplateEngine;
use Vasoft\Joke\Templator\TemplatedResponse;

$router->get(
    '/',
    static function (ServiceContainer $container, TemplateEngine $engine) {
        return new TemplatedResponse($container, $engine, 'default')
            ->show('pages/hello.php', ['name' => 'alex'], 0);
    },
);
```

`TemplatedResponse` контейнером не резолвится (требуется имя темы), поэтому создаётся вручную. Если экземпляр нужен
внутри компонентов, зарегистрируйте его: `$container->registerSingleton(TemplatedResponse::class, $response);`.

## Проверка установки

Создайте `pages/hello.php`:

```html
<h1>{{ name }}</h1>
<p>{% if name %}Шаблонизатор работает{% /if %}</p>
```

Добавьте маршрут из примера выше и запустите встроенный сервер:

```bash
composer dev
# или: php -S localhost:8001 -t public/
```

По адресу `http://localhost:8001/` должен появиться заголовок `alex`. Если директивы выводятся как текст — провайдер не
зарегистрирован.

## Возможные проблемы

**`FileSystemException: Unable to create directory: '.../var/cache/templator'`** — у процесса PHP нет прав на запись в
`var/`. Создайте каталог заранее и выдайте права владельцу процесса (`www-data`, `php-fpm`).

**`TemplatorException: Unable to locate layout file: main`** — файл каркаса отсутствует и в активной теме, и в
`default`. Проверьте наличие `templates/{тема}/layouts/main.php` и совпадение регистра имени.

**Правки в шаблоне не появляются на странице** — включён кэш. Передайте `ttl = 0` в `show()` на время разработки либо
очистите каталог: `rm -rf var/cache/templator/*`.

**`Unknown config class: TemplatorConfig` или отсутствие `TemplateEngine` в контейнере** — `TemplatorProvider` не
добавлен в `bootstrap/kernel.php` или добавлен после кода, который запрашивает движок.

Далее: [02-quick-start.md](02-quick-start.md).
