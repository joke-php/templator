# Интеграция с фреймворком Joke

## Назначение

Шаблонизатор не работает автономно — он встраивается в жизненный цикл приложения Joke на нескольких уровнях: регистрация
сервисов через `TemplatorProvider`, автоматический DI-резолвинг зависимостей в обработчиках маршрутов и формирование
HTTP-ответа через `TemplatedResponse`. Этот документ описывает весь путь от входящего запроса до отправленного клиенту
HTML.

## Регистрация провайдера

Провайдер добавляется в `bootstrap/kernel.php`:

```php
<?php

declare(strict_types=1);

use Vasoft\Joke\Application\KernelConfig;
use Vasoft\Joke\Templator\TemplatorProvider;
use Vasoft\Joke\Templator\Demo\DemoProvider;

return new KernelConfig()
    ->addProvider(TemplatorProvider::class)
    ->addProvider(DemoProvider::class); // регистрирует демо-компоненты
```

При старте приложения `TemplatorProvider::register()` регистрирует в контейнере основные сервисы как синглтоны:

```php
$this->serviceContainer->registerSingleton(TemplateEngine::class, TemplateEngine::class);
$this->serviceContainer->registerSingleton(LexerInterface::class, DefaultLexer::class);
$this->serviceContainer->registerSingleton(ParserInterface::class, DefaultParser::class);
$this->serviceContainer->registerSingleton('templator.compiler', DefaultCompiler::class);
$this->serviceContainer->registerSingleton('templator.renderer', DefaultRenderer::class);
$this->serviceContainer->registerSingleton(ComponentCollection::class, ComponentCollection::class);
```

После регистрации всех провайдеров вызывается `boot()` — на этом этапе `TemplatorConfig` уже доступен в контейнере, и
провайдер настраивает лексические токены (`{{ }}`, `{% %}`), обработчики узлов AST (`TextNode`, `PrintNode`,
`BlockNode`, `StatementNode`) и связывает имена директив (`if`, `foreach`, `csrf` и т.д.) с классами-обработчиками.

## DI-резолвинг в контроллерах и маршрутах

Обработчики маршрутов не должны сами обращаться к контейнеру — `Route::run()` резолвит параметры замыкания или
конструктора класса по типам через `ParameterResolver`, независимо от того, чем является обработчик: замыканием,
`[класс, метод]`, `Class::method` или инвокабл-классом.

```php
use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Templator\TemplateEngine;

$router->get(
    '/',
    static function (ServiceContainer $container, TemplateEngine $engine) {
        // оба параметра подставлены контейнером по типу
    },
);
```

`TemplateEngine` при первом обращении создаётся контейнером как синглтон (он был зарегистрирован в `register()`),
поэтому явный `new TemplateEngine(...)` в коде приложения не нужен.

Тот же механизм работает и для класс-контроллеров, а не только для замыканий. `Route::run()` проверяет, является ли
обработчик именем существующего класса: если да, аргументы конструктора резолвятся через
`ParameterResolver::resolveForConstructor()`, экземпляр создаётся, а затем вызывается его `__invoke()` — тоже с
резолвингом параметров:

```php
use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Templator\TemplateEngine;
use Vasoft\Joke\Templator\TemplatedResponse;

final class IndexController
{
    public function __construct(private readonly ServiceContainer $container) {}

    public function __invoke(TemplateEngine $engine): TemplatedResponse
    {
        return new TemplatedResponse($this->container, $engine)
            ->show('pages/index.php', ['name' => 'alex'], 0);
    }
}

$router->get('/', IndexController::class);
```

Здесь `ServiceContainer` внедряется через конструктор, а `TemplateEngine` — через параметр `__invoke()`; оба резолвятся
одним и тем же `ParameterResolver`, без ручного вызова `$container->get()`.

## Формирование ответа (TemplatedResponse)

```php
use Vasoft\Joke\Templator\TemplatedResponse;

$response = new TemplatedResponse($container, $engine);
$response->builder->setTitle('Пример'); // HTTP-заголовок <title> страницы

return $response->show(
    'pages/index.php',
    ['name' => 'alex', 'extend' => false],
    160000
);
```

