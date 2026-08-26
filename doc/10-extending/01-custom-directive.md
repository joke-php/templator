# Создание собственной директивы

## Назначение

Встроенных директив (`if`, `foreach`, `component` и т.д.) достаточно для базовой логики шаблона, но не для специфичных
для приложения вещей: форматирования по бизнес-правилам, проверки прав доступа, интеграции со сторонним сервисом
форматирования. Вместо того чтобы городить это в PHP-коде компонента, можно добавить собственную директиву — она
получает тот же уровень интеграции с движком, что и встроенные.

## Архитектура расширения

Обработчик директивы — обычный класс, реализующий `NodeHandlerInterface` с двумя обязательными методами:

- **`compile(NodeInterface $node, NodeProcessorInterface $processor, array $context, array $localVars = []): string`** —
  вызывается при компиляции шаблона в PHP-код (основной, реально используемый режим). Возвращает фрагмент PHP, который
  попадёт в скомпилированный файл.
- **`render(NodeInterface $node, NodeProcessorInterface $processor, array $context): string`** — режим интерпретации «на
  лету», без промежуточного файла. Если он вам не нужен (движок в `TemplateEngine` его не использует —
  см. [Основы синтаксиса](../03-syntax-basics.md)), просто бросьте `RenderingException('Not implemented yet.')`, как
  это делают `layout`/`component`/`include`/`defer`.

Отдельного метода `parse()` в интерфейсе нет — аргументы директивы приходят как сырая строка (`$node->arguments`), и
разбор этой строки — целиком забота обработчика (через `explode()`, `preg_match()` и т.п., как это делают встроенные
`EachHandler`/`IncludeHandler`). Управление обработчику передаёт диспетчер узла (`StatementNodeHandler` — для одиночных
директив без содержимого, `BlockNodeHandler` — для директив с закрывающим тегом и вложенным контентом): он по имени
директивы находит нужный класс через `TemplatorConfig::getDirectiveHandler()` и вызывает у него `compile()`/`render()`.

Удобно наследовать не сам интерфейс, а абстрактный `NodeHandler` — он уже даёт `compileVarAccess()` (доступ к переменной
по точечному пути) и `resolveValue()` (то же самое для режима интерпретации).

## Пошаговое создание директивы

### Шаг 1: Создание класса обработчика

Одиночная директива (без закрывающего тега) — на примере вывода текущей даты:

```php
<?php

declare(strict_types=1);

namespace App\Templator\Handler;

use Vasoft\Joke\Templator\Contracts\NodeProcessorInterface;
use Vasoft\Joke\Templator\Contracts\Parser\NodeInterface;
use Vasoft\Joke\Templator\Exceptions\CompileException;
use Vasoft\Joke\Templator\Exceptions\RequiredParameterException;
use Vasoft\Joke\Templator\Handler\NodeHandler;
use Vasoft\Joke\Templator\Parser\Node\StatementNode;
use Vasoft\Joke\Templator\TemplatorConfig;

class FormatDateHandler extends NodeHandler
{
    public function __construct(private readonly TemplatorConfig $config) {}

    public function compile(NodeInterface $node, NodeProcessorInterface $processor, array $context, array $localVars = []): string
    {
        if (!$node instanceof StatementNode) {
            throw new CompileException($this->getErrorMessage('StatementNode', $node));
        }
        [$path, $format] = array_pad(explode(' ', trim($node->arguments), 2), 2, '');
        if ('' === $path || '' === $format) {
            throw new RequiredParameterException('format_date', 'variable/format');
        }
        $code = $this->compileVarAccess($path, $localVars);
        $format = trim($format, "'\"");

        return "<?= htmlspecialchars(date('{$format}', strtotime((string)({$code}))), ENT_QUOTES, '{$this->config->encoding}'); ?>";
    }

    public function render(NodeInterface $node, NodeProcessorInterface $processor, array $context): string
    {
        // при необходимости — та же логика через resolveValue(); иначе можно бросить RenderingException
        return '';
    }
}
```

`compile()` разбирает сырую строку аргументов на путь к переменной и строковый литерал формата (`compileVarAccess()`
понимает только точечные пути к контексту, поэтому формат разбирается вручную), после чего генерирует PHP-код с `date()`
и экранированием.

### Шаг 2: Регистрация директивы

```php
use Vasoft\Joke\Templator\TemplatorConfig;
use Vasoft\Joke\Templator\Lexer\StatementToken;

$config = $container->get(TemplatorConfig::class);
$config->directiveCollection->upsert(StatementToken::class, 'format_date'); // без directiveEnd — одиночная директива
$config->addDirectiveHandler('format_date', FormatDateHandler::class);
```

Регистрацию удобно вынести в `boot()` собственного провайдера (см. [Конфигурирование шаблонизатора](../09-configuration.md)).

### Шаг 3: Использование в шаблоне

```html
<p>Опубликовано: {% format_date article.publishedAt 'Y-m-d' %}</p>
```

## Примеры пользовательских директив

### Пример 1: форматирование даты

Класс и регистрация — выше; результат: `Опубликовано: 2026-08-21`.

### Пример 2: проверка прав доступа

Блочная директива — с закрывающим тегом, содержимое рендерится только при наличии права:

```php
class CanHandler extends NodeHandler
{
    public function __construct(private readonly ServiceContainer $container) {}

    public function compile(NodeInterface $node, NodeProcessorInterface $processor, array $context, array $localVars = []): string
    {
        if (!$node instanceof BlockNode) {
            throw new CompileException($this->getErrorMessage('BlockNode', $node));
        }
        $permission = trim($node->arguments, "'\" ");
        $inner = $processor->process($node->children, $context, $localVars);

        return "<?php if (\$container->get(AuthChecker::class)->can('{$permission}')): ?>{$inner}<?php endif; ?>";
    }

    public function render(NodeInterface $node, NodeProcessorInterface $processor, array $context): string
    {
        throw new RenderingException('Not implemented yet.');
    }
}
```

Регистрация — с закрывающим тегом:

```php
$config->directiveCollection->upsert(StatementToken::class, 'can', '/can');
$config->addDirectiveHandler('can', CanHandler::class);
```

```html
{% can 'edit-post' %}
<a href="/posts/{{ post.id }}/edit">Редактировать</a>
{% /can %}
```

## Лучшие практики

- **Именование** — избегайте односложных общеупотребимых имён (`if`, `each`, `set`), которые могут появиться во
  встроенном наборе в будущих версиях; префикс проекта (`app_can`, `acme_format_date`) снижает риск конфликта.
- **Валидация аргументов** — бросайте `CompileException`/`RequiredParameterException` на этапе `compile()`, а не
  позволяйте некорректному коду попасть в скомпилированный файл и упасть непонятно где во время выполнения.
- **Производительность** — сложные вычисления (запрос к БД, тяжёлый парсинг) не должны выполняться в `compile()`: он
  вызывается один раз при генерации кэша, но сгенерированный код должен сам по себе оставаться дешёвым — сложную
  логику (как `AuthChecker::can()` выше) лучше держать в отдельном сервисе, вызываемом уже во время рендеринга страницы,
  а не «на лету» на этапе компиляции шаблона.

## Что дальше

[Обработчик для нового типа узла AST](02-custom-node-handler.md)
