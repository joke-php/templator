<?php

namespace Vasoft\Joke\Templator\Container;


use Vasoft\Joke\Container\BaseContainer;
use Vasoft\Joke\Container\Exceptions\ContainerException;
use Vasoft\Joke\Container\Exceptions\ParameterResolveException;

/**
 * DI контейнер шаблонизатора
 */
class TemplateContainer extends BaseContainer
{
    protected function initDefault(): void
    {
        parent::initDefault();
        $this->registerSingleton(TokenCollection::class, TokenCollection::class);
    }

    /**
     * Возвращает коллекцию описаний токенов
     * @return TokenCollection
     * @throws ContainerException
     * @throws ParameterResolveException
     */
    public function getTokenCollection(): TokenCollection
    {
        return $this->get(TokenCollection::class);
    }
}