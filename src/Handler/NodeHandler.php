<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Handler;

use Vasoft\Joke\Templator\Contracts\Handler\NodeHandlerInterface;

abstract class NodeHandler implements NodeHandlerInterface
{
    protected function toPhpArrayAccess(string $path): string
    {
        $keys = explode('.', $path);
        $code = '$context';
        foreach ($keys as $key) {
            $code .= "['" . addslashes($key) . "']";
        }

        return $code;
    }

    protected function resolveValue(array $context, string $path, mixed $defaultValue): mixed
    {
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
