# Быстрый старт

## Цель

За два шага получить рабочую страницу: маршрут, который отдаёт `TemplatedResponse`, и шаблон с выводом переменной,
условием и циклом. Пакет уже установлен и `TemplatorProvider` зарегистрирован — если нет,
см. [Установка и настройка](01-installation.md).

## Шаг 1: Создание маршрута

В `routes/web.php` регистрируется обработчик — замыкание, которое получает `ServiceContainer` и `TemplateEngine` через
DI (контейнер резолвит их по типам параметров):

```php
<?php

declare(strict_types=1);
use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Routing\Router;
use Vasoft\Joke\Templator\TemplateEngine;
use Vasoft\Joke\Templator\TemplatedResponse;

/** @var Router $router */
$router->get(
    '/',
    static function (ServiceContainer $container, TemplateEngine $engine) {
        $response = new TemplatedResponse($container, $engine);
        $response->setTemplateName('dark');
        $response->builder->setTitle('Пример');

        return $response->show(
            'pages/index.php',
            [
                'user' => ['name' => 'alex'],
                'extend' => false,
            ],
            0, // ttl: без кэша, удобно на время разработки
        );
    },
);
```

Так же возможно использовать механизм типа ответа по умолчанию. Для этого зарегистрировать в конфигурации приложения в
файле `config/app.php`:

```php
<?php
// config/app.php

declare(strict_types=1);

use Vasoft\Joke\Application\ApplicationConfig;
use Vasoft\Joke\Templator\TemplatedResponse;
use Vasoft\Joke\Templator\TemplatedResponse;

return new ApplicationConfig()
    ->setResponseClass(TemplatedResponse::class);
```

И далее уже можно использовать в контроллерах фабрику ответов

```php
<?php

declare(strict_types=1);
use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Routing\Router;
use Vasoft\Joke\Templator\TemplateEngine;
use Vasoft\Joke\Templator\TemplatedResponse;

/** @var Router $router */
$router->get(
    '/',
    static function (ResponseBuilder $builder) {
        $response = new TemplatedResponse($container, $engine);
        $response->setTemplateName('dark');
        $response->builder->setTitle('Пример');

        return $response->show(
            'pages/index.php',
            [
                'user' => ['name' => 'alex'],
                'extend' => false,
            ],
            0, // ttl: без кэша, удобно на время разработки
        );
    },
);
```

## Шаг 2: Создание файла страницы

Файлы страниц сайта рекомендуется создавать в каталоге pages. Минимальный вариант с примером использования условия,
цикла и переменных:

```html
<h1>{%defer page.title raw%}</h1>

<p>
    Привет, {{ user.name }}
</p>
<p>
    {% if extend %}
    Расширенный режим
    {% else %}
    Обычный режим
    {% /if %}
</p>

<ul>
    {% foreach item in status %}
    <li>{{ item }}</li>
    {% /foreach %}
</ul>

```

`{%defer page.title %}` отложено выводит значение заголовка страницы,  
`{{ user.name }}` выводит значение с HTML-экранированием, `{% if %}/{% else %}` — стандартное ветвление,
`{% foreach item in list %}` — перебор массива. Все переменные берутся из `$context`, переданного в `show()`, доступ к
вложенным данным — через точку (`user.name`).

## Шаг 3: Создание шаблона

Шаблоны размещаются в каталогах `templates/<имя_шаблона>`. Внутри которого так же могут быть подкаталоги layouts - для размещение каракасов и components для размещения шаблонов компонентов. Так же здесь может быть расположен файл config.php для настройки шаблона. Например, для подключения файлов стилей. В этом файле доступен ряд объектов 
```php
<?php
// templates/dark/config.php
declare(strict_types=1);

use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Templator\TemplatedResponse;
use Vasoft\Joke\Templator\TemplateEngine;

/**
 * @var TemplatedResponse $response     Текущий ответ
 * @var ServiceContainer  $container    Контейнер зависимостей
 * @var TemplateEngine    $engine       Движок шаблонизатора
 * @var string            $templatePath Путь к текущему шаблону
 */
$response->builder->css->addToBody($templatePath . 'assets/styles.css');
```
Добавим для примера файл стилей `templates/dark/assets/styles.css`:
```css
body {
    background-color: #eee;
    color: #333;
}
```

## Шаг 3: Запуск и проверка

Запустите встроенный сервер PHP из корня проекта:

```bash
composer dev
# эквивалент: php -S localhost:8001 -t public/
```

Откройте `http://localhost:8001/` — должна отрендериться страница с заголовком «Пример» и текстами «Привет, alex» и «Обычный режим» (
значение `extend` — `false`). Если вместо этого видна директива как обычный текст (`{% if %}` не обработан) — провайдер
`TemplatorProvider` не зарегистрирован в `bootstrap/kernel.php`.

## Полный рабочий пример

**`routes/web.php`:**

```php
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
            ['user' => ['name' => 'alex'], 'extend' => false, 'status' => ['готово', 'в работе']],
            0,
        );
    },
);
```

**`pages/index.php`:**

```html
<h1>{%defer page.title raw%}</h1>
<p>Привет, {{user.name}}</p>
<p>{% if extend %}Расширенный режим{% else %}Обычный режим{% /if %}</p>
<ul>
    {% foreach item in status %}
    <li>{{ item }}</li>
    {% /foreach %}
</ul>
```

**Результат в браузере** (`http://localhost:8001/`):

```
Пример
Привет, alex
Обычный режим
• готово
• в работе
```

Заголовок вкладки браузера — «Пример» (задан через `$response->builder->setTitle()`), тело страницы собрано из HTML выше
без дополнительных обёрток — `{% layout %}` в этом примере не используется.

## Что дальше

[Основы синтаксиса](03-syntax-basics.md) — полный синтаксис `{{ }}` и `{% %}`, точечная нотация, различие компиляции
  и интерпретации директив.
