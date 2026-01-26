<?php

namespace Vasoft\Joke\Templator\Core\Container;

use Vasoft\Joke\Core\BaseContainer;

/**
 * DI контейнер шаблонизатора
 */
class TemplateContainer extends BaseContainer
{
    /** @var TokenCollection Коллекция описаний токенов */
    public TokenCollection $tokenDescriptors {
        get  => $this->tokenDescriptors ??= new TokenCollection();
    }
}