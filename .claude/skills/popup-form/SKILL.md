---
name: popup-form
description: Padrão de modais (popup), confirmações, formulários, upload e notificações do painel com Inertia v3 + Vue 3 + shadcn-vue (usePopup, useConfirm, <Form>, useForm, flash toast). Use sempre que for criar ou alterar um formulário de cadastro ou edição, modal, diálogo de confirmação, upload de arquivo, exibição de erros de validação, toast ou lista ordenável.
---

# Popup, confirmação e formulários

O popup é aberto por um composable que recebe o componente importado. O formulário usa o
`<Form>` do Inertia (ou `useForm`), e a atualização da tela e o toast vêm do redirect do
servidor. Não há barramento de eventos, axios nem componente resolvido por nome em string.

| Arquivo | Papel |
|---|---|
| `resources/js/composables/usePopup.ts` | `open({ component, title, description?, size?, props? })`, `close()` |
| `resources/js/composables/useConfirm.ts` | `confirm({...})` devolve `Promise<boolean>` |
| `resources/js/components/Popup.vue` | diálogo único, montado nos layouts do painel |
| `resources/js/components/ConfirmationPopup.vue` | confirmação única, montada nos layouts do painel |
| `resources/js/components/PopupButton.vue` | botão que abre um popup |
| `resources/js/components/SortableList.vue` | lista ordenável (arrastar ou setas) que envia `ids` por POST |
| `resources/js/components/InputError.vue` | erro de um campo |
| `resources/js/components/AlertError.vue` | lista de erros gerais |

`<Popup />` e `<ConfirmationPopup />` ficam em `layouts/app/AppSidebarLayout.vue` e
`AppHeaderLayout.vue`. Não monte outra instância em página.

## Abrir um popup

```ts
import OfferForm from '@/pages/offers/OfferForm.vue';
import { usePopup } from '@/composables/usePopup';

const popup = usePopup();

popup.open({
    component: OfferForm,
    title: 'Editar oferta',
    size: 'lg', // sm | md | lg | xl
    props: { offer: row },
});
```

- Passe a linha que a tabela já tem. Se o formulário precisa de dados que a listagem não
  traz, peça ao servidor uma prop sob demanda (`Inertia::optional` +
  `router.reload({ only: ['detail'], data: { id } })`), não um endpoint JSON avulso.
- O componente fecha emitindo `close` ou chamando `usePopup().close()`.
- Clique fora não fecha (evita perder um formulário pela metade); `Esc` e o X fecham.

## Formulário

Prefira `<Form>` com a rota do Wayfinder, como nas telas de `settings/`:

```vue
<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import OfferController from '@/actions/App/Http/Controllers/OfferController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

type Offer = { id: number; name: string; checkout_url: string };

const props = defineProps<{ offer?: Offer }>();
const emit = defineEmits<{ close: [] }>();
</script>

<template>
    <Form
        v-bind="offer ? OfferController.update.form(offer.id) : OfferController.store.form()"
        :options="{ preserveScroll: true }"
        class="grid gap-4"
        v-slot="{ errors, processing }"
        @success="emit('close')"
    >
        <div class="grid gap-2">
            <Label for="name">Nome</Label>
            <Input id="name" name="name" :default-value="offer?.name" maxlength="255" required />
            <InputError :message="errors.name" />
        </div>

        <div class="flex justify-end gap-2">
            <Button type="button" variant="secondary" @click="emit('close')">Cancelar</Button>
            <Button type="submit" :disabled="processing">
                <Spinner v-if="processing" />
                Salvar
            </Button>
        </div>
    </Form>
</template>
```

Use `useForm` quando o estado precisa de lógica (campos dependentes, lista dinâmica,
transformação antes de enviar).

O que acontece no envio:

1. O FormRequest reprova: o Inertia preenche `errors` (uma mensagem por campo) e o popup
   continua aberto.
2. Aprovado: o controller registra o toast e redireciona; o Inertia recarrega as props (a
   tabela atualiza), `@success` fecha o popup e o toast aparece.

Regras:

- Botão de envio desabilitado com `processing`, para o duplo clique não gravar duas vezes.
- `maxlength`, `required` e afins no cliente são conforto. A regra vale no FormRequest;
  mantenha os dois iguais.
- Mensagem de erro e de confirmação sempre interpoladas, nunca `v-html`: elas podem conter
  texto digitado ou vindo de API.
- Checkbox: envie booleano e valide com `boolean`. Não dependa de "campo ausente = falso".
- Campo de segredo (token, chave de API): `type="password"`, `autocomplete="off"`, e na
  edição o valor atual **não** volta do servidor. Mostre só "configurado" e trate campo vazio
  como "manter o atual".

## Upload de arquivo

```ts
const form = useForm<{ title: string; file: File | null }>({ title: '', file: null });

form.post(MediaController.store.url(), { onSuccess: () => emit('close') });
```

- Com um `File` nos dados o Inertia envia multipart sozinho. Para editar, envie `POST` com
  `_method: 'put'`: o PHP não lê multipart em `PUT`.
- `form.progress?.percentage` dá a barra de progresso.
- A lista do atributo `accept`, o texto de ajuda e a regra `mimes` do FormRequest são a mesma
  lista. Regras de servidor na skill `seguranca`.

## Confirmação

```ts
import { router } from '@inertiajs/vue3';
import { useConfirm } from '@/composables/useConfirm';

const { confirm } = useConfirm();

const pause = async (row: Campaign): Promise<void> => {
    const confirmed = await confirm({
        title: 'Pausar campanha',
        message: `Deseja pausar ${row.name}?`,
        confirmText: 'Pausar',
        destructive: true,
    });

    if (confirmed) {
        router.post(CampaignController.pause.url(row.id), {}, { preserveScroll: true });
    }
};
```

Em tabela, use as ações `delete` e `confirm` do `DataTableRowActions` (skill `datatable`),
que já fazem isso. Toda ação destrutiva, irreversível ou que gasta dinheiro (publicar
anúncio, mudar orçamento, gerar vídeo pago) pede confirmação e usa POST/PUT/PATCH/DELETE,
nunca GET.

## Notificações

O servidor decide a mensagem; `resources/js/lib/flashToast.ts` exibe com `vue-sonner`:

```php
Inertia::flash('toast', ['type' => 'success', 'message' => 'Oferta salva.']);

return to_route('offers.index');
```

`type`: `success`, `info`, `warning`, `error`. A mensagem de erro para o usuário é genérica
("Não foi possível salvar"); o detalhe técnico vai para o log. Regra de negócio violada vira
`ValidationException::withMessages([...])` ou toast de erro, nunca erro 500 com a mensagem da
exceção.

## Lista ordenável

```ts
popup.open({
    component: SortableList,
    title: 'Ordenar cenas',
    size: 'md',
    props: { items: scenes.map((s) => ({ id: s.id, label: s.title })), url: SceneController.reorder(project.id) },
});
```

O servidor recebe `ids` na nova ordem. Valide como array com tamanho máximo e confira que
todos os ids pertencem ao registro pai antes de gravar.
