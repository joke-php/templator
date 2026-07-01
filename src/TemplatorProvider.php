<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator;

use Vasoft\Joke\Config\AbstractConfig;
use Vasoft\Joke\Config\Exceptions\UnknownConfigException;
use Vasoft\Joke\Container\ServiceContainer;
use Vasoft\Joke\Contract\Provider\ConfigurableServiceProviderInterface;
use Vasoft\Joke\Provider\AbstractProvider;
use Vasoft\Joke\Templator\Compiler\DefaultCompiler;
use Vasoft\Joke\Templator\Contracts\LexerInterface;
use Vasoft\Joke\Templator\Contracts\Parser\ParserInterface;
use Vasoft\Joke\Templator\Handler\Directive\EachHandler;
use Vasoft\Joke\Templator\Handler\Directive\IfHandler;
use Vasoft\Joke\Templator\Handler\Node\BlockNodeHandler;
use Vasoft\Joke\Templator\Handler\Node\PrintNodeHandler;
use Vasoft\Joke\Templator\Handler\Node\StatementNodeHandler;
use Vasoft\Joke\Templator\Handler\Node\TextNodeHandler;
use Vasoft\Joke\Templator\Handler\Statement\CsrfHandler;
use Vasoft\Joke\Templator\Lexer\DefaultLexer;
use Vasoft\Joke\Templator\Lexer\PrintToken;
use Vasoft\Joke\Templator\Lexer\StatementToken;
use Vasoft\Joke\Templator\Lexer\TokenDescriptor;
use Vasoft\Joke\Templator\Parser\DefaultParser;
use Vasoft\Joke\Templator\Parser\Node\BlockNode;
use Vasoft\Joke\Templator\Parser\Node\PrintNode;
use Vasoft\Joke\Templator\Parser\Node\StatementNode;
use Vasoft\Joke\Templator\Parser\Node\TextNode;
use Vasoft\Joke\Templator\Render\DefaultRenderer;

/**
 * Сервис-провайдер шаблонизатора Joke.
 *
 * Отвечает за регистрацию движка шаблонизатора в контейнере зависимостей
 * и инициализацию конфигурации стандартными токенами, узлами и директивами.
 *
 * @todo Добавить в ноду информацию о классе токена и связать обработчики директив с классом токена
 *        для разрешения возможных коллизий имен директив в разных типах токенов.
 */
class TemplatorProvider extends AbstractProvider implements ConfigurableServiceProviderInterface
{
    /**
     * @param ServiceContainer $serviceContainer контейнер зависимостей приложения
     */
    public function __construct(
        private readonly ServiceContainer $serviceContainer,
    ) {}

    /**
     * {@inheritDoc}
     *
     * Регистрирует основные сервисы шаблонизатора в контейнере.
     * TemplateEngine регистрируется как синглтон для повторного использования.
     */
    public function register(): void
    {
        $this->serviceContainer->registerSingleton(TemplateEngine::class, TemplateEngine::class);
        $this->serviceContainer->registerSingleton(LexerInterface::class, DefaultLexer::class);
        $this->serviceContainer->registerSingleton(ParserInterface::class, DefaultParser::class);
        $this->serviceContainer->registerSingleton('templator.compiler', DefaultCompiler::class);
        $this->serviceContainer->registerSingleton('templator.renderer', DefaultRenderer::class);
    }

    /**
     * {@inheritDoc}
     *
     * Инициализирует конфигурацию шаблонизатора настройками по умолчанию.
     * Вызывается после регистрации всех провайдеров, когда TemplatorConfig уже доступен в контейнере.
     *
     * Регистрирует:
     * - Лексические токены ({{ }}, {% %})
     * - Обработчики узлов AST (Text, Print, Block, Statement)
     * - Директивы и их обработчики (if, foreach, csrf)
     */
    public function boot(): void
    {
        /** @var TemplatorConfig $config */
        $config = $this->serviceContainer->get(TemplatorConfig::class);

        $this->registerDefaultTokens($config);
        $this->registerDefaultNodeHandlers($config);
        $this->registerDefaultDirectives($config);
    }

    /**
     * Регистрирует стандартные обработчики узлов AST.
     *
     * Связывает классы узлов с соответствующими обработчиками:
     * TextNode → TextNodeHandler, PrintNode → PrintNodeHandler,
     * BlockNode → BlockNodeHandler, StatementNode → StatementNodeHandler.
     *
     * @param TemplatorConfig $config конфигурация шаблонизатора
     */
    private function registerDefaultNodeHandlers(TemplatorConfig $config): void
    {
        $config->addNodeHandler(TextNode::class, TextNodeHandler::class);
        $config->addNodeHandler(PrintNode::class, PrintNodeHandler::class);
        $config->addNodeHandler(BlockNode::class, BlockNodeHandler::class);
        $config->addNodeHandler(StatementNode::class, StatementNodeHandler::class);
    }

    /**
     * Регистрирует стандартные директивы и их обработчики.
     *
     * Настраивает синтаксис директив (BEGIN/END/BRANCH/SINGLE) в DirectiveCollection
     * и связывает имена директив с классами обработчиков.
     *
     * @param TemplatorConfig $config конфигурация шаблонизатора
     */
    private function registerDefaultDirectives(TemplatorConfig $config): void
    {
        $stmt = StatementToken::class;

        $config->directiveCollection->upsert($stmt, 'if', '/if', ['else', 'elseif']);
        $config->directiveCollection->upsert($stmt, 'foreach', '/foreach');
        $config->directiveCollection->upsert($stmt, 'csrf');

        $config->addDirectiveHandler('if', IfHandler::class);
        $config->addDirectiveHandler('foreach', EachHandler::class);
        $config->addDirectiveHandler('csrf', CsrfHandler::class);
    }

    /**
     * Регистрирует стандартные лексические токены.
     *
     * Определяет маркеры для выражений вывода ({{ }}) и инструкций ({% %}).
     *
     * @param TemplatorConfig $config конфигурация шаблонизатора
     */
    private function registerDefaultTokens(TemplatorConfig $config): void
    {
        $config->tokenCollection->upsert(new TokenDescriptor('{{', '}}', PrintToken::class));
        $config->tokenCollection->upsert(new TokenDescriptor('{%', '%}', StatementToken::class));
    }

    /**
     * @return list<class-string> список классов сервисов, предоставляемых этим провайдером
     */
    public function provides(): array
    {
        return [TemplateEngine::class];
    }

    /**
     * @return list<class-string<AbstractConfig>> список классов конфигураций, создаваемых этим провайдером
     */
    public static function provideConfigs(): array
    {
        return [TemplatorConfig::class];
    }

    /**
     * {@inheritDoc}
     *
     * Фабричный метод для создания экземпляра конфигурации шаблонизатора.
     *
     * @param string           $configClass запрашиваемый класс конфигурации
     * @param ServiceContainer $container   контейнер зависимостей (не используется, но требуется интерфейсом)
     *
     * @return AbstractConfig экземпляр TemplatorConfig
     *
     * @throws UnknownConfigException если запрошен неизвестный класс конфигурации
     */
    public static function buildConfig(string $configClass, ServiceContainer $container): AbstractConfig
    {
        if (TemplatorConfig::class === $configClass) {
            return new TemplatorConfig();
        }

        throw new UnknownConfigException($configClass);
    }
}
