<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Exceptions;

/**
 * Исключение, возникающее на этапе компиляции AST в PHP-код.
 *
 * Выбрасывается, если обработчики узлов (Handlers) обнаруживают логические ошибки
 * или невозможность преобразования конструкции шаблона в валидный PHP-код.
 */
class CompileException extends TemplatorException {}
