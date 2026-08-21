<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Handler\Statement;

use Vasoft\Joke\Templator\Contracts\NodeProcessorInterface;
use Vasoft\Joke\Templator\Contracts\Parser\NodeInterface;
use Vasoft\Joke\Templator\Exceptions\CompileException;
use Vasoft\Joke\Templator\Exceptions\RenderingException;
use Vasoft\Joke\Templator\Exceptions\RequiredParameterException;
use Vasoft\Joke\Templator\Handler\NodeHandler;
use Vasoft\Joke\Templator\Parser\Node\StatementNode;
use Vasoft\Joke\Templator\TemplateEngine;
use Vasoft\Joke\Templator\Container\DeferService;

/**
 * Обработчик директивы {% defer varName [raw] %}.
 *
 * Компилирует директиву отложенного вывода в вызов DeferService::register()
 * или DeferService::registerRaw(). Значение вычисляется в момент встречи тега,
 * но может быть перезаписано позже (например, из TemplatedResponse::show()
 * после завершения рендеринга всех компонентов).
 *
 * Синтаксис:
 * - {% defer page.title %} — экранированный вывод (htmlspecialchars при flush)
 * - {% defer page.content raw %} — вывод без экранирования (ответственность на разработчике)
 *
 * @see StatementNode Ожидаемый тип узла AST
 * @see DeferService Сервис хранения и разрешения плейсхолдеров
 */
class DeferHandler extends NodeHandler
{
    public function __construct(public readonly TemplateEngine $engine) {}

    /**
     * Компилирует директиву defer в PHP-код регистрации плейсхолдера.
     *
     * При наличии флага `raw` используется registerRaw() вместо register().
     * Значение переменной вычисляется немедленно при встрече тега.
     * Финальное значение определяется последней регистрацией с данным ключом.
     *
     * @param StatementNode          $node      Узел выражения {% defer var.name [raw] %}
     * @param NodeProcessorInterface $processor Процессор узлов (не используется,
     *                                          но требуется интерфейсом)
     * @param array<string, mixed>   $context   Контекст переменных шаблона
     * @param list<string>           $localVars Локальные переменные цикла/блока
     *
     * @return string PHP-код, выводящий плейсхолдер через DeferService
     *
     * @throws CompileException           Если передан узел неверного типа
     * @throws RequiredParameterException Если не передано имя переменной
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
        $parts = explode(' ', trim($node->arguments), 2);

        $path = $parts[0];
        if (empty($path)) {
            throw new RequiredParameterException('defer', 'varName');
        }
        $function = isset($parts[1]) && 'raw' === $parts[1] ? 'registerRaw' : 'register';

        $code = $this->compileVarAccess($path, $localVars);

        return "<?= \$response->getDeferService()->{$function}('{$path}',(string)({$code}??'')); ?>";
    }

    /**
     * Рендеринг директивы defer в интерпретируемом режиме не поддерживается.
     *
     * Директива работает только через компиляцию в PHP-код.
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
