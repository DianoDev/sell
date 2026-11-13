<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { Pencil, UserPlus } from '@lucide/vue';
import UserController from '@/actions/App/Http/Controllers/Admin/UserController';
import DataTable from '@/components/datatable/DataTable.vue';
import DataTableRowActions from '@/components/datatable/DataTableRowActions.vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { usePopup } from '@/composables/usePopup';
import { index } from '@/routes/admin/users';
import type {
    DataTableColumn,
    DataTableQuery,
    Paginated,
    RowAction,
} from '@/types';
import UserForm from './UserForm.vue';
import type { ManagedUser } from './UserForm.vue';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Usuários',
                href: index(),
            },
        ],
    },
});

defineProps<{
    users: Paginated<ManagedUser>;
    query: DataTableQuery;
}>();

const page = usePage();
const popup = usePopup();

const columns: DataTableColumn<ManagedUser>[] = [
    { name: 'name', title: 'Nome', sort: 'name', filter: { type: 'text' } },
    { name: 'email', title: 'E-mail', sort: 'email', filter: { type: 'text' } },
    {
        name: 'is_admin',
        title: 'Acesso',
        filter: {
            type: 'select',
            options: [
                { value: 1, label: 'Administrador' },
                { value: 0, label: 'Usuário' },
            ],
        },
    },
    {
        name: 'created_at',
        title: 'Criado em',
        template: 'datetime',
        sort: 'created_at',
    },
    { name: 'actions', title: 'Ações', headerClass: 'w-16', cellClass: 'w-16' },
];

const createUser = (): void => {
    popup.open({
        component: UserForm,
        title: 'Novo usuário',
        description: 'A pessoa entra com o e-mail e a senha definidos aqui.',
    });
};

const editUser = (user: ManagedUser): void => {
    popup.open({
        component: UserForm,
        title: 'Editar usuário',
        props: { user },
    });
};

const actions = (user: ManagedUser): RowAction[] => [
    {
        type: 'button',
        text: 'Editar',
        icon: Pencil,
        onClick: () => editUser(user),
    },
    ...(user.id === page.props.auth.user.id
        ? []
        : [
              {
                  type: 'delete',
                  url: UserController.destroy(user.id),
                  title: 'Excluir usuário',
                  message: `Excluir ${user.name}? Esta ação não pode ser desfeita.`,
              } satisfies RowAction,
          ]),
];
</script>

<template>
    <Head title="Usuários" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <Heading
            title="Usuários"
            description="Quem pode entrar no sistema e quem pode administrá-lo."
        />

        <DataTable
            :columns="columns"
            :rows="users"
            :query="query"
            :only="['users', 'query']"
            empty-text="Nenhum usuário encontrado com esses filtros."
        >
            <template #toolbar>
                <Button type="button" @click="createUser">
                    <UserPlus />
                    Novo usuário
                </Button>
            </template>

            <template #cell-is_admin="{ row }">
                <Badge :variant="row.is_admin ? 'default' : 'secondary'">
                    {{ row.is_admin ? 'Administrador' : 'Usuário' }}
                </Badge>
            </template>

            <template #cell-actions="{ row }">
                <DataTableRowActions :items="actions(row)" />
            </template>
        </DataTable>
    </div>
</template>
