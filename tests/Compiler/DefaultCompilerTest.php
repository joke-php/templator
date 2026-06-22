<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Tests\Compiler;

use Vasoft\Joke\Templator\Compiler\DefaultCompiler;
use Vasoft\Joke\Templator\Lexer\PrintToken;
use Vasoft\Joke\Templator\Lexer\TokenDescriptor;
use Vasoft\Joke\Templator\TemplatorConfig;
use Vasoft\Joke\Templator\Tests\ProcessorTestBase;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Templator\Compiler\DefaultCompiler
 */
final class DefaultCompilerTest extends ProcessorTestBase
{
    public function testCompile(): void
    {
        $expected = <<<'PHP'
            <?= htmlspecialchars((string)$context['testVariable'], ENT_QUOTES, 'UTF-8');?>
            single text
            <?php if((bool)($context['expression1'])): ?>branch1<?php elseif((bool)($context['expression2'])): ?>branch2<?php endif; ?>
            <?php foreach ($context['list'] as $id => $item): ?><?= htmlspecialchars((string)$id, ENT_QUOTES, 'UTF-8');?>:<?= htmlspecialchars((string)$item, ENT_QUOTES, 'UTF-8');?>
            <?php endforeach; ?>
            PHP;

        $renderer = new DefaultCompiler($this->container, $this->config);
        self::assertSame($expected, $renderer->process($this->getDefaultAst(), $this->getDefaultContext()));
    }

    public function testSameTags(): void
    {
        $config = new TemplatorConfig();
        $config->tokenCollection->upsert(new TokenDescriptor('{{', '}}', PrintToken::class));
    }
}
