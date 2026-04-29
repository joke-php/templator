<?php

namespace Vasoft\Joke\Templator\Tests\Integration;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Templator\Exceptions\RenderingException;
use Vasoft\Joke\Templator\Exceptions\TemplatorException;
use Vasoft\Joke\Templator\Render\Handlers\EachHandler;
use Vasoft\Joke\Templator\Render\Handlers\EchoHandler;
use Vasoft\Joke\Templator\Render\Handlers\IfHandler;
use Vasoft\Joke\Templator\TemplateEngine;

#[Group("skip")]
class TemplateEngineIntegrationTest extends TestCase
{
    private TemplateEngine $engine;

    protected function setUp(): void
    {
        $container = new ServiceContainer();
        $this->engine = new TemplateEngine($container);

        $this->engine->registerTag('echo', new EchoHandler());
        $this->engine->registerTag('if', new IfHandler());
        $this->engine->registerTag('each', new EachHandler());
    }

    public function testBasicEcho(): void
    {
        $template = 'Hello <j-echo value="name"/>!';
        $context = ['name' => 'Alice'];

        $result = $this->engine->renderString($template, $context);
        self::assertSame('Hello <?php echo $context[\'name\'];?>!', $result);
    }

    public function testEchoEscapesHtml(): void
    {
        $template = '<j-echo value="content" escaped j-static/>';
        $context = ['content' => '<script>alert("xss")</script>'];

        $result = $this->engine->renderString($template, $context);
        self::assertSame('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', $result);
    }

    public function testRawDoesNotEscape(): void
    {
        $template = '<j-echo value="html" j-static/>';
        $context = ['html' => '<b>Bold Text</b>'];

        $result = $this->engine->renderString($template, $context);
        self::assertSame('<b>Bold Text</b>', $result);
    }

    public function testIfConditionTrue(): void
    {
        $template = '<j-if condition="show">Visible</j-if>';
        $context = ['show' => true];

        $result = $this->engine->renderString($template, $context);
        self::assertSame('Visible', $result);
    }

    public function testIfConditionFalse(): void
    {
        $template = '<j-if condition="show" j-static>Hidden</j-if>';
        $context = ['show' => false];

        $result = $this->engine->renderString($template, $context);
        self::assertSame('', $result);
    }

    public function testNestedIfAndEcho(): void
    {
        $template = '<j-if condition="user"><j-echo value="user.name"/></j-if>';
        $context = ['user' => ['name' => 'Bob']];

        $result = $this->engine->renderString($template, $context);
        self::assertSame('Bob', $result);
    }

    public function testEachSimple(): void
    {
        $template = '<j-each items="items" as="item"><j-echo value="item"/>,</j-each>';
        $context = ['items' => ['A', 'B', 'C']];

        $result = $this->engine->renderString($template, $context);
        self::assertSame('A,B,C,', $result);
    }

    public function testEachWithObjects(): void
    {
        $template = '<j-each items="users" as="user"><p><j-echo value="user.name"/></p></j-each>';
        $context = [
            'users' => [
                ['name' => 'Alice'],
                ['name' => 'Bob']
            ]
        ];

        $result = $this->engine->renderString($template, $context);
        self::assertSame('<p>Alice</p><p>Bob</p>', $result);
    }

    public function testComplexNestedStructure(): void
    {
        $template = '
        <j-if condition="authenticated">
            Welcome, <j-echo value="user.name"/>!
            <j-raw value="user.signature"/>
            <ul>
            <j-each items="user.friends" as="friend">
                <li><j-echo value="friend.name"/> (<j-echo value="friend.email"/>)</li>
            </j-each>
            </ul>
        </j-if>
        ';

        $context = [
            'authenticated' => true,
            'user' => [
                'name' => 'Admin',
                'signature' => '<i>Site Administrator</i>',
                'friends' => [
                    ['name' => 'Alice', 'email' => 'alice@example.com'],
                    ['name' => 'Bob', 'email' => 'bob@example.com']
                ]
            ]
        ];

        $result = $this->engine->renderString($template, $context);
        $expected = "
        Welcome, Admin!
            <i>Site Administrator</i>
            <ul>
                <li>Alice (alice@example.com)</li>
                <li>Bob (bob@example.com)</li>
            </ul>
        ";
        // Убираем лишние пробелы для сравнения
        self::assertStringContainsString('Welcome, Admin!', $result);
        self::assertStringContainsString('<i>Site Administrator</i>', $result);
        self::assertStringContainsString('<li>Alice (alice@example.com)</li>', $result);
        self::assertStringContainsString('<li>Bob (bob@example.com)</li>', $result);
    }

    public function testRenderFile(): void
    {
        $templateFile = dirname(__DIR__) . '/Fixtures/test.joke';
        $templateContent = '<j-if condition="test"><j-echo value="message"/></j-if>';
        file_put_contents($templateFile, $templateContent);

        try {
            $result = $this->engine->renderFile($templateFile, [
                'test' => true,
                'message' => 'File rendered successfully!'
            ]);
            self::assertSame('File rendered successfully!', $result);
        } finally {
            unlink($templateFile);
        }
    }

    public function testMissingRequiredAttribute(): void
    {
        self::expectException(RenderingException::class);
        self::expectExceptionMessage("Attribute 'value' is required for <j-echo>");

        $this->engine->renderString('<j-echo/>', []);
    }

    public function testUnknownTag(): void
    {
        self::expectException(TemplatorException::class);
        self::expectExceptionMessage("No handler registered for tag 'j-unknown'");

        $this->engine->renderString('<j-unknown/>', []);
    }
}