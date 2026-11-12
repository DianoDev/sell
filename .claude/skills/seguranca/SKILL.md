---
name: seguranca
description: Checklist de cibersegurança e qualidade deste projeto (Laravel 13 + Inertia v3 + Vue 3, com credenciais de APIs de anúncios e de IA, webhooks e páginas públicas). Use antes de concluir qualquer alteração em controller, rota, FormRequest, model, job, integração com API externa, webhook, upload ou download, componente Vue que exibe dados externos, autenticação ou configuração; ao revisar código ou PR; e quando o assunto for segurança, vulnerabilidade, XSS, CSRF, SSRF, SQL injection, IDOR, segredo, token, webhook, LGPD, upload, pentest ou hardening.
---

# Segurança

O sistema guarda credenciais que valem dinheiro (tokens das redes de anúncio, chaves de IA
cobradas por uso) e recebe dados de fora o tempo todo (webhooks, query string das páginas
públicas, respostas de API). As duas perguntas que mais importam em qualquer mudança:

- **Um segredo pode sair do servidor?** (prop Inertia, log, mensagem de erro, repositório,
  resposta JSON, histórico do navegador.)
- **Algo vindo de fora está sendo tratado como confiável?** (virar SQL, HTML, URL acessada
  pelo servidor, caminho de arquivo, destino de redirect ou venda registrada.)

Passe pelo checklist ao terminar uma alteração. Em revisão, reporte cada achado com arquivo,
linha, cenário de exploração e correção.

## Checklist

### Acesso
- [ ] Rota do painel em `['auth', 'verified']`; tela de credencial com `RequirePassword`.
- [ ] Nenhuma mudança de estado em GET.
- [ ] IDs encadeados na URL pertencem uns aos outros (`->scopeBindings()`).
- [ ] Rota pública, ação que chama API externa e ação que gasta crédito têm `throttle`.

### Entrada
- [ ] Controller usa `$request->validated()`; nunca `all()`, `input()` cru ou `except()` em
      `create`/`update`.
- [ ] Toda regra tem tipo e limite; conjunto fechado com `Rule::enum`/`Rule::in`; ID com
      `Rule::exists`; URL com `url:https`.
- [ ] `#[Fillable]` sem colunas de controle (dono, status calculado, custo, contadores).
- [ ] SQL sempre com binding. Coluna e direção de ordenação por lista fechada; nenhuma
      variável interpolada em `DB::raw`, `whereRaw` ou `orderByRaw`.
- [ ] `per_page` por lista fechada (máx. 100); array de filtro com tamanho máximo.

### Saída
- [ ] Sem `v-html` com dado externo. Nome de campanha, título de anúncio, texto de webhook e
      resposta de API são dados de terceiros.
- [ ] `href` vindo de dado só com `http(s)`; link externo com `rel="noopener noreferrer"`.
- [ ] Blade com `{{ }}`; nada de `{!! !!}`. Dado para JavaScript em Blade passa por
      `Js::from()`, nunca por string montada.
- [ ] Props Inertia e respostas JSON só com os campos usados (`->only([...])`, API Resource).
- [ ] Erro para o usuário é genérico; detalhe só em log. Nada de `$e->getMessage()` em
      resposta.

### Segredos e credenciais
- [ ] Token, chave de API e segredo de webhook em coluna com cast `encrypted` e no
      `#[Hidden]` do model.
- [ ] Credencial nunca volta para o front. A tela mostra "configurado" e os últimos 4
      caracteres, no máximo; campo vazio na edição significa "manter".
- [ ] Segredo de infraestrutura só em `.env`, lido por `config()`. `env()` fora de `config/`
      quebra com cache de config. Front só recebe `VITE_*` públicos.
- [ ] Nada de segredo em log, exceção, `dd`, fixture, teste, commit ou mensagem de toast.
      Log de requisição externa omite `Authorization` e corpo com token.
- [ ] `APP_KEY` não muda depois de haver dado criptografado; rotação usa
      `APP_PREVIOUS_KEYS`.
- [ ] Escopo mínimo nos tokens OAuth: leitura onde só se lê métrica.

### Webhooks
- [ ] Assinatura validada antes de qualquer efeito, com `hash_equals` (comparação em tempo
      constante). Sem assinatura válida: 403 e nada gravado.
- [ ] Idempotência: evento repetido (mesmo pedido e tipo) não gera segunda venda. Índice
      único no banco, não só `if` no código.
- [ ] Payload bruto guardado para reprocessar; o processamento vai para fila.
- [ ] Valor, moeda e produto vêm do payload assinado, nunca da query string.
- [ ] Rota fora do CSRF só para o endpoint do webhook, com `throttle`.

### Páginas públicas e redirecionamento
- [ ] Destino de redirect vem do banco (a oferta), nunca de parâmetro da URL. Parâmetro
      `?url=` ou `?next=` é open redirect.
- [ ] Parâmetros de rastreamento são validados (tamanho, formato) antes de gravar e antes de
      repassar; ao montar a URL de saída use `http_build_query`, nunca concatenação.
- [ ] Endpoint que registra clique tem `throttle` e limite de tamanho por campo.
- [ ] IP e user agent são dado pessoal: guarde o mínimo, de preferência hash do IP.
- [ ] Cache de página pública nunca guarda resposta com cookie de sessão nem conteúdo
      dependente de usuário logado.

### HTTP externo (SSRF e robustez)
- [ ] URL acessada pelo servidor nunca vem direto do usuário. Quando o host é configurável,
      valide contra lista fechada de domínios e bloqueie IP privado, loopback e metadados de
      nuvem (`169.254.169.254`).
