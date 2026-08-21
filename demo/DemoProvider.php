<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Demo;

use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Provider\AbstractProvider;
use Vasoft\Joke\Templator\Component\ComponentCollection;
use Vasoft\Joke\Templator\Demo\Component\DayComponent;
use Vasoft\Joke\Templator\Demo\Component\RandomComponent;

class DemoProvider extends AbstractProvider
{
    /**
     * @param ServiceContainer $serviceContainer контейнер зависимостей приложения
     */
    public function __construct(
        private readonly ServiceContainer $serviceContainer,
    ) {}

    public function register(): void {}

    public function boot(): void
    {
        /** @var ComponentCollection $components */
        $components = $this->serviceContainer->get(ComponentCollection::class);
        $components->set(RandomComponent::componentName(), RandomComponent::class);
        $components->set(DayComponent::componentName(), DayComponent::class);
    }

    public function provides(): array
    {
        return [];
    }
}
