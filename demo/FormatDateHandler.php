<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Demo;

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

    public function compile(
        NodeInterface $node,
        NodeProcessorInterface $processor,
        array $context,
        array $localVars = [],
    ): string {
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
