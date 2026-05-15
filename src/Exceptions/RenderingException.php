<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Exceptions;

/**
 * Исключение, возникающее на этапе рендеринга шаблона.
 *
 * Выбрасывается при ошибках выполнения логики шаблона, которые невозможно
 * обнаружить на этапе компиляции или парсинга.
 */
class RenderingException extends TemplatorException {}
