<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Handler\Directive;

use Vasoft\Joke\Templator\Contracts\NodeProcessorInterface;
use Vasoft\Joke\Templator\Contracts\Parser\NodeInterface;
use Vasoft\Joke\Templator\Exceptions\CompileException;
use Vasoft\Joke\Templator\Exceptions\RenderingException;
use Vasoft\Joke\Templator\Exceptions\RequiredParameterException;
use Vasoft\Joke\Templator\Exceptions\TemplatorException;
use Vasoft\Joke\Templator\Handler\NodeHandler;
use Vasoft\Joke\Templator\Parser\Node\BlockNode;
use Vasoft\Joke\Templator\TemplateEngine;
use Vasoft\Joke\Templator\TemplatorConfig;

/**
 *  Обработчик директивы {%layout name%}.
 *
 *  Реализует механизм наследования каркасов.
 *  Вместо прямого включения каркаса в текущий шаблон, генерирует PHP-код,
 *  который:
 *  1. Захватывает вывод внутреннего блока через output buffering.
 *  2. Формирует контекст для каркаса с ключом $__layout['content'].
 *  3. Делегирует подключение файла каркаса TemplateEngine для сохранения
 *     единого механизма изоляции переменных и обработки ошибок.
 *
 *  В файле каркаса содержимое страницы выводится через директиву:
 *    {%raw __layout.content%}
 *
 * @note Директива работает только в режиме компиляции.
 *        Метод render() временно выбрасывает RenderingException;
 *        поддержка интерпретируемого режима планируется в будущих версиях.
 *
 * @see BlockNode      Ожидаемый тип узла AST
 * @see TemplateEngine::includeFile() Метод подключения каркаса
 */
class LayoutHandler extends NodeHandler
{
    /**
     * @param TemplateEngine $engine Движок шаблонов
     */
    public function __construct(
        private readonly TemplateEngine $engine,
    ) {}

    /**
     * Компилирует директиву layout в PHP-код с захватом вывода.
     *
     * Генерируемый код использует ob_start()/ob_get_clean() для получения
     * результата рендеринга дочерних узлов как строки, которая затем
     * передаётся в каркас через $__layoutContext['__layout']['content'].
     *
     * @param BlockNode              $node      Узел блока {%layout name%}...{%/layout%}
     * @param NodeProcessorInterface $processor Процессор для компиляции дочерних узлов
     * @param array<string, mixed>   $context   Контекст переменных шаблона (не используется напрямую,
     *                                          но передаётся процессору для дочерних узлов)
     * @param list<string>           $localVars Локальные переменные цикла/блока
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
        if (!$node instanceof BlockNode) {
            throw new CompileException($this->getErrorMessage('BlockNode', $node));
        }
        $layoutName = trim($node->arguments);
        if (empty($layoutName)) {
            throw new RequiredParameterException('layout', 'layoutName');
        }
        $filename = $this->engine->getLayoutPath($layoutName);
        $dir = dirname($filename);
        $layoutCss = $dir . '/' . $layoutName . '.css';
        $layoutJs = $dir . '/' . $layoutName . '.js';


        $innerPhpCode = $processor->process($node->children, $context, $localVars);

        return <<<PHP
                <?php
                ob_start();
                ?>
                {$innerPhpCode}
                <?php
                 \$__content = ob_get_clean();
                \$__layoutContext = ['__layout' => ['content' => \$__content, 'css' => '{$layoutCss}', 'js' => '{$layoutJs}']];
                \$engine->includeFile('{$filename}',\$__layoutContext, \$config->defaultTtl);
                ?>
            PHP;
    }

    /**
     * Рендеринг директивы layout в интерпретируемом режиме не поддерживается.
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
