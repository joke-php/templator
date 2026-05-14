<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

require_once __DIR__ . '/vendor/autoload.php';

$finder = Finder::create()
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);

return
    new Config()
        ->setFinder($finder)
        ->setCacheFile(__DIR__ . '/var/cache/.php-cs-fixer.cache')
        ->setRiskyAllowed(true)
        ->setRules([
            '@PHP8x0Migration:risky' => true,
            '@PHP8x1Migration' => true,
            '@PHPUnit8x4Migration:risky' => true,
            '@PhpCsFixer' => true,
            '@PhpCsFixer:risky' => true,
            '@PER-CS3x0' => true,
            '@PER-CS3x0:risky' => true,
            'fopen_flags' => false,
            'blank_line_before_statement' => [
                'statements' => [
                    'continue',
                    'declare',
                    'default',
                    'return',
                    'throw',
                    'try',
                ],
            ],
            'native_function_invocation' => false,
            'multiline_whitespace_before_semicolons' => true,
            'phpdoc_to_comment' => false,
        ]);
