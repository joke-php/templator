<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Tests\Component;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestDox;
use Vasoft\Joke\Container\ServiceContainer;
use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Templator\Component\ComponentCollection;
use Vasoft\Joke\Templator\Demo\Component\SimpleComponent;
use Vasoft\Joke\Templator\Exceptions\TemplatorException;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Templator\Component\ComponentCollection
 */
#[TestDox('ComponentCollection Коллекция компонентов')]
#[CoversClass(ComponentCollection::class)]
final class ComponentCollectionTest extends TestCase
{
    private static ServiceContainer $container;

    public static function setUpBeforeClass(): void
    {
        self::$container = new ServiceContainer();
    }

    #[TestDox('reset Перезаписывает всю коллекцию')]
    public function testReset(): void
    {
        $component = new SimpleComponent(self::$container);
        $collection = new ComponentCollection(self::$container);
        $collection->set('vendor1.example', $component);
        $collection->set('vendor2.example', $component);
        $collection->set('vendor3.example', $component);
        $collection->reset([
            'vendor1.example' => SimpleComponent::class,
            'vendor2.example' => new SimpleComponent(self::$container),
            'vendor3.example' => static fn(ServiceContainer $container) => new SimpleComponent($container),
        ]);
        $entity1 = $collection->get('vendor1.example');
        $entity2 = $collection->get('vendor2.example');
        $entity3 = $collection->get('vendor3.example');
        self::assertNotSame($component, $entity1);
        self::assertNotSame($component, $entity2);
        self::assertNotSame($component, $entity3);
        self::assertInstanceOf(SimpleComponent::class, $entity1, 'Не вернуло компонент из объекта');
        self::assertInstanceOf(SimpleComponent::class, $entity2, 'Не вернуло компонент из имени класса');
        self::assertInstanceOf(SimpleComponent::class, $entity3, 'Не вернуло компонент из замыкания');
    }

    #[TestDox('Set регистрирует конкретную сущность и перезаписывает ранее заданную')]
    public function testResetOne(): void
    {
        $component = new SimpleComponent(self::$container);
        $collection = new ComponentCollection(self::$container);
        $collection->set('vendor1.example', new SimpleComponent(self::$container));
        $collection->set('vendor1.example', $component);
        $entity1 = $collection->get('vendor1.example');
        self::assertSame($component, $entity1);
    }

    #[TestDox('Set регистрирует имя класса')]
    public function testSetClassName(): void
    {
        $collection = new ComponentCollection(self::$container);
        $collection->set('vendor1.example', SimpleComponent::class);
        $entity1 = $collection->get('vendor1.example');
        self::assertInstanceOf(SimpleComponent::class, $entity1);
    }

    #[TestDox('Set регистрирует замыкание')]
    public function testSetCallback(): void
    {
        $collection = new ComponentCollection(self::$container);
        $collection->set('vendor1.example', static fn(ServiceContainer $container) => new SimpleComponent($container));
        $entity1 = $collection->get('vendor1.example');
        self::assertInstanceOf(SimpleComponent::class, $entity1);
    }

    #[TestDox('Исключение если компонент не зарегистрирован')]
    public function testNotRegistered(): void
    {
        $collection = new ComponentCollection(self::$container);
        self::expectException(TemplatorException::class);
        self::expectExceptionMessageIs("Component 'vendor1.example' not found.");
        $collection->get('vendor1.example');
    }

    #[TestDox('Исключение если компонент не наследует BaseComponent')]
    public function testNotBaseComponent(): void
    {
        $collection = new ComponentCollection(self::$container);
        $collection->set('vendor1.example', static fn(ServiceContainer $container) => $container);

        self::expectException(TemplatorException::class);
        self::expectExceptionMessageIs("Component 'vendor1.example' must be instance of BaseComponent.");
        $collection->get('vendor1.example');
    }
}