`show()` — единственный метод, который нужен для рендеринга: он подключает файл темы, буферизует вывод `includeFile()`,
прогоняет буфер через `DeferService::flush()` (для `{% defer %}`) и вызывает `setBody()`. `TemplatedResponse` наследует
`HtmlPageResponse extends HtmlResponse extends Response` — HTTP-статус задаётся методом базового
`Response::setStatus()`, унаследованным как обычно, и `show()` его не затрагивает:

```php
use Vasoft\Joke\Http\Response\ResponseStatus;

$response = new TemplatedResponse($container, $engine);

return $response
    ->setStatus(ResponseStatus::NOT_FOUND)
    ->show('pages/404.php', [], 0);
```

Порядок вызовов не важен — `setStatus()` и `show()` независимо модифицируют один и тот же объект `Response` (статус и
тело), оба возвращают `$this`, поэтому их можно объединять в цепочку в любом порядке.

## Жизненный цикл запроса (Application::handle)

```
HttpRequest
  → Application::handle()
      → processMiddlewares() [глобальные middleware]
          → handleRoute()
              → Router::findRoute()
              → processMiddlewares() [middleware группы 'web': Session, Csrf]
                  → Route::run() — резолвинг параметров, вызов обработчика
                      → TemplatedResponse::show()
                          → TemplateEngine::includeFile() — компиляция + кэш + include
                          → DeferService::flush()
  → ResponseBuilder::make($response)->send()
→ Клиент
```

Компиляция шаблона в PHP-код и его выполнение происходят строго внутри `show()`, до того как управление вернётся из
обработчика маршрута в `Application::handle()`. Финальная отправка заголовков и тела клиенту — уже задача
`ResponseBuilder`, шаблонизатор в ней не участвует.

## Особенности Middleware

Все маршруты, зарегистрированные в `routes/web.php`, автоматически попадают в группу `web` (
`Router::addAutoGroups([StdGroup::WEB->value])` — это делает `RouterServiceProvider`, а не пользовательский код). К этой
группе framework жёстко привязывает `SessionMiddleware` и `CsrfMiddleware`:

- **`SessionMiddleware`** гарантирует, что сессия открыта до вызова обработчика — от неё зависит `CsrfTokenManager`,
  хранящий серверный токен в сессии.
- **`CsrfMiddleware`** проверяет CSRF-токен для всех небезопасных методов (`POST`, `PUT`, `PATCH`, `DELETE`) ещё до
  того, как выполнится обработчик маршрута — именно поэтому директива `{% csrf %}` в шаблоне гарантированно получает
  актуальный токен: сессия и токен-менеджер уже инициализированы к моменту рендеринга.

Если обработчик зарегистрирован не через `routes/web.php`, а иным способом (без группы `web`), директива `{% csrf %}`
по-прежнему сгенерирует токен, но проверка на входе выполняться не будет — CSRF-защита окажется фиктивной.

## Полный пример интеграции

```php
// bootstrap/kernel.php
return new KernelConfig()->addProvider(TemplatorProvider::class);
```

```php
// routes/web.php
use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Templator\TemplateEngine;
use Vasoft\Joke\Templator\TemplatedResponse;

$router->get(
    '/',
    static function (ServiceContainer $container, TemplateEngine $engine) {
        $response = new TemplatedResponse($container, $engine);
        $response->setTemplateName('dark');
        $response->builder->setTitle('Пример');

        return $response->show(
            'pages/index.php',
            ['name' => 'alex', 'extend' => false],
            120,
        );
    },
);
```

`pages/index.php`:

```html
<h1>{{ name }}</h1>
<p>{% if extend %}test1{% else %}test2{% /if %}</p>
```

Запрос `GET /` проходит через глобальные и групповые (`web`) middleware, резолвится в замыкание, внутри которого
создаётся `TemplatedResponse`, компилируется и рендерится `pages/index.php`, а результат `ResponseBuilder` отправляет
клиенту как обычный HTML-ответ.

## Что дальше

[Кеширование](08-caching.md)

