<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Handler\Statement;

use Vasoft\Joke\Templator\Contracts\NodeProcessorInterface;
use Vasoft\Joke\Templator\Contracts\Parser\NodeInterface;
use Vasoft\Joke\Templator\Exceptions\CompileException;
use Vasoft\Joke\Templator\Exceptions\RenderingException;
use Vasoft\Joke\Templator\Exceptions\RequiredParameterException;
use Vasoft\Joke\Templator\Handler\NodeHandler;
use Vasoft\Joke\Templator\Parser\Node\BlockNode;
use Vasoft\Joke\Templator\Parser\Node\StatementNode;
use Vasoft\Joke\Templator\Component\AssetFileManager;

/**
 * Обработчик директивы {%include type filename [position]%}.
 *
 * Реализует подключение внешних ресурсов (CSS/JS) к странице через объект ответа.
 * Вместо прямого включения содержимого файла в шаблон, генерирует PHP-код,
 * который регистрирует ресурс в сборщике ответа ($response->builder).
 *
 * Синтаксис:
 * - type (обязательный): тип ресурса. Поддерживаемые значения:
 *   - 'css' — генерирует тег <link> для стилей.
 *   - 'js' — генерирует тег <script> для скриптов.
 * - filename (обязательный): путь к файлу или переменная шаблона, содержащая путь.
 *   Правила формирования пути аналогичны {@see AssetFileManager}.
 * - position (необязательный, по умолчанию 'body'): место размещения тега в HTML:
 *   - 'body' — в конце тела страницы (перед закрывающим тегом </body>).
 *   - 'head' — в заголовке страницы (внутри тега <head>).
 *
 * @note Директива работает только в режиме компиляции.
 *       Метод render() выбрасывает RenderingException, так как интерпретируемый режим
 *       для данной директивы не реализован.
 *
 * @see StatementNode      Ожидаемый тип узла AST
 * @see NodeHandler        Базовый класс обработчиков узлов
 */
class IncludeHandler extends NodeHandler
{
    /**
     * Компилирует директиву include в PHP-код для регистрации подключаемого файла.
     *
     * Генерирует код вида:
     * <?php $response->builder->{type}->{function}({filename}); ?>
     *
     * @param BlockNode              $node      Узел блока (ожидается StatementNode)
     * @param NodeProcessorInterface $processor Процессор для компиляции дочерних узлов
     * @param array<string, mixed>   $context   Контекст переменных шаблона
     * @param list<string>           $localVars Локальные переменные текущей области видимости
     *
     * @return string Сгенерированный PHP-код
     *
     * @throws CompileException           Если передан узел неверного типа
     * @throws RequiredParameterException Если отсутствует тип ресурса или имя файла
     */
    public function compile(
        NodeInterface $node,
        NodeProcessorInterface $processor,
        array $context,
        array $localVars = [],
    ): string {
        // @phpstan-ignore instanceof.alwaysTrue
        if (!$node instanceof StatementNode) {
            throw new CompileException($this->getErrorMessage('StatementNode', $node));
        }
        $parts = explode(' ', trim($node->arguments), 3);
        $type = $parts[0];
        if (empty($type) || ('css' !== $type && 'js' !== $type)) {
            throw new RequiredParameterException('include', 'type');
        }
        $filename = $parts[1] ?? '';
        if (empty($filename)) {
            throw new RequiredParameterException('include', 'filename');
        }
        $filename = $this->compileVarAccess($filename, $localVars);

        $position = $parts[2] ?? 'body';
        $function = 'head' === $position ? 'addToHead' : 'addToBody';

        return "<?php \$response->builder->{$type}->{$function}({$filename}); ?>";
    }

    /**
     * Рендеринг директивы include в интерпретируемом режиме не поддерживается.
     *
     * @param NodeInterface          $node      Узел блока
     * @param NodeProcessorInterface $processor Процессор узлов
     * @param array<string, mixed>   $context   Контекст переменных
     *
     * @throws RenderingException Всегда, так как метод не реализован
     */
    public function render(
        NodeInterface $node,
        NodeProcessorInterface $processor,
        array $context,
    ): never {
        throw new RenderingException('Not implemented yet.');
    }
}
