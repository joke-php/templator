# Директива {% csrf %}

## Назначение

Выводит актуальное значение CSRF-токена в шаблоне, чтобы форма могла отправить его вместе с запросом и пройти проверку `CsrfMiddleware`.

## Синтаксис

```
{% csrf %}
```

Одиночная директива без аргументов. **Важно:** она выводит только само значение токена (строку), а не готовый `<input>` — тег скрытого поля нужно написать в шаблоне самостоятельно.

## Примеры использования

### В HTML-форме

Имя поля должно быть строго `csrf_token` — это имя, которое `CsrfTokenManager` ищет в GET/POST-параметрах при проверке (`CsrfTokenManager::CSRF_TOKEN_NAME`):

```html
<form method="post" action="/profile/update">
    <input type="hidden" name="csrf_token" value="{% csrf %}">
    <input type="text" name="name" value="{{ user.name }}">
    <button type="submit">Сохранить</button>
</form>
```

### Полный цикл (контроллер + шаблон)

```php
$router->post(
    '/profile/update',
    static function (HttpRequest $request, ServiceContainer $container, TemplateEngine $engine) {
        // CsrfMiddleware группы 'web' уже проверил csrf_token до вызова этого обработчика
        $name = $request->post('name');

        return new TemplatedResponse($container, $engine)
            ->show('pages/profile.php', ['user' => ['name' => $name]], 0);
    },
);
```

```html
{% csrf %}
```

## Механизм работы

В скомпилированном шаблоне доступна переменная `$container`; директива генерирует код, который получает `CsrfTokenManager` и текущий `HttpRequest` из контейнера и выводит `$tokenManager->getServerToken($request)` — это тот же серверный токен, который `CsrfMiddleware` ожидает увидеть в следующем запросе. Значение выводится через простой `echo`, **без** `htmlspecialchars()` (как у `{{ }}`) — это осознанно допустимо, потому что токен генерируется сервером и никогда не содержит пользовательского ввода.

Если сервисы `CsrfTokenManager`/`HttpRequest` недоступны в контейнере, скомпилированный код проверяет их на `null` и просто ничего не выводит (пустая строка) — ошибка компиляции/рендеринга не возникает, но форма без валидного токена не пройдёт проверку при отправке.

## Важные замечания

- Проверка токена в `CsrfTokenManager::validate()` применяется ко всем небезопасным HTTP-методам (всё, кроме `GET`/`HEAD`) — не только к `POST`, но и к `PUT`, `PATCH`, `DELETE`.
- Токен можно передать не только полем формы `csrf_token`, но и заголовком `X-Csrf-Token` или cookie `XSRF-TOKEN` — для форм используется первый вариант.
- Директива полезна только на маршрутах, обёрнутых в `CsrfMiddleware` — по умолчанию это группа `web` (`routes/web.php`); на маршрутах без этого middleware токен генерируется, но никем не проверяется.

## Что дальше

[Отложенный вывод значений](08-defer.md)
