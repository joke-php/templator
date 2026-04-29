<?php

namespace Vasoft\Joke\Templator\Compiler\Tag;

use Vasoft\Joke\Templator\Ast\TagNode;
use Vasoft\Joke\Templator\Contracts\Core\Compiler\CompilerInterface;
use Vasoft\Joke\Templator\Contracts\Core\Compiler\TagCompilerInterface;

class EchoCompiler implements TagCompilerInterface
{

    /**
     * @inheritDoc
     */
    public function getTagName(): string
    {
        return 'echo';
    }

    /**
     * @inheritDoc
     */
    public function compile(TagNode $node, CompilerInterface $compiler): string
    {
        if (!isset($node->attributes['value'])) {
            throw new \Exception("Missing 'value' attribute in <{$node->fullTagName}>");
        }
        $path = $this->generateArrayAccess($node->attributes['value']);

        $code = ($node->attributes['escaped'] ?? false) ? "htmlspecialchars((string)$path, ENT_QUOTES, 'UTF-8')" : "$path";
        return "<?php echo " . $code . ";?>";
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
}