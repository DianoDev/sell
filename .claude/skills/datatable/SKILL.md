---
name: datatable
description: Como construir listagens do painel com o componente DataTable (Inertia v3 + Vue 3 + Tailwind + shadcn-vue), com paginação, ordenação, filtros, seleção e ações por linha vindos do servidor. Use sempre que for criar ou alterar uma tabela, grid, listagem paginada, coluna, filtro, ordenação ou menu de ações de linha, mesmo que o pedido diga só "listar X" ou "tela de X".
---

# DataTable

A tabela não busca dados. Ela recebe o paginador do Laravel como prop e, a cada troca de
página, ordenação ou filtro, faz uma visita Inertia para a rota atual com os parâmetros na
query string. O servidor é a única fonte de verdade, a URL é compartilhável e o botão voltar
funciona. Não há estado em `localStorage` nem store.

| Arquivo | Papel |
|---|---|
| `resources/js/components/datatable/DataTable.vue` | tabela, cabeçalho, seleção, estado de carregamento |
| `resources/js/components/datatable/DataTableFilter.vue` | popover de filtro por coluna |
| `resources/js/components/datatable/DataTablePagination.vue` | itens por página e páginas |
| `resources/js/components/datatable/DataTableRowActions.vue` | menu de ações da linha |
| `resources/js/lib/datatable.ts` | leitura de valor aninhado e formatação (texto puro) |
| `resources/js/types/datatable.ts` | `Paginated`, `DataTableColumn`, `DataTableQuery`, `RowAction` |

`<ConfirmationPopup />` já está montado nos layouts do painel; as ações `delete` e `confirm`
dependem dele (skill `popup-form`).

## Contrato com o servidor

| Parâmetro | Significado |
|---|---|
| `page`, `per_page` | paginação (`page` é o nome nativo do paginador do Laravel) |
| `sort`, `direction` | ordenação; `direction` é `asc` ou `desc` |
| `<nome do filtro>` | valor do filtro; o nome é `filter.name` ou, na falta, `column.name` |

Formato do valor por tipo de filtro:

| `filter.type` | Valor enviado | Regra de validação |
|---|---|---|
| `text` | string | `['nullable', 'string', 'max:100']` |
| `select` | string | `['nullable', Rule::in([...])]` |
| `checkbox` | array | `['nullable', 'array', 'max:20']` + `'campo.*' => Rule::in([...])` |
| `date` | `YYYY-MM-DD` | `['nullable', 'date_format:Y-m-d']` |
| `date-range` | `{ from, to }` | `'campo.from'` e `'campo.to'` com `date_format:Y-m-d` |

### FormRequest da listagem

Toda listagem tem um FormRequest. É ele que impede ordenação por coluna arbitrária e
`per_page` gigante:

```php
/**
 * @return array<string, array<int, mixed>>
 */
public function rules(): array
{
    return [
        'page' => ['nullable', 'integer', 'min:1'],
        'per_page' => ['nullable', 'integer', Rule::in([10, 25, 50, 100])],
        'sort' => ['nullable', Rule::in(['name', 'created_at'])],
        'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        'name' => ['nullable', 'string', 'max:100'],
        'status' => ['nullable', 'array', 'max:20'],
        'status.*' => [Rule::enum(OfferStatus::class)],
        'created_at.from' => ['nullable', 'date_format:Y-m-d'],
        'created_at.to' => ['nullable', 'date_format:Y-m-d'],
    ];
}
```

### Controller

```php
public function index(OfferIndexRequest $request): Response
{
    $query = $request->validated();

    $offers = Offer::query()
        ->when($query['name'] ?? null, fn (Builder $q, string $name) => $q->whereLike('name', "%{$name}%"))
        ->when($query['status'] ?? null, fn (Builder $q, array $status) => $q->whereIn('status', $status))
        ->when($query['created_at']['from'] ?? null, fn (Builder $q, string $from) => $q->whereDate('created_at', '>=', $from))
        ->when($query['created_at']['to'] ?? null, fn (Builder $q, string $to) => $q->whereDate('created_at', '<=', $to))
        ->orderBy($query['sort'] ?? 'created_at', $query['direction'] ?? 'desc')
        ->paginate($query['per_page'] ?? 10)
        ->through(fn (Offer $offer) => $offer->only(['id', 'name', 'status', 'created_at']));

    return Inertia::render('offers/Index', [
        'offers' => $offers,
        'query' => $query,
    ]);
}
```

Por quê:

- `sort` e `direction` só chegam ao `orderBy` porque passaram por `Rule::in`. Nome de coluna
  não aceita binding: sem a lista, o parâmetro vira SQL.
- `per_page` com lista fechada. Uma requisição com `per_page=999999999` derruba o banco; por
  isso não existe "exibir tudo".
