<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Component;

use Vasoft\Joke\Templator\Contracts\NodeProcessorInterface;
use Vasoft\Joke\Templator\Contracts\Parser\NodeInterface;
use Vasoft\Joke\Templator\Exceptions\CompileException;
use Vasoft\Joke\Templator\Exceptions\RenderingException;
use Vasoft\Joke\Templator\Exceptions\RequiredParameterException;
use Vasoft\Joke\Templator\Exceptions\TemplatorException;
use Vasoft\Joke\Templator\Handler\NodeHandler;
use Vasoft\Joke\Templator\Parser\Node\BlockNode;
use Vasoft\Joke\Templator\Parser\Node\StatementNode;
use Vasoft\Joke\Templator\TemplateEngine;

/**
 *  Обработчик директивы {%component name template options%}.
 *
 *  Реализует механизм подключения компонентов.
 *
 * @note Директива работает только в режиме компиляции.
 *        Метод render() временно выбрасывает RenderingException;
 *        поддержка интерпретируемого режима планируется в будущих версиях.
 *
 * @see BlockNode      Ожидаемый тип узла AST
 * @see TemplateEngine::includeFile() Метод подключения каркаса
 */
class ComponentHandler extends NodeHandler
{
    public function __construct() {}

    /**
     * Компилирует директиву component в PHP-код.
     *
     * @param StatementNode          $node      Узел директивы
     * @param NodeProcessorInterface $processor Процессор для компиляции дочерних узлов (не используется)
     * @param array<string, mixed>   $context   Контекст переменных шаблона (не используется)
     * @param list<string>           $localVars Локальные переменные цикла/блока (не используется)
     *
     * @return string Сгенерированный PHP-код с буферизацией и подключением каркаса
     *
     * @throws CompileException           Если передан узел неверного типа или файл каркаса не найден
     * @throws RequiredParameterException Если не передано имя каркаса
     * @throws TemplatorException         Если отсутствует файл каркаса
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
        [$componentName, $template, $options] = $this->parseArguments($node->arguments);

        $result = "<?php \$component = \$components->get('{$componentName}');";
        if ('' !== $template) {
            $result .= "\$component->setTemplateName('{$template}');";
        }
        $optionsCode = '' === $options
            ? 'null'
            : $this->compileVarAccess($options, []) . '??null';

        return <<<PHP
                {$result}
                \$component->setOptions({$optionsCode})->compile(\$engine, \$response);
                ?>
            PHP;
    }

    /**
     * Разбирает строку аргументов компонента на составные части.
     *
     * Ожидает формат: «componentName [templateName [templateFile]]»
     * Аргументы разделяются одним или более пробельными символами.
     * Пустые элементы (от последовательных пробелов) игнорируются.
     *
     * @param string $arguments Строка аргументов из тега компонента
     *
     * @return array{0: non-empty-string, 1: string, 2: string}
     *                                                          [0] — имя компонента (обязательный, non-empty-string)
     *                                                          [1] — имя шаблона (необязательный, '' по умолчанию)
     *                                                          [2] — имя файла шаблона (необязательный, '' по умолчанию)
     *
     * @throws RequiredParameterException Если строка аргументов пуста или не содержит имя компонента
     */
    private function parseArguments(string $arguments): array
    {
        $args = preg_split('/\s+/', trim($arguments), -1, PREG_SPLIT_NO_EMPTY);
        if (false === $args || count($args) < 1) {
            throw new RequiredParameterException('component', 'componentName');
        }

        return [
            $args[0],
            $args[1] ?? '',
            $args[2] ?? '',
        ];
    }

    /**
     * Рендеринг директивы component в интерпретируемом режиме не поддерживается.
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
