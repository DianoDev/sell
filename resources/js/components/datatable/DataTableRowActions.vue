<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { Ellipsis, ExternalLink, Trash2 } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useConfirm } from '@/composables/useConfirm';
import { toUrl } from '@/lib/utils';
import type { RowAction } from '@/types';

type Props = {
    items: RowAction[];
};

defineProps<Props>();

const { confirm } = useConfirm();

/** External links come from stored data: anything but http(s) is dropped. */
const safeExternalUrl = (
    href: Extract<RowAction, { type: 'link' }>['href'],
): string | null => {
    const url = toUrl(href);

    return /^https?:\/\//i.test(url) ? url : null;
};

const runConfirm = async (
    item: Extract<RowAction, { type: 'confirm' }>,
): Promise<void> => {
    const confirmed = await confirm({
        title: item.title,
        message: item.message ?? 'Você deseja realmente confirmar esta ação?',
        confirmText: item.confirmText,
    });

    if (confirmed) {
        router[item.method ?? 'post'](toUrl(item.url), item.data ?? {}, {
            preserveScroll: true,
        });
    }
};

const runDelete = async (
    item: Extract<RowAction, { type: 'delete' }>,
): Promise<void> => {
    const confirmed = await confirm({
        title: item.title ?? 'Confirmação de exclusão',
        message: item.message ?? 'Você deseja realmente excluir este registro?',
        confirmText: 'Excluir',
        destructive: true,
    });

    if (confirmed) {
        router.delete(toUrl(item.url), { preserveScroll: true });
    }
};
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button
                type="button"
                variant="ghost"
                size="icon-sm"
                aria-label="Ações"
                @click.stop
            >
                <Ellipsis />
            </Button>
        </DropdownMenuTrigger>

        <DropdownMenuContent align="end">
            <template v-for="(item, index) in items" :key="index">
                <template v-if="item.type === 'link' && item.external">
                    <DropdownMenuItem
                        v-if="safeExternalUrl(item.href)"
                        as-child
                    >
                        <a
                            :href="safeExternalUrl(item.href) ?? undefined"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <component :is="item.icon ?? ExternalLink" />
                            {{ item.text }}
                        </a>
                    </DropdownMenuItem>
                </template>

                <DropdownMenuItem v-else-if="item.type === 'link'" as-child>
                    <Link :href="item.href">
                        <component :is="item.icon" v-if="item.icon" />
                        {{ item.text }}
                    </Link>
                </DropdownMenuItem>

                <DropdownMenuItem
                    v-else-if="item.type === 'button'"
                    :variant="item.destructive ? 'destructive' : 'default'"
                    @select="item.onClick()"
                >
                    <component :is="item.icon" v-if="item.icon" />
                    {{ item.text }}
                </DropdownMenuItem>

                <DropdownMenuItem
                    v-else-if="item.type === 'confirm'"
                    @select="runConfirm(item)"
                >
                    <component :is="item.icon" v-if="item.icon" />
                    {{ item.text }}
                </DropdownMenuItem>

                <DropdownMenuItem
                    v-else
                    variant="destructive"
                    @select="runDelete(item)"
                >
                    <Trash2 />
                    {{ item.text ?? 'Excluir' }}
                </DropdownMenuItem>
            </template>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
