<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Exceptions;

/**
 * Исключение, возникающее на этапе лексического анализа.
 *
 * Выбрасывается, если исходный код шаблона содержит некорректные символы,
 * незавершенные конструкции или нарушения базового синтаксиса токенизации.
 */
class LexerException extends TemplatorException {}
