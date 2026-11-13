<script setup lang="ts">
import { Form, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import UserController from '@/actions/App/Http/Controllers/Admin/UserController';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

export type ManagedUser = {
    id: number;
    name: string;
    email: string;
    is_admin: boolean;
    created_at: string;
};

const props = defineProps<{
    user?: ManagedUser;
}>();

const emit = defineEmits<{
    close: [];
}>();

const page = usePage();

const isAdmin = ref(props.user?.is_admin ?? false);
const isOwnAccount = computed(() => props.user?.id === page.props.auth.user.id);
</script>

<template>
    <Form
        v-bind="
            user
                ? UserController.update.form(user.id)
                : UserController.store.form()
        "
        :options="{ preserveScroll: true }"
        class="grid gap-4"
        v-slot="{ errors, processing }"
        @success="emit('close')"
    >
        <div class="grid gap-2">
            <Label for="user-name">Nome</Label>
            <Input
                id="user-name"
                name="name"
                :default-value="user?.name"
                maxlength="255"
                autocomplete="off"
                required
            />
            <InputError :message="errors.name" />
        </div>

        <div class="grid gap-2">
            <Label for="user-email">E-mail</Label>
            <Input
                id="user-email"
                type="email"
                name="email"
                :default-value="user?.email"
                maxlength="255"
                autocomplete="off"
                required
            />
            <InputError :message="errors.email" />
        </div>

        <div class="grid items-start gap-4 sm:grid-cols-2">
            <div class="grid gap-2">
                <Label for="user-password">
                    {{ user ? 'Nova senha' : 'Senha' }}
                </Label>
                <PasswordInput
                    id="user-password"
                    name="password"
                    autocomplete="new-password"
                    :required="!user"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="user-password-confirmation">Confirme a senha</Label>
                <PasswordInput
                    id="user-password-confirmation"
                    name="password_confirmation"
                    autocomplete="new-password"
                    :required="!user"
                />
            </div>
        </div>

        <p v-if="user" class="text-sm text-muted-foreground">
            Deixe a senha em branco para manter a atual.
        </p>

        <div class="grid gap-2">
            <input type="hidden" name="is_admin" :value="isAdmin ? 1 : 0" />
            <Label class="font-normal">
                <Checkbox
                    :model-value="isAdmin"
                    :disabled="isOwnAccount"
                    @update:model-value="isAdmin = $event === true"
                />
                Administrador (pode gerenciar usuários)
            </Label>
            <p v-if="isOwnAccount" class="text-sm text-muted-foreground">
                Você não pode remover o seu próprio acesso de administrador.
            </p>
            <InputError :message="errors.is_admin" />
        </div>

        <div class="flex justify-end gap-2">
            <Button type="button" variant="secondary" @click="emit('close')">
                Cancelar
            </Button>
            <Button type="submit" :disabled="processing">
                <Spinner v-if="processing" />
                {{ user ? 'Salvar usuário' : 'Criar usuário' }}
            </Button>
        </div>
    </Form>
</template>