- `->through(... only([...]))`: tudo que vai em prop fica legível no navegador. Envie só as
  colunas que a tela usa, nunca o model inteiro (credenciais, tokens, payloads brutos).
- `query` volta como prop para a tabela mostrar o estado que o servidor realmente aplicou.

## Uso

```vue
<script setup lang="ts">
import { Pencil } from '@lucide/vue';
import OfferController from '@/actions/App/Http/Controllers/OfferController';
import DataTable from '@/components/datatable/DataTable.vue';
import DataTableRowActions from '@/components/datatable/DataTableRowActions.vue';
import type { DataTableColumn, DataTableQuery, Paginated, RowAction } from '@/types';

type Offer = { id: number; name: string; status: string; created_at: string };

defineProps<{ offers: Paginated<Offer>; query: DataTableQuery }>();

const columns: DataTableColumn<Offer>[] = [
    { name: 'name', title: 'Oferta', sort: 'name', filter: { type: 'text' } },
    { name: 'created_at', title: 'Criada em', template: 'datetime', sort: 'created_at', filter: { type: 'date-range' } },
    { name: 'actions', title: 'Ações' },
];

const actions = (row: Offer): RowAction[] => [
    { type: 'button', text: 'Editar', icon: Pencil, onClick: () => edit(row) },
    { type: 'delete', url: OfferController.destroy(row.id) },
];
</script>

<template>
    <DataTable :columns="columns" :rows="offers" :query="query" :only="['offers', 'query']">
        <template #cell-actions="{ row }">
            <DataTableRowActions :items="actions(row)" />
        </template>
    </DataTable>
</template>
```

### Props

| Prop | Uso |
|---|---|
| `columns` | definição das colunas |
| `rows` | paginador; `undefined` mostra o esqueleto (prop deferida com `Inertia::defer`) |
| `query` | estado validado devolvido pelo servidor |
| `only` | props recarregadas em cada visita; inclua a prop das linhas e `query` |
| `per-page-options` | padrão `[10, 25, 50, 100]`; mantenha igual ao `Rule::in` do request |
| `row-key` | padrão `id` |
| `selectable` + `v-model:selected` | checkbox por linha; guarda as chaves e sobrevive à troca de página |
| `row-clickable` + `@row-click` | clique na linha; checkbox e menu de ações não disparam |
| `empty-text` / slot `#empty` | mensagem sem registros |
| slot `#toolbar` | botões acima da tabela |

Uma tabela por página: os parâmetros não têm prefixo.

### Colunas

- `name`: chave da linha; ponto acessa valor aninhado (`network.name`).
- `sort`: chave enviada ao servidor. Precisa estar no `Rule::in` do request.
- `template`: `date`, `datetime`, `ago`, `yesno`, `active`.
- `formatter(value, row)`: devolve **texto puro**.
- `filter`: `text`, `select`, `checkbox`, `date`, `date-range`. `select` e `checkbox` recebem
  `options: [{ value, label }]`; opções dinâmicas vêm por prop do controller.
- `width`, `headerClass`, `cellClass`, `wrap` (as células não quebram linha por padrão).
- Marcação (link, badge, botão): slot `#cell-<name>`, que recebe `{ row, value }`. Em nome
  aninhado o ponto vira `_` (`#cell-network_name`).

### Não existe `v-html`

Toda célula é interpolada, inclusive o retorno de `formatter`. Nome de campanha, título de
anúncio, texto de webhook e resposta de API são dados de terceiros: renderizados como HTML,
um `<img onerror=...>` executa script na sessão logada. Marcação só existe em slots, escritos
por nós. Não reintroduza `v-html` em célula.

### Ações por linha (`RowAction`)

| `type` | Comportamento |
|---|---|
| `link` | navegação Inertia; com `external: true` abre em nova aba e só aceita `http(s)` (outro esquema, como `javascript:`, não é renderizado) |
| `button` | executa `onClick` (em geral abre um popup) |
| `confirm` | pede confirmação e envia `post`/`put`/`patch` para `url` com `data` |
| `delete` | pede confirmação e envia `DELETE` para `url` |

`url` e `href` aceitam a rota do Wayfinder ou string. O toast vem do flash do servidor e a
tabela recarrega sozinha pelo redirect. Esconder uma ação é só conforto visual: quem
autoriza é a rota.

## Antes de concluir

1. FormRequest com lista fechada para `sort`, `direction` e `per_page`, e limite em todo filtro.
2. Props só com as colunas usadas.
3. Teste Pest da listagem: visitante é redirecionado; `sort` fora da lista e `per_page` fora
   da lista dão erro de validação; filtro aplicado devolve só o esperado.
4. Checklist da skill `seguranca`.
