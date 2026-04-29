<?php

namespace Vasoft\Joke\Templator\Core\Container;

use Vasoft\Joke\Core\BaseContainer;
use Vasoft\Joke\Core\Exceptions\ParameterResolveException;

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
     * @throws ParameterResolveException
     */
    public function getTokenCollection(): TokenCollection
    {
        return $this->get(TokenCollection::class);
    }
}