<?php

namespace Vasoft\Joke\Templator\Lexer;

use Vasoft\Joke\Templator\Contracts\LexerInterface;
use Vasoft\Joke\Templator\TemplatorConfig;

class DefaultLexer implements LexerInterface
{
    /**
     * @var list<TokenDescriptor>
     */
    private array $tokenDescriptors = [];

    public function __construct(TemplatorConfig $config)
    {
        $this->tokenDescriptors = $config->tokenCollection->list();
    }

    public function tokenize(string $template): array
    {
        $tokens = [];
        $templateLength = strlen($template);
        $pos = 0;
        while ($pos < $templateLength) {
            /**
             * @var TokenDescriptor $firstDescriptor
             * @var int $nextMarker
             */
            [$firstDescriptor, $nextMarker] = $this->findNext($template, $pos);

            if ($pos !== $nextMarker) {
                if ($nextMarker === INF) {
                    $tokens[] = new TextToken(substr($template, $pos));
                    break;
                }
                $tokens[] = new TextToken(substr($template, $pos, $nextMarker - $pos));
            }
            $pos = $nextMarker;
            if ($firstDescriptor !== null) {
                $end = strpos($template, $firstDescriptor->close, $pos + $firstDescriptor->openLength);
                if ($end === false) {
                    throw new \Exception();
                }
                $content = substr(
                    $template,
                    $pos + $firstDescriptor->openLength,
                    $end - $pos - $firstDescriptor->closeLength
                );
                $pos = $end + $firstDescriptor->closeLength;
                $tokens[] = new ($firstDescriptor->tokenClass)($content);
            }
        }
        return $tokens;
    }

    /**
     * @param string $template
     * @param $pos
     * @return array
     * @todo оценить производительность: многократный strpos против посимвольного чтения со сравнением
     */
    private function findNext(
        string $template,
        $pos
    ): array {
        $minimal = INF;
        $firstDescriptor = null;
        foreach ($this->tokenDescriptors as $descriptor) {
            $position = strpos($template, $descriptor->open, $pos);
            if ($position !== false && $position < $minimal) {
                $minimal = $position;
                $firstDescriptor = $descriptor;
            }
        }
        return [$firstDescriptor, $minimal];
    }
}