<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Handler;

use Vasoft\Joke\Templator\Contracts\Handler\NodeHandlerInterface;
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
     * Преобразует путь к переменной в синтаксис доступа к элементам массива PHP.
     *
     * Используется при компиляции шаблонов для генерации PHP-кода, обращающегося
     * к массиву $context. Например, путь 'user.name' превращается в "$context['user']['name']".
     *
     * @param string $path Точечный путь к данным (например, 'config.settings.debug').
     *
     * @return string строка кода PHP для доступа к значению в массиве $context
     */
    protected function toPhpArrayAccess(string $path): string
    {
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
            throw new RenderingException("Directive {$directive} with no arguments.");
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
}
