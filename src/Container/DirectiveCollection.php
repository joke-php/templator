<?php

declare(strict_types=1);

namespace Vasoft\Joke\Templator\Container;

use Vasoft\Joke\Templator\Contracts\TokenInterface;
use Vasoft\Joke\Templator\Exceptions\TemplatorException;

/**
 * Коллекция правил синтаксиса директив.
 *
 * Определяет связи между открывающими, закрывающими и ветвящимися директивами
 * для конкретного типа токена (например, StatementToken).
 * Используется парсером для определения типа директивы (BEGIN, END, BRANCH, SINGLE)
 * и проверки корректности вложенности блоков.
 */
class DirectiveCollection
{
    /**
     *  Карта открывающих директив и их соответствующих закрывающих тегов.
     *
     * @var array<class-string<TokenInterface>,array<string,string>>
     */
    private array $directives = [];
    /**
     * Обратная карта: от закрывающего тега к открывающему.
     * Используется для быстрой проверки соответствия при закрытии блока.
     *
     * @var array<class-string<TokenInterface>,array<string,string>>
     */
    private array $directivesInverse = [];
    /**
     * Карта ветвящихся директив (else, elseif, case), привязанных к родительскому блоку.
     *
     * @var array<class-string<TokenInterface>,array<string,string>>
     */
    private array $directivesBranches = [];

    /**
     * Добавляет новое правило директивы с проверкой на уникальность.
     *
     * @param class-string<TokenInterface> $tokenClass      класс токена, к которому относится директива
     * @param string                       $directiveBegin  имя открывающей директивы (например, 'if')
     * @param string                       $directiveEnd    Имя закрывающей директивы (например, '/if'). Пустая строка для одиночных тегов.
     * @param list<string>                 $directiveBranch список имен ветвящихся директив (например, ['else', 'elseif'])
     *
     * @return $this
     *
     * @throws TemplatorException если открывающая директива уже зарегистрирована для данного класса токена
     */
    public function add(
        string $tokenClass,
        string $directiveBegin,
        string $directiveEnd = '',
        array $directiveBranch = [],
    ): static {
        if (isset($this->directives[$tokenClass][$directiveBegin])) {
            throw new TemplatorException("Directive '{$directiveBegin}' already defined for type '{$tokenClass}'.");
        }
        $this->upsert($tokenClass, $directiveBegin, $directiveEnd, $directiveBranch);

        return $this;
    }

    /**
     * Добавляет или обновляет правило директивы.
     *
     * Позволяет переопределять существующие правила или добавлять новые без проверки на дубликаты.
     *
     * @param class-string<TokenInterface> $tokenClass      класс токена
     * @param string                       $directiveBegin  имя открывающей директивы
     * @param string                       $directiveEnd    имя закрывающей директивы
     * @param list<string>                 $directiveBranch список ветвящихся директив
     *
     * @return $this
     */
    public function upsert(
        string $tokenClass,
        string $directiveBegin,
        string $directiveEnd = '',
        array $directiveBranch = [],
    ): static {
        if (!array_key_exists($tokenClass, $this->directives)) {
            $this->directives[$tokenClass] = [$directiveBegin => $directiveEnd];
            $this->directivesInverse[$tokenClass] = [$directiveEnd => $directiveBegin];
            $this->directivesBranches[$tokenClass] = [];
        } else {
            if (array_key_exists($directiveBegin, $this->directives[$tokenClass])) {
                $oldEnd = $this->directives[$tokenClass][$directiveBegin];
                unset($this->directivesInverse[$tokenClass][$oldEnd]);
                $this->cleanOldBranches($tokenClass, $directiveBegin);
            }

            $this->directives[$tokenClass][$directiveBegin] = $directiveEnd;
            $this->directivesInverse[$tokenClass][$directiveEnd] = $directiveBegin;
        }
        foreach ($directiveBranch as $directive) {
            $this->directivesBranches[$tokenClass][$directive] = $directiveBegin;
        }

        return $this;
    }

    /**
     * Очищает старые связи ветвящихся директив при обновлении правила блока.
     *
     * Удаляет из реестра ветвей все записи, которые ранее указывали на обновляемую
     * открывающую директиву. Это необходимо для предотвращения конфликтов, когда
     * одна директива имеющая ветвления переопределяется.
     *
     * @param string $tokenClass     класс токена, для которого производится очистка
     * @param string $directiveBegin имя открывающей директивы, связи которой нужно удалить
     */
    private function cleanOldBranches(string $tokenClass, string $directiveBegin): void
    {
        $this->directivesBranches[$tokenClass] = array_filter(
            $this->directivesBranches[$tokenClass],
            static fn($directive) => $directive !== $directiveBegin,
        );
    }

    /**
     * Возвращает имя открывающей директивы, соответствующей данной закрывающей или ветвящейся.
     *
     * @param string $tokenClass класс токена
     * @param string $directive  имя текущей директивы (закрывающей или ветви)
     *
     * @return string имя родительской открывающей директивы или пустая строка, если связь не найдена
     */
    public function getOpenDirective(string $tokenClass, string $directive): string
    {
        return $this->directivesInverse[$tokenClass][$directive] ?? $this->directivesBranches[$tokenClass][$directive] ?? '';
    }

    /**
     * Определяет тип директивы на основе зарегистрированных правил.
     *
     * @param string $tokenClass класс токена
     * @param string $directive  имя директивы для анализа
     *
     * @return DirectiveType тип директивы (BEGIN, END, BRANCH, SINGLE или UNKNOWN)
     */
    public function getType(string $tokenClass, string $directive): DirectiveType
    {
        if (isset($this->directives[$tokenClass][$directive])) {
            return '' === $this->directives[$tokenClass][$directive]
                ? DirectiveType::SINGLE
                : DirectiveType::BEGIN;
        }
        if (isset($this->directivesInverse[$tokenClass][$directive])) {
            return DirectiveType::END;
        }
        if (isset($this->directivesBranches[$tokenClass][$directive])) {
            return DirectiveType::BRANCH;
        }

        return DirectiveType::UNKNOWN;
    }
}
