<script setup lang="ts">
import {
    ChevronLeft,
    ChevronRight,
    ChevronsLeft,
    ChevronsRight,
} from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { Paginated } from '@/types';

type Props = {
    rows: Paginated<unknown>;
    perPageOptions: number[];
};

const props = defineProps<Props>();

const emit = defineEmits<{
    page: [page: number];
    perPage: [perPage: number];
}>();

const visiblePages = 5;

const pages = computed(() => {
    const last = props.rows.last_page;
    const start = Math.max(
        1,
        Math.min(
            props.rows.current_page - Math.floor(visiblePages / 2),
            last - visiblePages + 1,
        ),
    );
    const end = Math.min(last, start + visiblePages - 1);

    return Array.from({ length: end - start + 1 }, (_, index) => start + index);
});

const isFirst = computed(() => props.rows.current_page <= 1);
const isLast = computed(() => props.rows.current_page >= props.rows.last_page);

const goTo = (page: number): void => {
    if (
        page >= 1 &&
        page <= props.rows.last_page &&
        page !== props.rows.current_page
    ) {
        emit('page', page);
    }
};
</script>

<template>
    <div
        class="flex flex-col items-center justify-between gap-3 text-sm sm:flex-row"
    >
        <div class="flex items-center gap-3">
            <Select
                :model-value="String(rows.per_page)"
                @update:model-value="emit('perPage', Number($event))"
            >
                <SelectTrigger size="sm" aria-label="Itens por página">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="option in perPageOptions"
                        :key="option"
                        :value="String(option)"
                    >
                        {{ option }} por página
                    </SelectItem>
                </SelectContent>
            </Select>

            <p class="text-muted-foreground">
                Exibindo {{ rows.from }} a {{ rows.to }} de {{ rows.total }}
            </p>
        </div>

        <nav class="flex items-center gap-1" aria-label="Paginação">
            <Button
                type="button"
                variant="outline"
                size="icon-sm"
                aria-label="Primeira página"
                :disabled="isFirst"
                @click="goTo(1)"
            >
                <ChevronsLeft />
            </Button>
            <Button
                type="button"
                variant="outline"
                size="icon-sm"
                aria-label="Página anterior"
                :disabled="isFirst"
                @click="goTo(rows.current_page - 1)"
            >
                <ChevronLeft />
            </Button>

            <Button
                v-for="page in pages"
                :key="page"
                type="button"
                size="icon-sm"
                :variant="page === rows.current_page ? 'default' : 'outline'"
                :aria-current="page === rows.current_page ? 'page' : undefined"
                @click="goTo(page)"
            >
                {{ page }}
            </Button>

            <Button
                type="button"
                variant="outline"
                size="icon-sm"
                aria-label="Próxima página"
                :disabled="isLast"
                @click="goTo(rows.current_page + 1)"
            >
                <ChevronRight />
            </Button>
            <Button
                type="button"
                variant="outline"
                size="icon-sm"
                aria-label="Última página"
                :disabled="isLast"
                @click="goTo(rows.last_page)"
            >
                <ChevronsRight />
            </Button>
        </nav>
    </div>
</template>
