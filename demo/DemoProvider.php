<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Demo;

use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Provider\AbstractProvider;
use Vasoft\Joke\Templator\Component\ComponentCollection;
use Vasoft\Joke\Templator\Demo\Component\DayComponent;
use Vasoft\Joke\Templator\Demo\Component\RandomComponent;
use Vasoft\Joke\Templator\Lexer\StatementToken;
use Vasoft\Joke\Templator\Lexer\TokenDescriptor;
use Vasoft\Joke\Templator\TemplatorConfig;

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

        /** @var TemplatorConfig $config */
        $config = $this->serviceContainer->get(TemplatorConfig::class);
        $config->tokenCollection->upsert(
            new TokenDescriptor(open: '{#', close: '#}', tokenClass: StatementToken::class),
        );
        $config->directiveCollection->upsert(StatementToken::class, 'bold');
        $config->addDirectiveHandler('bold', BoldRawHandler::class);
        $config->directiveCollection->upsert(StatementToken::class, 'format_date');
        $config->addDirectiveHandler('format_date', FormatDateHandler::class);
    }

    public function provides(): array
    {
        return [];
    }
}
