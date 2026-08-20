<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Tests\Exceptions;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestDox;
use Vasoft\Joke\Templator\Exceptions\RequiredParameterException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Templator\Exceptions\RequiredParameterException
 */
#[CoversClass(RequiredParameterException::class)]
#[TestDox('RequiredParameterException - Исключение, возникающее если не передан или имеет пустое значение обязательный параметр директивы.')]
final class RequiredParameterExceptionTest extends TestCase
{
    #[TestDox('Форматирует сообщение')]
    public function testRequiredParameterException(): void
    {
        self::expectExceptionMessageIs('Required parameter "componentName" for directive "component" is missing.');

        throw new RequiredParameterException('component', 'componentName');
    }
}
