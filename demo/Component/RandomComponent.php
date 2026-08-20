<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Demo\Component;

use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Http\Response\Html\PageBuilder;
use Vasoft\Joke\Templator\Component\BaseComponent;

/**
 * Демонстрационный компонент выводящий случайное число.
 */
class RandomComponent extends BaseComponent
{
    protected string $demo = 'testData';
    protected int $min = 1000;
    protected int $max = 9999;

    public function __construct(public readonly ServiceContainer $container) {}

    /**
     * @param array<string,int> $options
     *
     * @return $this
     */
    public function setOptions(mixed $options): static
    {
        if (isset($options['min'])) {
            $this->min = (int) $options['min'];
        }
        if (isset($options['max'])) {
            $this->max = (int) $options['max'];
        }

        return $this;
    }

    public static function vendor(): string
    {
        return 'vasoft';
    }

    public static function name(): string
    {
        return 'random';
    }

    public function getDefaultTemplatePath(): string
    {
        return dirname(__DIR__, 2) . '/templates/components/vasoft/random/';
    }

    protected function execute(PageBuilder $pageBuilder): void
    {
        $this->demo = (string) random_int($this->min, $this->max);
        $pageBuilder->setTitle('example');
    }

    protected function beforeRender(PageBuilder $pageBuilder): void
    {
        $css = $this->templateFile('style.css');
        if (file_exists($css)) {
            $pageBuilder->css->addToBody($css);
        }
        $js = $this->templateFile('script.js');
        if (file_exists($js)) {
            $pageBuilder->js->addToBody($js);
        }
    }

    protected function getContext(): array
    {
        return [
            'demo' => $this->demo,
        ];
    }
}
