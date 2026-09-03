# Joke Templator

Шаблонизатор для микрофреймворка [Joke](https://github.com/joke-php/joke).

Шаблоны компилируются в PHP-код и кэшируются на
файловой системе; синтаксис ограничен небольшим набором директив, что делает пакет пригодным для учебных и небольших
проектов. Набор директив и обработчиков узлов расширяется без правки ядра, а подключение сводится к регистрации одного
сервис провайдера

---

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/joke-php/templator/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/joke-php/templator/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/joke-php/templator/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/joke-php/templator/?branch=master)
![PHP Tests](https://github.com/joke-php/templator/actions/workflows/php.yml/badge.svg)

---

## Требования

- PHP 8.5 или выше
- `voral/joke` ^1.5

## Установка

```bash
composer require joke-php/templator
```

Регистрация провайдера в `bootstrap/kernel.php`:

```php
use Vasoft\Joke\Templator\TemplatorProvider;

return new KernelConfig()
    ->addProvider(TemplatorProvider::class);
```

## Быстрый старт

Обработчик маршрута в `routes/web.php`. `ServiceContainer` и `TemplateEngine` подставляются контейнером по типам
параметров:

```php
use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Templator\TemplatedResponse;
use Vasoft\Joke\Templator\TemplateEngine;

$router->get(
    '/',
    static function (ServiceContainer $container, TemplateEngine $engine) {
        $response = new TemplatedResponse($container, $engine);

        return $response->show('pages/index.php', ['name' => 'alex'], 0);
    },
);
```

Шаблон `pages/index.php`:

```html
<h1>Привет, {{ name }}!</h1>
```

Третий аргумент `show()` — TTL кэша в секундах; `0` отключает кэширование на время разработки.

## Основные возможности

- **Вывод переменных.** `{{ }}` экранирует HTML, `{% raw %}` выводит значение как есть. Вложенные данные доступны через
  точку:

  ```html
  {{ user.profile.name }}
  {% raw description %}
  ```

- **Условия.**

  ```html
  {% if user.admin %}Администратор{% else %}Гость{% endif %}
  ```

- **Циклы.** Поддерживается перебор значений и пар «ключ — значение»:

  ```html
  {% foreach item in items %}<li>{{ item.name }}</li>{% endforeach %}
  {% foreach key, value in map %}{{ key }}: {{ value }}{% endforeach %}
  ```

- **Наследование шаблонов.** Содержимое страницы оборачивается в каркас из `templates/{тема}/layouts/`, при отсутствии
  файла в активной теме используется `default`:

  ```html
  {% layout main %}
      <p>Содержимое страницы</p>
  {% endlayout %}
  ```

- **Компоненты.** Классы на базе `BaseComponent` с собственным шаблоном и жизненным циклом
  `execute → beforeRender → render`; регистрируются в `ComponentCollection`:

  ```html
  {% component vasoft:day %}
  ```

- **Подключение ресурсов, CSRF и отложенный вывод.** Директивы связаны с `PageBuilder`, `CsrfMiddleware` и
  `DeferService` фреймворка:

  ```html
  {% include css /assets/style.css head %}
  {% csrf %}
  <title>{% defer page.title %}</title>
  ```

- **Собственные директивы.** Обработчик реализует `NodeHandlerInterface` (`compile()` и `render()`) и регистрируется в
  конфигурации:

  ```php
  $config->directiveCollection->upsert(StatementToken::class, 'custom', '/custom');
  $config->addDirectiveHandler('custom', CustomHandler::class);
  ```

- **Кэширование.** Скомпилированные шаблоны хранятся в `{cache}/templator/` и пересобираются при изменении исходного
  файла или истечении TTL.

## Документация

Полная документация доступна в каталоге [doc/](doc/).

## Лицензия

MIT. См. [LICENSE.md](LICENSE.md).
