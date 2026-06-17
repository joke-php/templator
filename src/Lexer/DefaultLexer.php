<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Lexer;

use Vasoft\Joke\Templator\Contracts\LexerInterface;
use Vasoft\Joke\Templator\Contracts\TokenInterface;
use Vasoft\Joke\Templator\Exceptions\LexerException;
use Vasoft\Joke\Templator\TemplatorConfig;

/**
 * Стандартный лексический анализатор шаблонов.
 *
 * Преобразует исходную строку шаблона в плоский список токенов (TextToken, PrintToken, StatementToken).
 * Использует жадный алгоритм поиска ближайшего открывающего маркера среди всех зарегистрированных дескрипторов.
 */
class DefaultLexer implements LexerInterface
{
    /**
     * Реестр дескрипторов токенов, используемых для поиска маркеров.
     * Инициализируется из конфигурации шаблонизатора.
     *
     * @var array<string, TokenDescriptor>
     */
    private array $tokenDescriptors = [];

    public function __construct(TemplatorConfig $config)
    {
        $this->tokenDescriptors = $config->tokenCollection->list();
    }

    /**
     * {@inheritDoc}
     *
     * Выполняет лексический анализ переданного шаблона.
     *
     * @param string $template исходная строка шаблона
     *
     * @return list<TokenInterface> массив объектов токенов, представляющих структуру шаблона
     *
     * @throws LexerException если обнаружен незакрытый тег (отсутствует закрывающий маркер)
     */
    public function tokenize(string $template): array
    {
        $tokens = [];
        $templateLength = strlen($template);
        $pos = 0;
        $currentLine = 1;
        $lastNewLinePos = -1;
        while ($pos < $templateLength) {
            /**
             * @var ?TokenDescriptor $firstDescriptor
             * @var int              $nextMarker
             */
            [$firstDescriptor, $nextMarker] = $this->findNext($template, $pos);

            if ($pos !== $nextMarker) {
                $tokenLine = $currentLine;
                $tokenCol = $pos - $lastNewLinePos;

                if (PHP_INT_MAX === $nextMarker) {
                    $tokens[] = new TextToken(substr($template, $pos), $tokenLine, $tokenCol);
                    break;
                }
                $text = substr($template, $pos, $nextMarker - $pos);
                $tokens[] = new TextToken($text, $tokenLine, $tokenCol);

                $newLinesCount = substr_count($text, "\n");
                if ($newLinesCount > 0) {
                    $currentLine += $newLinesCount;
                    $lastNewLinePos = $pos + strrpos($text, "\n");
                }
            }
            $pos = $nextMarker;
            if (null !== $firstDescriptor) {
                $tagLine = $currentLine;
                $tagCol = $pos - $lastNewLinePos;

                $end = strpos($template, $firstDescriptor->close, $pos + $firstDescriptor->openLength);
                if (false === $end) {
                    throw new LexerException(
                        sprintf(
                            'Unclosed tag "%s" found at position %d:%d.',
                            $firstDescriptor->open,
                            $tagLine,
                            $tagCol,
                        ),
                    );
                }
                $content = substr(
                    $template,
                    $pos + $firstDescriptor->openLength,
                    $end - $pos - $firstDescriptor->openLength,
                );

                $fullTagEndPos = $end + $firstDescriptor->closeLength;
                $fullTagContent = substr($template, $pos, $fullTagEndPos - $pos);

                $tokens[] = new ($firstDescriptor->tokenClass)($content, $tagLine, $tagCol);

                $newLinesInTag = substr_count($fullTagContent, "\n");
                if ($newLinesInTag > 0) {
                    $currentLine += $newLinesInTag;
                    $lastNewLinePos = $pos + strrpos($fullTagContent, "\n");
                }

                $pos = $fullTagEndPos;
            }
        }

        return $tokens;
    }

    /**
     * Находит позицию ближайшего открывающего маркера любого типа.
     *
     * Перебирает все зарегистрированные дескрипторы и возвращает тот, чей открывающий тег
     * встречается в шаблоне раньше остальных начиная с позиции $pos.
     *
     * @param string $template исходная строка шаблона
     * @param int    $pos      позиция, с которой начинается поиск
     *
     * @return array{?TokenDescriptor, int} массив, содержащий:
     *                                      [0] — объект TokenDescriptor найденного маркера (или null, если ничего не найдено),
     *                                      [1] — позиция начала маркера (или PHP_INT_MAX, если маркеров нет)
     *
     * @todo оценить производительность: многократный strpos против посимвольного чтения со сравнением
     */
    private function findNext(
        string $template,
        int $pos,
    ): array {
        $minimal = PHP_INT_MAX;
        $firstDescriptor = null;
        foreach ($this->tokenDescriptors as $descriptor) {
            $position = strpos($template, $descriptor->open, $pos);
            if (false !== $position && $position < $minimal) {
                $minimal = $position;
                $firstDescriptor = $descriptor;
            }
        }

        return [$firstDescriptor, $minimal];
    }
}
