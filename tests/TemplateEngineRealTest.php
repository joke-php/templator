<?php

declare(strict_types=1);


use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\TestDox;
use Vasoft\Joke\Support\FileSystem;
use Vasoft\Joke\Config\Environment;
use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Templator\Demo\Component\RandomComponent;
use Vasoft\Joke\Templator\Exceptions\TemplatorException;
use Vasoft\Joke\Templator\TemplateEngine;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Templator\TemplateEngine;
 */
#[TestDox('TemplateEngine тесты на реальной файловой структуре')]
final class TemplateEngineRealTest extends TestCase
{
    use PHPMock;

    private ServiceContainer $container;
    private static string $cachePath = '';
    private string $lastTemplate = '';
    private array $lastContext = [];
    private string $compiled = '';
    private static string $layoutPath = '';
    private static string $defaultLayoutPath = '';

    #[TestDox('getComponentTemplateDir получает шаблон компонента из текущего шаблона сайта')]
    public function testGetComponentTemplateDir(): void
    {
        $engine = new TemplateEngine($this->container);
        $engine->setTemplate('dark');
        $path = $engine->getComponentTemplateDir(new RandomComponent($this->container), 'variant1');
        self::assertSame(
            self::$cachePath . 'templates/dark/components/vasoft/random/variant1/',
            $path,
        );
    }

    #[TestDox('getComponentTemplateDir получает шаблон компонента из шаблона сайта по умолчанию')]
    public function testGetComponentTemplateDirDefault(): void
    {
        $engine = new TemplateEngine($this->container);
        $engine->setTemplate('dark');
        $path = $engine->getComponentTemplateDir(new RandomComponent($this->container), 'default');
        self::assertSame(
            self::$cachePath . 'templates/default/components/vasoft/random/default/',
            $path,
        );
    }

    #[TestDox('getComponentTemplateDir получает шаблон компонента расположения класса')]
    public function testGetComponentTemplateDirByClass(): void
    {
        $engine = new TemplateEngine($this->container);
        $engine->setTemplate('dark');
        $path = $engine->getComponentTemplateDir(new RandomComponent($this->container), 'variant2');
        self::assertSame(
            self::$cachePath . 'templates/components/vasoft/random/variant2/',
            $path,
        );
    }

    #[TestDox('getComponentTemplateDir кеширует результат')]
    #[RunInSeparateProcess]
    public function testGetComponentTemplateDirCached(): void
    {
        $fileGetContents = $this->getFunctionMock('Vasoft\Joke\Templator', 'file_exists');
        $fileGetContents->expects(self::once())->willReturn(true);

        $engine = new TemplateEngine($this->container);
        $engine->getComponentTemplateDir(new RandomComponent($this->container), 'default');
        $path = $engine->getComponentTemplateDir(new RandomComponent($this->container), 'default');

        self::assertSame(
            self::$cachePath . 'templates/default/components/vasoft/random/default/',
            $path,
        );
    }

    #[TestDox('getComponentTemplateDir выбрасывает исключение если компонент не найден')]
    public function testGetComponentTemplateDirNotFound(): void
    {
        $engine = new TemplateEngine($this->container);
        $engine->setTemplate('dark');
        self::expectException(TemplatorException::class);
        self::expectExceptionMessageIs("Unable to locate template 'unknown' for 'vasoft:random'.");
        $engine->getComponentTemplateDir(new RandomComponent($this->container), 'unknown');
    }

    protected function setUp(): void
    {
        $this->container = new ServiceContainer();
        $env = self::getStubBuilder(Environment::class)
            ->disableOriginalConstructor()
            ->getStub();
        $env->method('getBasePath')->willReturn(self::$cachePath);
        $this->container->registerSingleton('env', $env);
        $this->container->registerSingleton('paths', new FileSystem(self::$cachePath));
    }

    public static function setUpBeforeClass(): void
    {
        self::$cachePath = dirname(__DIR__) . '/';
    }
}
