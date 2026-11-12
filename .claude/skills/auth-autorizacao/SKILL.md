---
name: auth-autorizacao
description: Como funcionam e como usar autenticação (Laravel Fortify), sessão, proteção de rotas, autorização e props compartilhadas do Inertia neste projeto. Use sempre que mexer em login, logout, senha, sessão, middleware, proteção de rota, policy, `HandleInertiaRequests`, menu, ou quando precisar decidir quem pode ver ou fazer algo no painel ou numa rota pública.
---

# Autenticação e autorização

Para detalhes do Fortify (2FA, passkeys, customização de respostas) use a skill
`fortify-development`. Esta skill diz como o acesso está montado **neste projeto**.

## Como o acesso funciona

1. **Login**: Fortify, e-mail e senha, guard `web` (`config/fortify.php`). O limitador
   `login` já restringe tentativas.
2. **Sem cadastro público**: só `Features::resetPasswords()` está ligado. O sistema é de uso
   individual; o usuário é criado por seeder ou comando. Não ligue `registration` sem pedido
   explícito: abriria o painel a qualquer visitante.
3. **Painel**: toda rota fica em `Route::middleware(['auth', 'verified'])`.
4. **Senha confirmada**: telas sensíveis usam `RequirePassword` (veja `routes/settings.php`).
   Aplique o mesmo a telas que mostram ou trocam credenciais de integração.
5. **Superfícies públicas**: presell e webhooks não têm login. Elas são a superfície de
   ataque real e seguem as regras da skill `seguranca`.

## Middlewares

| Middleware | O que garante | Onde usar |
|---|---|---|
| `auth` | usuário logado | toda rota do painel |
| `verified` | e-mail verificado | toda rota do painel |
| `RequirePassword::class` | senha confirmada há pouco | credenciais, segurança, ações críticas |
| `throttle:N,1` | limite por minuto | troca de senha, rota pública, ação que chama API externa ou gasta crédito |

```php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('connections', [ConnectionController::class, 'index'])->name('connections.index');

    Route::put('connections/{connection}', [ConnectionController::class, 'update'])
        ->middleware([RequirePassword::class, 'throttle:6,1'])
        ->name('connections.update');
});
```

Uma rota do painel sem `auth` é um defeito, mesmo que "ninguém conheça a URL".

## Autorização

Hoje há um único usuário: estar logado é a permissão. Ainda assim, responda a três perguntas
em toda ação:

1. **A rota exige login?** `auth` + `verified`.
2. **Os IDs da URL pertencem uns aos outros?** Em rota aninhada use `->scopeBindings()`
   (`projects/{project}/scenes/{scene}`: a cena é do projeto). ID vindo do corpo passa por
   `Rule::exists`.
3. **Há regra de estado?** (conexão desativada, vídeo já em geração, conversão já enviada.)
   Verifique no servidor e responda com `abort(403)`, `abort(409)` ou erro de validação.

Se o sistema ganhar mais de um usuário, a autorização entra por **Policy**
(`php artisan make:policy`), com `Gate::authorize` no controller ou `can:` na rota, e toda
query passa a filtrar pelo dono. Não espalhe `if ($user->id === ...)` pelos controllers.

Esconder menu ou botão no front é só usabilidade. A rota continua precisando do middleware,
porque a requisição pode ser feita sem a tela.

## Inertia: o que compartilhar

`app/Http/Middleware/HandleInertiaRequests.php` compartilha `name`, `auth.user` e
`sidebarOpen`. Props compartilhadas vão no HTML inicial e em toda resposta: o que está ali,
o navegador lê.

- `auth.user` sai pelo model; o que protege é o `#[Hidden]` de `User`. Ao criar coluna
  sensível em `users`, adicione-a ao `Hidden` no mesmo commit.
- Nunca compartilhe token, chave de API, credencial de integração, segredo de webhook nem
  configuração do servidor.
- Dado caro ou raro vai como closure (`fn () => ...`) ou `Inertia::optional`, para não ser
  calculado em toda requisição.
- Ao mudar a forma de `auth` ou dos dados compartilhados, atualize `resources/js/types`.

Mensagens rápidas usam `Inertia::flash('toast', [...])` (skill `popup-form`), não prop
compartilhada.

## Sessão, login e logout

- O Fortify já regenera a sessão no login e invalida no logout. Em login manual
  (`Auth::login`) chame `$request->session()->regenerate()`; em logout manual,
  `session()->invalidate()` e `session()->regenerateToken()`, como em
  `ProfileController::destroy`.
- Logout sempre por POST. Em GET, qualquer site dispara com uma tag de imagem.
- CSRF: o Inertia já envia `X-XSRF-TOKEN`. Não crie exceção em `validateCsrfTokens` para
  rota do painel. A única exceção legítima é webhook de terceiro, que em troca **precisa**
  validar assinatura (skill `seguranca`).
- Cookies: `appearance` e `sidebar_state` ficam fora da criptografia de propósito
  (`bootstrap/app.php`), porque são lidos no front. Não adicione outros à lista.
- Produção: `SESSION_SECURE_COOKIE=true`, `SESSION_HTTP_ONLY=true`, `SESSION_SAME_SITE=lax`,
  `APP_DEBUG=false`.
- A aplicação roda atrás de proxy (`trustProxies(at: '*')`). IP para `throttle` e log vem de
  `$request->ip()`; não leia `X-Forwarded-For` na mão.
- O Inertia guarda as props no histórico do navegador. Em tela com credencial ou dado
  pessoal, ligue `Inertia::encryptHistory()` e limpe no logout (`Inertia::clearHistory()`).
- Senha: use as regras de `app/Concerns/PasswordValidationRules.php`; não escreva regra nova.
- Erro de autenticação é genérico ("credenciais inválidas"): não revele se o e-mail existe.

## Testes mínimos de acesso (Pest)

Para cada rota nova do painel, seguindo `tests/Feature/DashboardTest.php`:

```php
test('guests are redirected to the login page', function () {
    $this->get(route('offers.index'))->assertRedirect(route('login'));
});

test('authenticated users can list offers', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('offers.index'))
        ->assertOk();
});
```

Cubra também: verbo de escrita sem login é redirecionado e não grava; rota com
`RequirePassword` redireciona para a confirmação; ID filho de outro pai responde 404.
