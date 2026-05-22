<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Handler;

use Vasoft\Joke\Templator\Contracts\Handler\NodeHandlerInterface;
use Vasoft\Joke\Templator\Contracts\Parser\NodeInterface;
use Vasoft\Joke\Templator\Exceptions\RenderingException;

/**
 * Абстрактный базовый класс для обработчиков узлов.
 *
 * Предоставляет общие вспомогательные методы, используемые большинством хендлеров
 * для работы с контекстом данных и генерации кода доступа к переменным.
 * Упрощает реализацию конкретных директив, инкапсулируя логику парсинга путей
 * к данным (например, 'user.profile.name').
 */
abstract class NodeHandler implements NodeHandlerInterface
{
    /**
     * Генерирует PHP-код для доступа к переменной.
     *
     * Если переменная является локальной (находится в $localVars), возвращает
     * прямое обращение к переменной (например, '$item').
     * В противном случае генерирует обращение через массив контекста
     * (например, "$context['user']['name']").
     *
     * @param string       $path      Точечный путь к данным (например, 'config.settings.debug').
     * @param list<string> $localVars список локальных переменных текущей области видимости
     *
     * @return string строка кода PHP для доступа к значению в массиве $context
     */
    protected function compileVarAccess(string $path, array $localVars): string
    {
        if (in_array($path, $localVars, true)) {
            return '$' . $path;
        }
        $keys = explode('.', $path);
        $code = '$context';
        foreach ($keys as $key) {
            $code .= "['" . addslashes($key) . "']";
        }

        return $code;
    }

    /**
     * Извлекает значение из контекста по точечному пути.
     *
     * Рекурсивно проходит по массиву контекста, проверяя наличие каждого ключа.
     * Если какой-либо ключ отсутствует или значение не является массивом,
     * возвращает значение по умолчанию.
     *
     * @param array<string, mixed> $context      ассоциативный массив данных шаблона
     * @param string               $path         точечный путь к искомому значению
     * @param mixed                $defaultValue значение, возвращаемое в случае, если путь не найден
     *
     * @return mixed найденное значение или $defaultValue
     *
     * @throws RenderingException Если не передано имя переменной
     */
    protected function resolveValue(array $context, string $path, mixed $defaultValue, string $directive): mixed
    {
        if ('' === trim($path)) {
            throw new RenderingException("Directive '{$directive}' with no arguments.");
        }

        $value = $context;
        foreach (explode('.', $path) as $key) {
            if (is_array($value) && array_key_exists($key, $value)) {
                $value = $value[$key];
            } else {
                return $defaultValue;
            }
        }

        return $value;
    }

    /**
     * Формирует текст ошибки о несоответствующем типе ноды.
     *
     * @param string        $expected Ожидаемый тип
     * @param NodeInterface $node     Полученный тип
     *
     * @return string текст ошибки
     */
    protected function getErrorMessage(string $expected, NodeInterface $node): string
    {
        return sprintf('Expected instance of %s, got %s.', $expected, $node::class);
    }
}
