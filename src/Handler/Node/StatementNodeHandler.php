<?php

namespace Vasoft\Joke\Templator\Handler\Node;

use Vasoft\Joke\Templator\Contracts\Parser\NodeInterface;
use Vasoft\Joke\Templator\Contracts\Compiler\CompilerInterface;
use Vasoft\Joke\Templator\Contracts\Handler\NodeHandlerInterface;
use Vasoft\Joke\Templator\Parser\Node\BlockNode;

class StatementNodeHandler implements NodeHandlerInterface
{
    public function compile(NodeInterface $node, CompilerInterface $compiler, array $context, array $localVars = []): string
    {
        assert($node instanceof BlockNode);
        if ($node->directive === 'else') {
            assert($node instanceof BlockNode);
            $result = "<?php else: ?>";
        }
        return $result;
    }

    public function render(NodeInterface $node, CompilerInterface $compiler, array $context): string
    {
        assert($node instanceof BlockNode);
        return '';//$this->getValue($node->content, $context);
    }

    private function generateArrayAccess(string $path): string
    {
        $keys = explode('.', $path);
        $code = '$context';
        foreach ($keys as $key) {
            $code .= "['" . addslashes($key) . "']";
        }
        return $code;
    }

    private function getValue(string $path, array $context): string
    {
        $keys = explode('.', $path);
        $current = $context;
        foreach ($keys as $key) {
            if (!array_key_exists($key, $current)) {
                return '';
            } elseif (is_array($current[$key])) {
                $current = $current[$key];
            } else {
                return (string)$current[$key];
            }
        }
        return empty($current) ? '' : "Array";
    }
}