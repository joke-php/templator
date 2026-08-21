<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Demo\Component;

use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Http\Response\Html\PageBuilder;
use Vasoft\Joke\Templator\Component\BaseComponent;

/**
 * Демонстрационный компонент выводящий случайное число.
 */
class DayComponent extends BaseComponent
{
    private bool $isWorkDay = false;

    public function __construct(public readonly ServiceContainer $container) {}


    public static function vendor(): string
    {
        return 'vasoft';
    }

    public static function name(): string
    {
        return 'day';
    }

    public function getDefaultTemplatePath(): string
    {
        return dirname(__DIR__, 2) . '/templates/components/vasoft/day/';
    }

    protected function execute(PageBuilder $pageBuilder): void
    {
        $dayNumber = date('N');
        $this->isWorkDay = $dayNumber >= 1 && $dayNumber <= 5;
    }

    protected function getContext(): array
    {
        return [
            'isWorkDay' => $this->isWorkDay,
        ];
    }
}
