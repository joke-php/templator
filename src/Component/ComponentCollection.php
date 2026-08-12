<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Component;

use Vasoft\Joke\Container\Exceptions\ParameterResolveException;
use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Templator\Exceptions\TemplatorException;

/**
 * Коллекция компонентов шаблонизатора с поддержкой ленивой инициализации через DI-контейнер.
 *
 * Компоненты могут быть зарегистрированы как готовые экземпляры, фабрики (callable)
 * или class-string для автоматического разрешения через ServiceContainer.
 *
 * @phpstan-type ComponentDef BaseComponent|callable(): BaseComponent|class-string<BaseComponent>
 */
class ComponentCollection
{
    /** @var array<non-empty-string, ComponentDef> Коллекция компонентов */
    protected array $components = [];

    /**
     * Создает коллекцию компонентов.
     *
     * @param ServiceContainer $container DI-контейнер для ленивого разрешения компонентов
     */
    public function __construct(protected readonly ServiceContainer $container) {}

    /**
     * Регистрирует компонент в коллекции.
     *
     * @param non-empty-string $componentName Уникальное имя компонента
     * @param ComponentDef     $value         Экземпляр, замыкание или class-string компонента
     */
    public function set(string $componentName, mixed $value): static
    {
        $this->components[$componentName] = $value;

        return $this;
    }

    /**
     * Полностью заменяет текущую коллекцию заданным набором компонентов.
     *
     * @param array<non-empty-string, ComponentDef> $props Новая коллекция компонентов
     */
    public function reset(array $props): static
    {
        $this->components = $props;

        return $this;
    }

    /**
     * Возвращает экземпляр компонента по имени.
     *
     * Если компонент зарегистрирован как class-string или callable,
     * он будет разрешён через DI-контейнер при первом обращении.
     *
     * @param non-empty-string $componentName Имя зарегистрированного компонента
     *
     * @return BaseComponent Разрешённый экземпляр компонента
     *
     * @throws TemplatorException        Если компонент не зарегистрирован или сли разрешённый объект не является экземпляром BaseComponent
     * @throws ParameterResolveException При ошибках автосвязывания параметров
     */
    public function get(string $componentName): BaseComponent
    {
        if (!array_key_exists($componentName, $this->components)) {
            throw new TemplatorException("Component '{$componentName}' not found.");
        }
        if ($this->components[$componentName] instanceof BaseComponent) {
            return $this->components[$componentName];
        }
        $component = $this->container->make($this->components[$componentName]);
        if (!$component instanceof BaseComponent) {
            throw new TemplatorException("Component '{$componentName}' must be instance of BaseComponent.");
        }

        return $component;
    }
}
