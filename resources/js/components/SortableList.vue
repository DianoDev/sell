<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import type { InertiaLinkProps } from '@inertiajs/vue3';
import { ChevronDown, ChevronUp, GripVertical } from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { toUrl } from '@/lib/utils';

type SortableItem = {
    id: string | number;
    label: string;
};

type Props = {
    items: SortableItem[];
    /** Receives a POST with `ids` in the new order. */
    url: NonNullable<InertiaLinkProps['href']>;
};

const props = defineProps<Props>();

const emit = defineEmits<{
    close: [];
}>();

const list = ref<SortableItem[]>([...props.items]);
const draggedIndex = ref<number | null>(null);
const processing = ref(false);

const move = (from: number, to: number): void => {
    if (to < 0 || to >= list.value.length || from === to) {
        return;
    }

    const reordered = [...list.value];
    const [item] = reordered.splice(from, 1);

    reordered.splice(to, 0, item);
    list.value = reordered;
};

const onDragOver = (index: number): void => {
    if (draggedIndex.value === null || draggedIndex.value === index) {
        return;
    }

    move(draggedIndex.value, index);
    draggedIndex.value = index;
};

const save = (): void => {
    router.post(
        toUrl(props.url),
        { ids: list.value.map((item) => item.id) },
        {
            preserveScroll: true,
            onStart: () => {
                processing.value = true;
            },
            onFinish: () => {
                processing.value = false;
            },
            onSuccess: () => emit('close'),
        },
    );
};
</script>

<template>
    <div class="grid gap-4">
        <p v-if="list.length === 0" class="text-sm text-muted-foreground">
            Não há itens para ordenar
        </p>

        <ol v-else class="grid gap-1">
            <li
                v-for="(item, index) in list"
                :key="item.id"
                draggable="true"
                class="flex items-center gap-2 rounded-md border bg-background px-2 py-1.5 text-sm"
                :class="{ 'opacity-50': draggedIndex === index }"
                @dragstart="draggedIndex = index"
                @dragover.prevent="onDragOver(index)"
                @dragend="draggedIndex = null"
            >
                <GripVertical
                    class="size-4 shrink-0 cursor-grab text-muted-foreground"
                />
                <span class="w-6 shrink-0 text-muted-foreground tabular-nums">
                    {{ index + 1 }}
                </span>
                <span class="min-w-0 flex-1 truncate">{{ item.label }}</span>

                <Button
                    type="button"
                    variant="ghost"
                    size="icon-sm"
                    :aria-label="`Mover ${item.label} para cima`"
                    :disabled="index === 0"
                    @click="move(index, index - 1)"
                >
                    <ChevronUp />
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon-sm"
                    :aria-label="`Mover ${item.label} para baixo`"
                    :disabled="index === list.length - 1"
                    @click="move(index, index + 1)"
                >
                    <ChevronDown />
                </Button>
            </li>
        </ol>

        <div class="flex justify-end gap-2">
            <Button type="button" variant="secondary" @click="emit('close')">
                Cancelar
            </Button>
            <Button
                type="button"
                :disabled="processing || list.length === 0"
                @click="save"
            >
                <Spinner v-if="processing" />
                Salvar ordem
            </Button>
        </div>
    </div>
</template>
