<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Tests\Render;

use Vasoft\Joke\Templator\Render\DefaultRenderer;
use Vasoft\Joke\Templator\Tests\ProcessorTestBase;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Templator\Render\DefaultRenderer
 */
final class DefaultRendererTest extends ProcessorTestBase
{
    public function testRender(): void
    {
        $expected = <<<'text'
            testVariableValue
            single text
            branch1
            0:a
            1:b

            text;

        $renderer = new DefaultRenderer($this->container, $this->config);
        self::assertSame($expected, $renderer->process($this->getDefaultAst(), $this->getDefaultContext()));
    }
}
