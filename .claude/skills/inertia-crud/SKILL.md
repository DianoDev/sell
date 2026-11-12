---
name: inertia-crud
description: Padrão de desenvolvimento de um módulo do painel (Laravel 13 + Inertia v3 + Vue 3 + TypeScript + Wayfinder). Use sempre que for criar ou alterar uma tela, CRUD, rota, controller, FormRequest, Action, model ou página do painel, ou quando o pedido for "novo módulo", "nova tela", "novo cadastro", "listar/editar/excluir X", mesmo que não cite Inertia.
---

# Módulo do painel

Todo módulo segue a mesma fatia vertical. Seguir o padrão importa porque é nele que estão a
validação, a autorização e o cuidado com o que sai para o navegador; código "criativo" aqui
costuma virar vazamento de credencial ou escrita sem validação.

Confira antes: `composer show --direct` e `package.json` para as versões, e os arquivos
irmãos (`app/Http/Controllers/Settings`, `resources/js/pages/settings`) para o estilo.

## A fatia vertical

| Camada | Local | Regra |
|---|---|---|
| Rota | `routes/web.php` (ou arquivo incluído por ele) | grupo `['auth', 'verified']`; nome `<modulo>.<acao>` |
| Controller | `app/Http/Controllers/<Area>/<Modulo>Controller.php` | fino: valida, delega, devolve `Inertia::render` ou redirect |
| Validação | `app/Http/Requests/<Area>/<Modulo>...Request.php` | um por ação que recebe dados, inclusive a listagem |
| Regra de negócio | `app/Actions/<Area>/<Verbo><Coisa>.php` | quando há mais que um `create`/`update` simples |
| Model | `app/Models/<Modulo>.php` | atributos `#[Fillable]` e `#[Hidden]`; factory e seeder |
| Enum | `app/Enums/` | chaves em TitleCase |
| Página | `resources/js/pages/<modulo>/Index.vue` | `DataTable` + popup |
| Formulário | `resources/js/pages/<modulo>/<Modulo>Form.vue` | `<Form>` (skill `popup-form`) |
| Teste | `tests/Feature/<Area>/<Modulo>Test.php` | Pest, com factories |

Crie os arquivos PHP com `php artisan make:* --no-interaction`. Skills relacionadas:
`datatable` (listagem), `popup-form` (modal e formulário), `auth-autorizacao` (acesso),
`seguranca` (checklist antes de concluir).

## Rotas

```php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('offers', [OfferController::class, 'index'])->name('offers.index');
    Route::post('offers', [OfferController::class, 'store'])->name('offers.store');
    Route::put('offers/{offer}', [OfferController::class, 'update'])->name('offers.update');
    Route::delete('offers/{offer}', [OfferController::class, 'destroy'])->name('offers.destroy');
});
```

- Nada que altera estado em GET: GET não tem proteção CSRF e é disparado por link, imagem e
  prefetch.
- Rota fixa (`offers/reorder`) antes da rota com parâmetro.
- A listagem não tem rota JSON separada: os dados vêm como props do `index`.
- Ação que chama API externa, envia e-mail ou gasta crédito leva `throttle`.
- Rota pública (presell, webhook) não entra neste grupo e tem regras próprias na skill
  `seguranca`.

## Controller

```php
public function store(OfferStoreRequest $request): RedirectResponse
{
    Offer::create($request->validated());

    Inertia::flash('toast', ['type' => 'success', 'message' => 'Oferta salva.']);

    return to_route('offers.index');
}

public function update(OfferUpdateRequest $request, Offer $offer): RedirectResponse
{
    $offer->update($request->validated());

    Inertia::flash('toast', ['type' => 'success', 'message' => 'Oferta salva.']);

    return to_route('offers.index');
}

public function destroy(Offer $offer): RedirectResponse
{
    $offer->delete();

    Inertia::flash('toast', ['type' => 'success', 'message' => 'Oferta excluída.']);

    return to_route('offers.index');
}
```

O `index` está na skill `datatable`. Por quê:

- **`validated()` e nunca `$request->all()`, `input()` cru ou `except()`**: só entra no banco
  o que tem regra. Um campo extra no POST não pode alterar coluna de controle.
- **Route model binding** (`Offer $offer`): registro inexistente responde 404 sem código.
  Em rota aninhada (`projects/{project}/scenes/{scene}`) use `->scopeBindings()` para a cena
  ter de pertencer ao projeto.