- [ ] `Http::` com `timeout`, `connectTimeout` e `retry` limitado; verificação TLS ligada
      (nunca `withoutVerifying()`).
- [ ] Resposta de API tratada como entrada: valide a forma antes de usar; falha vira erro
      tratado, não exceção crua na tela.
- [ ] Arquivo baixado de terceiro (vídeo gerado) tem limite de tamanho e tipo conferido pelo
      conteúdo.

### Arquivos
- [ ] Validação no servidor: `file`, `max` (KB), `mimes` e `mimetypes`; a lista é a mesma
      mostrada no front.
- [ ] Nome no disco gerado pelo servidor (`store()` / `hashName()`); o nome original vai só
      para coluna de exibição, limitado e sem caminho. Nunca
      `storeAs(..., getClientOriginalName())`.
- [ ] MIME gravado a partir do conteúdo (`getMimeType()`), não de `getClientMimeType()`.
- [ ] Download localiza o arquivo pelo registro no banco (ID → caminho), nunca monta caminho
      com pedaço da URL (path traversal).
- [ ] Arquivo privado em disco fora de `public/`, servido por rota autenticada ou URL
      temporária assinada.

### Filas e comandos
- [ ] Job recebe ID ou model, não credencial nem payload com segredo (o payload do job fica
      legível na tabela `jobs` e em `failed_jobs`).
- [ ] Job que chama API paga é idempotente ou `ShouldBeUnique`, com `tries` e `backoff`
      definidos: retry não pode cobrar duas vezes.
- [ ] Argumento de comando externo (FFmpeg) passa por `Process::run([...])` em array, nunca
      por string concatenada (injeção de comando).

### Sessão e autenticação
- [ ] Login regenera a sessão; logout por POST invalida sessão e token CSRF.
- [ ] `state` de OAuth aleatório, guardado na sessão e comparado com `hash_equals`; callback
      sem `state` salvo é rejeitado.
- [ ] Cadastro público continua desligado (skill `auth-autorizacao`).

### Produção e dependências
- [ ] `APP_DEBUG=false`, `APP_ENV=production`; `.env` fora do git e da imagem.
- [ ] Cabeçalhos: `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`,
      `Referrer-Policy: strict-origin-when-cross-origin`, HSTS e uma CSP.
- [ ] `composer audit` e `npm audit` sem vulnerabilidade alta ou crítica antes de liberar.
- [ ] Dependência nova só com aprovação. Depois de rodar qualquer CLI geradora (shadcn-vue,
      instaladores), confira `git diff package.json composer.json`.

### Qualidade
- [ ] `vendor/bin/pint --dirty --format agent` no PHP alterado.
- [ ] `npm run types:check` e `npx vp check resources/js` no front alterado.
- [ ] Teste Pest para o comportamento novo e suas falhas importantes: visitante barrado,
      dado inválido rejeitado, campo extra ignorado, webhook com assinatura errada recusado.
- [ ] Várias escritas dentro de `DB::transaction`.
- [ ] Migration nova com `down()` funcional; nunca editar migration já aplicada.

## Padrões seguros de referência

Credencial criptografada e escondida:

```php
#[Fillable(['platform', 'label'])]
#[Hidden(['credentials'])]
class PlatformConnection extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['credentials' => 'encrypted:array'];
    }
}
```

Webhook com assinatura e idempotência:

```php
public function __invoke(Request $request): Response
{
    $expected = hash_hmac('sha256', $request->getContent(), config('services.network.webhook_secret'));

    abort_unless(hash_equals($expected, (string) $request->header('X-Signature')), 403);

    $event = WebhookEvent::firstOrCreate(
        ['network' => 'network', 'external_id' => $request->string('event_id')->toString()],
        ['payload' => $request->all()],
    );

    if ($event->wasRecentlyCreated) {
        ProcessWebhookEvent::dispatch($event);
    }

    return response('OK');
}
```

(Cada rede define o próprio esquema de assinatura; siga a documentação dela e mantenha a
comparação com `hash_equals`.)

Chamada externa:

```php
$response = Http::withToken($connection->credentials['access_token'])
    ->timeout(15)
    ->connectTimeout(5)
    ->retry(2, 500, throw: false)
    ->get('https://api.example.com/v1/report', $params);

if ($response->failed()) {
    Log::warning('Report sync failed', ['connection' => $connection->id, 'status' => $response->status()]);

    return;
}
```

Upload e download:

```php
// FormRequest
'file' => ['required', 'file', 'max:102400', 'mimes:mp4,mov', 'mimetypes:video/mp4,video/quicktime'],

// Gravação
$path = $request->file('file')->store('media');

MediaAsset::create([
    'path' => $path,
    'original_name' => Str::limit(basename($request->file('file')->getClientOriginalName()), 200, ''),
    'mime_type' => $request->file('file')->getMimeType(),
    'size' => $request->file('file')->getSize(),
]);

// Download
public function download(MediaAsset $asset): StreamedResponse
{
    abort_unless(Storage::exists($asset->path), 404);

    return Storage::download($asset->path, $asset->original_name, [
        'X-Content-Type-Options' => 'nosniff',
    ]);
}
```

## Como reportar um achado

```
[ALTA] Credencial exposta em prop Inertia (app/Http/Controllers/ConnectionController.php:31)
Cenário: a listagem envia o model inteiro; o access_token aparece no HTML e no histórico.
Correção: ->only(['id', 'platform', 'label', 'status']) e 'credentials' no #[Hidden].
```

Severidade: ALTA (vazamento de credencial, execução de script, escrita ou venda forjada,
acesso a dado de terceiro), MÉDIA (exige condição extra ou tem impacto limitado), BAIXA
(endurecimento).
