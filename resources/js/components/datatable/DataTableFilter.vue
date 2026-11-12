<script setup lang="ts">
import { Filter } from '@lucide/vue';
import { computed, reactive, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { isEmptyFilter } from '@/lib/datatable';
import type { DataTableFilter, DataTableFilterValue } from '@/types';

type Props = {
    filter: DataTableFilter;
    title: string;
    value?: DataTableFilterValue;
};

const props = withDefaults(defineProps<Props>(), {
    value: undefined,
});

const emit = defineEmits<{
    apply: [value: DataTableFilterValue];
}>();

const open = ref(false);
const text = ref('');
const checked = ref<string[]>([]);
const range = reactive({ from: '', to: '' });

const active = computed(() => !isEmptyFilter(props.value));

const syncDraft = (): void => {
    const value = props.value;

    text.value =
        typeof value === 'string' || typeof value === 'number'
            ? String(value)
            : '';
    checked.value = Array.isArray(value) ? value.map(String) : [];

    const isRange =
        value !== null && typeof value === 'object' && !Array.isArray(value);

    range.from = isRange ? (value.from ?? '') : '';
    range.to = isRange ? (value.to ?? '') : '';
};

watch(open, (isOpen) => {
    if (isOpen) {
        syncDraft();
    }
});

const toggleOption = (option: string, isChecked: boolean): void => {
    checked.value = isChecked
        ? [...checked.value, option]
        : checked.value.filter((item) => item !== option);
};

const apply = (): void => {
    open.value = false;

    switch (props.filter.type) {
        case 'checkbox':
            emit('apply', checked.value);
            break;
        case 'date-range':
            emit('apply', { from: range.from, to: range.to });
            break;
        default:
            emit('apply', text.value.trim());
    }
};

const clear = (): void => {
    open.value = false;
    emit('apply', null);
};
</script>

<template>
    <Popover v-model:open="open">
        <PopoverTrigger as-child>
            <Button
                type="button"
                variant="ghost"
                size="icon-sm"
                class="size-6"
                :class="active ? 'text-primary' : 'text-muted-foreground'"
                :aria-label="`Filtrar ${title}`"
                :data-active="active"
            >
                <Filter class="size-3.5" :class="{ 'fill-current': active }" />
            </Button>
        </PopoverTrigger>

        <PopoverContent align="start" class="w-64 font-normal">
            <form class="grid gap-3" @submit.prevent="apply">
                <Input
                    v-if="filter.type === 'text'"
                    v-model="text"
                    v-focus
                    type="text"
                    maxlength="100"
                    :placeholder="filter.placeholder ?? title"
                    :aria-label="title"
                />

                <Input
                    v-else-if="filter.type === 'date'"
                    v-model="text"
                    type="date"
                    :aria-label="title"
                />

                <div
                    v-else-if="filter.type === 'date-range'"
                    class="grid gap-2"
                >
                    <div class="grid gap-1">
                        <Label :for="`${title}-from`">De</Label>
                        <Input
                            :id="`${title}-from`"
                            v-model="range.from"
                            type="date"
                            :max="range.to || undefined"
                        />
                    </div>
                    <div class="grid gap-1">
                        <Label :for="`${title}-to`">Até</Label>
                        <Input
                            :id="`${title}-to`"
                            v-model="range.to"
                            type="date"
                            :min="range.from || undefined"
                        />
                    </div>
                </div>

                <Select v-else-if="filter.type === 'select'" v-model="text">
                    <SelectTrigger class="w-full" :aria-label="title">
                        <SelectValue placeholder="Todos" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="option in filter.options"
                            :key="option.value"
                            :value="String(option.value)"
                        >
                            {{ option.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <div
                    v-else-if="filter.type === 'checkbox'"
                    class="grid max-h-56 gap-2 overflow-y-auto"
                >
                    <Label
                        v-for="option in filter.options"
                        :key="option.value"
                        class="font-normal"
                    >
                        <Checkbox
                            :model-value="
                                checked.includes(String(option.value))
                            "
                            @update:model-value="
                                toggleOption(
                                    String(option.value),
                                    $event === true,
                                )
                            "
                        />
                        <span class="truncate">{{ option.label }}</span>
                    </Label>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <Button type="submit" size="sm">Aplicar</Button>
                    <Button
                        type="button"
                        size="sm"
                        variant="ghost"
                        @click="clear"
                    >
                        Limpar
                    </Button>
                </div>
            </form>
        </PopoverContent>
    </Popover>
</template>