- **Redirect + flash** em vez de JSON: o Inertia recarrega as props, a tabela atualiza e o
  toast aparece.
- **Props mínimas**: tudo que vai em prop fica legível no navegador e no histórico. Use
  `->only([...])` ou um API Resource; nunca envie o model inteiro.
- Erro previsível (API externa fora do ar, regra de negócio) vira toast de erro ou
  `ValidationException`. A mensagem da exceção vai para o log, não para a resposta.

## FormRequest

```php
/**
 * @return array<string, array<int, mixed>>
 */
public function rules(): array
{
    return [
        'name' => ['required', 'string', 'max:255'],
        'checkout_url' => ['required', 'url:https', 'max:2048'],
        'status' => ['required', Rule::enum(OfferStatus::class)],
        'network_id' => ['required', 'integer', Rule::exists('platform_connections', 'id')],
        'is_active' => ['boolean'],
    ];
}
```

- Todo campo tem tipo e limite.
- URL sempre com esquema: `url:https` (ou `url:http,https`). Um `string` simples aceita
  `javascript:...`, que vira XSS quando cai num `href`, e aceita endereço interno, que vira
  SSRF quando o servidor acessa a URL.
- Valor de conjunto fechado: `Rule::enum` ou `Rule::in`.
- Siga o estilo dos requests existentes em `app/Http/Requests/Settings` (array ou string).

## Model

```php
#[Fillable(['name', 'checkout_url', 'status', 'is_active'])]
#[Hidden(['credentials'])]
class PlatformConnection extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'credentials' => 'encrypted:array',
            'status' => ConnectionStatus::class,
            'token_expires_at' => 'datetime',
        ];
    }
}
```

- Coluna de controle (dono, contadores, status calculado, custo) fica fora do `Fillable` e é
  atribuída no servidor.
- Segredo (token OAuth, chave de API, developer token) usa cast `encrypted` e entra no
  `Hidden`.
- Várias escritas relacionadas vão dentro de `DB::transaction(fn () => ...)`.
- Migration nova com `down()` funcional; nunca edite migration já aplicada.

## Página

```vue
<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PopupButton from '@/components/PopupButton.vue';
import DataTable from '@/components/datatable/DataTable.vue';
import { index } from '@/routes/offers';
import OfferForm from './OfferForm.vue';

defineOptions({
    layout: { breadcrumbs: [{ title: 'Ofertas', href: index() }] },
});
</script>

<template>
    <Head title="Ofertas" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <DataTable :columns="columns" :rows="offers" :query="query" :only="['offers', 'query']">
            <template #toolbar>
                <PopupButton :component="OfferForm" title="Nova oferta">Nova oferta</PopupButton>
            </template>
        </DataTable>
    </div>
</template>
```

- TypeScript em `<script setup lang="ts">`; props tipadas com `defineProps<{...}>()`.
- URLs sempre pelo Wayfinder (`@/actions/...` ou `@/routes/...`), nunca string escrita à mão.
- O layout é escolhido em `resources/js/app.ts` pelo nome da página; a página só informa os
  breadcrumbs.
- Componentes de `resources/js/components/ui` (shadcn-vue) e ícones de `@lucide/vue`. Para
  um componente que falta, gere com a CLI do shadcn-vue e **confira o `package.json`
  depois**: a CLI pode alterar dependências, o que exige aprovação.
- Tailwind 4 com as cores do tema (`bg-background`, `text-muted-foreground`, `border`), que
  já cobrem o modo escuro. Não use cor fixa sem a variante `dark:`.
- Prop deferida (`Inertia::defer`) precisa de estado vazio com esqueleto; o `DataTable` já
  mostra um quando `rows` é `undefined`.

## Antes de concluir

1. Rota no grupo `['auth', 'verified']`, com verbo correto e nome único.
2. Controller usa `validated()`; props só com o necessário.
3. Teste Pest (leia a skill `testing-best-practices` antes): visitante é redirecionado ao
   login; dado inválido volta com erro e não grava; campo extra é ignorado; caminho feliz
   grava e redireciona.
4. `vendor/bin/pint --dirty --format agent`, `npm run types:check` e
   `npx vp check resources/js`.
5. Checklist da skill `seguranca`.
