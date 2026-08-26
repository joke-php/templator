# Создание собственного обработчика узлов

## Назначение

[Создание собственной директивы](01-custom-directive.md) добавляет **новую директиву** в рамках уже существующих типов
узлов
AST (`StatementNode`/`BlockNode`) — это безопасный, полностью конфигурируемый путь. Этот документ — на уровень глубже:
про регистрацию обработчика для **другого класса узла**, включая узлы, которых в стандартной поставке нет. Здесь важна
честность в терминах: интерфейс шаблонизатора не даёт метода `match()` или подобного авто-обнаружения — выбор
обработчика по типу узла в этом коде жёстко привязана к PHP-классу.

## Когда это необходимо

- Нужно изменить компиляцию **существующего** типа узла глобально — например, заменить экранирование в `{{ }}` (
  `PrintNode`) на кастомную функцию санитайзера вместо `htmlspecialchars()`.
- Нужен принципиально новый синтаксис, не укладывающийся в модель «токен → директива по имени» (`{% name args %}`), —
  потребует не только нового обработчика, но и замены парсера.
- Глубокая интеграция с внешним DSL, для которого директивы недостаточно выразительны.

## Архитектура обработки узлов

Реальный конвейер: **Лексер** (`DefaultLexer` + `TokenCollection`) режет строку шаблона на токены `TextToken`/
`PrintToken`/`StatementToken` (или ваш класс, реализующий `TokenInterface` — интерфейс минимален: `raw`, `line`,
`column`, и регистрируется чисто конфигурацией через
`tokenCollection->upsert(new TokenDescriptor($open, $close, $tokenClass))`, без правок лексера). Дальше 
**`DefaultParser::parse()`** превращает токены в узлы AST — и вот здесь начинается важная деталь: метод жёстко перебирает
три случая через `instanceof` (`TextToken`, `PrintToken`, `StatementToken`) и не имеет ни `else`-ветки, ни точки
расширения для незнакомого класса токена — токен неизвестного типа будет молча проигнорирован, а не выброшен как ошибка.
Наконец, **`AbstractNodeProcessor::processNode()`** выбирает обработчик строго по `get_class($node)` через карту,
которую строит `TemplatorConfig::addNodeHandler(class-string $nodeClass, class-string $handler)` — никакого `match()`,
только точное совпадение класса.

Практический вывод: **добавить обработчик для существующего класса узла** (`TextNode`, `PrintNode`, `BlockNode`,
`StatementNode`) — конфигурация, ничего не ломает. **Добавить обработчик для нового класса узла** — бессмысленно само по
себе, пока такой узел вообще не появляется в AST, а появиться он может, только если вы замените реализацию
`ParserInterface` целиком (стандартная привязка `ParserInterface::class → DefaultParser::class` регистрируется в
`TemplatorProvider::register()` и может быть переопределена вашим провайдером).

## Пошаговое создание обработчика

### Шаг 1: Реализация обработчика для существующего узла

Простой и безопасный случай — замена обработчика `PrintNode` на версию с собственным экранированием:

```php
<?php

declare(strict_types=1);

namespace App\Templator\Handler;

use Vasoft\Joke\Templator\Contracts\NodeProcessorInterface;
use Vasoft\Joke\Templator\Contracts\Parser\NodeInterface;
use Vasoft\Joke\Templator\Exceptions\CompileException;
use Vasoft\Joke\Templator\Handler\NodeHandler;
use Vasoft\Joke\Templator\Parser\Node\PrintNode;

class SanitizingPrintNodeHandler extends NodeHandler
{
    public function compile(NodeInterface $node, NodeProcessorInterface $processor, array $context, array $localVars = []): string
    {
        if (!$node instanceof PrintNode) {
            throw new CompileException($this->getErrorMessage('PrintNode', $node));
        }
        $code = $this->compileVarAccess($node->content, $localVars);

        return "<?= App\\Support\\Sanitizer::clean((string)({$code})); ?>";
    }

    public function render(NodeInterface $node, NodeProcessorInterface $processor, array $context): string
    {
        return '';
    }
}
```

Здесь нет никакого `match()` — весь «матчинг» уже произошёл на уровне
`TemplatorConfig::addNodeHandler(PrintNode::class, ...)`: этот класс вызывается для абсолютно всех `{{ }}` в шаблоне.

### Шаг 2: Регистрация обработчика

```php
$config = $container->get(TemplatorConfig::class);
$config->addNodeHandler(PrintNode::class, SanitizingPrintNodeHandler::class); // перекрывает стандартный PrintNodeHandler
```

### Шаг 3: Использование

Синтаксис шаблона не меняется — `{{ name }}` будет компилироваться уже через новый обработчик без каких-либо изменений в
самих шаблонах.

## Пример: добавление нового синтаксиса

Для делимитеров вида `[? ... ?]`, которые ведут себя **как обычная директива** (то есть первое слово — имя, остальное —
аргументы), новый *класс узла* не нужен вовсе: достаточно зарегистрировать альтернативный `TokenDescriptor` с тем же
`tokenClass = StatementToken::class` — парсер и весь механизм директив продолжат работать без изменений:

```php
$config->tokenCollection->upsert(new TokenDescriptor('[?', '?]', StatementToken::class));
```

Если же нужен узел с принципиально иной семантикой (не «имя + аргументы»), придётся реализовать собственный
`ParserInterface` (например, унаследовав `DefaultParser` и переопределив `parse()`, добавив обработку своего
`TokenInterface`), зарегистрировать его через `$container->registerSingleton(ParserInterface::class, MyParser::class)` в
собственном провайдере, и уже для нового класса узла вызвать `addNodeHandler()`.

## Предостережения и лучшие практики

- **Отладка сложнее**, чем у директив: ошибка в парсере проявляется как «пропавший» кусок шаблона (токен неизвестного
  класса молча отбрасывается), а не как понятное исключение.
- **Риск конфликтов** — переопределение обработчика встроенного узла (`PrintNode`, `TextNode`) действует глобально, на
  все шаблоны сразу; тестируйте на всём наборе демо-шаблонов, а не на одном примере.
- **Юнит-тесты обязательны** — как минимум по одному тесту на `compile()` и на пограничные случаи (пустой контекст,
  вложенные узлы).

## Что дальше

[Безопасность шаблонов](../11-security.md)
