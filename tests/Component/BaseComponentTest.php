<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Demo\Component;

use PHPUnit\Framework\Attributes\TestDox;
use Vasoft\Joke\Http\Response\Html\PageBuilder;
use Vasoft\Joke\Http\Response\HtmlPageResponse;
use Vasoft\Joke\Templator\Component\BaseComponent;
use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Templator\TemplatedResponse;
use Vasoft\Joke\Templator\TemplateEngine;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Templator\Component\BaseComponent
 */
#[TestDox('Базовый класс компонентов')]
final class BaseComponentTest extends TestCase
{
    private PageBuilder $builder;
    private TemplateEngine $engine;
    private TemplatedResponse $response;
    /**
     * @var BaseComponent|__anonymous@1525
     */
    private $component;

    #[TestDox('Вызывается метод выполнения и подключается файл шаблона на основе данных компонента')]
    public function testBaseComponent(): void
    {
        $this->component->compile($this->engine, $this->response);
        self::assertTrue($this->component->executed, 'Не вызван метод выполнения компонента');
        self::assertTrue($this->component->beforeRender, 'Не вызван метод перед подключением файла');
        self::assertSame(
            '/default_templates/vasoft:test/default/template.php',
            $this->engine->file,
            'Не подключается файл шаблона',
        );
        self::assertSame(
            ['var' => 'test'],
            $this->engine->context,
            'Не передан контекст шаблона',
        );
        self::assertSame(
            ['component' => $this->component],
            $this->engine->extraEngineContext,
            'Не передан компонент в контекст файла шаблона',
        );
        self::assertSame(
            86400,
            $this->engine->ttl,
            'Не переданы TTL по умолчанию',
        );
    }

    #[TestDox('Настраиваемый шаблон')]
    public function testCustomTemplate(): void
    {
        $this->component->setTemplateName('custom');
        $this->component->compile($this->engine, $this->response);
        self::assertSame('/default_templates/vasoft:test/custom/template.php', $this->engine->file);
    }

    #[TestDox('Настраиваемое время кеширования файла')]
    public function testCustomTtl(): void
    {
        $this->component->setFileTtl(10);
        $this->component->compile($this->engine, $this->response);
        self::assertSame(10, $this->engine->ttl);
    }

    #[TestDox('Настраиваемый файл шаблона компонента')]
    public function testCustomFile(): void
    {
        $this->component->setComponentTemplateFile('custom');
        $this->component->compile($this->engine, $this->response);
        self::assertSame(
            '/default_templates/vasoft:test/default/custom.php',
            $this->engine->file,
        );
    }

    #[TestDox('Если имя шаблона пустое - файл не должен исполняться и не выполняется метод перед его подключением')]
    public function testEmptyFile(): void
    {
        $this->component->setTemplateName('');
        $this->component->compile($this->engine, $this->response);
        self::assertFalse($this->component->beforeRender, 'Вызван метод перед подключением файла');
        self::assertSame('', $this->engine->file, 'Подключен файл');
    }

    #[TestDox('Опции перезаписываются при повторном вызове метода')]
    public function testSetOptions(): void
    {
        $this->component->setComponentTemplateFile('');
        $arOptions = ['ttl' => 134];
        $oOptions = new \stdClass();
        $this->component->setOptions($arOptions);
        self::assertSame($arOptions, $this->component->getOptions());
        $this->component->setOptions($oOptions);
        self::assertSame($oOptions, $this->component->getOptions());
    }

    #[TestDox('Опции не перезаписываются если передан null')]
    public function testSetOptionsNull(): void
    {
        $this->component->setComponentTemplateFile('');
        $arOptions = ['ttl' => 134];
        $this->component->setOptions($arOptions);
        self::assertSame($arOptions, $this->component->getOptions());
        $this->component->setOptions(null);
        self::assertSame($arOptions, $this->component->getOptions());
    }

    protected function setUp(): void
    {
        $this->component = new class extends BaseComponent {
            public bool $executed = false;
            public bool $beforeRender = false;

            public function getOptions(): mixed
            {
                return $this->options;
            }

            protected function beforeRender(PageBuilder $pageBuilder): void
            {
                parent::beforeRender($pageBuilder);
                $this->beforeRender = true;
            }

            public function setComponentTemplateFile(string $componentTemplateFile): void
            {
                $this->componentTemplateFile = $componentTemplateFile;
            }

            public function setFileTtl(int $ttl): void
            {
                $this->fileTtl = $ttl;
            }

            protected function execute(PageBuilder $pageBuilder): void
            {
                parent::execute($pageBuilder);
                $this->executed = true;
            }

            protected function getContext(): array
            {
                return array_merge(parent::getContext(), ['var' => 'test']);
            }

            protected function getFileContext(): array
            {
                return array_merge(parent::getFileContext(), ['example' => 1]);
            }

            public static function vendor(): string
            {
                return 'vasoft';
            }

            public static function name(): string
            {
                return 'test';
            }

            public function getDefaultTemplatePath(): string
            {
                return '/default_templates/';
            }
        };


        $this->builder = new class extends PageBuilder {
            public function __construct() {}
        };
        $this->response = (new \ReflectionClass(TemplatedResponse::class))
            ->newInstanceWithoutConstructor();

        $property = new \ReflectionProperty(HtmlPageResponse::class, 'builder');
        $property->setValue($this->response, $this->builder);

        $this->engine = new class extends TemplateEngine {
            public string $file = '';
            public array $context = [];
            public int $ttl = -1;
            public ?array $extraEngineContext = [];

            public function __construct() {}

            public function getComponentTemplateDir(BaseComponent $component, string $templateName): string
            {
                return $component->getDefaultTemplatePath() . $component::componentName() . '/' . $templateName;
            }

            public function includeFile(
                string $file,
                array $context,
                int $ttl = 864000,
                array $extraEngineContext = [],
            ): void {
                $this->file = $file;
                $this->context = $context;
                $this->ttl = $ttl;
                $this->extraEngineContext = $extraEngineContext;
            }
        };
    }
}
