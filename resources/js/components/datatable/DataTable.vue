<script setup lang="ts" generic="T extends DataTableRow">
import { router, usePage } from '@inertiajs/vue3';
import {
    ArrowDown,
    ArrowUp,
    ArrowUpDown,
    CircleCheck,
    CircleX,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import DataTableFilter from '@/components/datatable/DataTableFilter.vue';
import DataTablePagination from '@/components/datatable/DataTablePagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Skeleton } from '@/components/ui/skeleton';
import { Spinner } from '@/components/ui/spinner';
import {
    Table,
    TableBody,
    TableCell,
    TableEmpty,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    displayValue,
    isEmptyFilter,
    isTruthy,
    rawValue,
} from '@/lib/datatable';
import type {
    DataTableColumn,
    DataTableFilterValue,
    DataTableQuery,
    DataTableRow,
    Paginated,
} from '@/types';

type RowKey = string | number;

type Props = {
    columns: DataTableColumn<T>[];
    /** Laravel paginator. Leave undefined while a deferred prop loads. */
    rows?: Paginated<T>;
    /** Validated query the server used, so the table reflects the real state. */
    query?: DataTableQuery;
    /** Props to reload on each visit. Include the rows prop and `query`. */
    only?: string[];
    perPageOptions?: number[];
    rowKey?: string;
    selectable?: boolean;
    rowClickable?: boolean;
    emptyText?: string;
};

const props = withDefaults(defineProps<Props>(), {
    rows: undefined,
    query: () => ({}),
    only: () => [],
    perPageOptions: () => [10, 25, 50, 100],
    rowKey: 'id',
    selectable: false,
    rowClickable: false,
    emptyText: 'Sem registros para exibir',
});

const emit = defineEmits<{
    rowClick: [row: T];
}>();

defineSlots<
    {
        [name: `cell-${string}`]: (props: {
            row: T;
            value: unknown;
        }) => unknown;
    } & {
        toolbar?: () => unknown;
        empty?: () => unknown;
    }
>();

const selected = defineModel<RowKey[]>('selected', { default: () => [] });

const page = usePage();
const loading = ref(false);
const skeletonRows = 5;

/**
 * Every change is a visit to the current route: the server stays the only
 * source of truth and the state lives in the URL.
 */
const visit = (changes: DataTableQuery): void => {
    const merged: DataTableQuery = { ...props.query, ...changes };
    const params: Record<string, NonNullable<DataTableFilterValue>> = {};

    for (const [key, value] of Object.entries(merged)) {
        if (value !== null && value !== undefined && !isEmptyFilter(value)) {
            params[key] = value;
        }
    }

    router.get(page.url.split('?')[0], params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        ...(props.only.length > 0 ? { only: props.only } : {}),
        onStart: () => {
            loading.value = true;
        },
        onFinish: () => {
            loading.value = false;
        },
    });
};

const filterName = (column: DataTableColumn<T>): string =>
    column.filter?.name ?? column.name;

const applyFilter = (
    column: DataTableColumn<T>,
    value: DataTableFilterValue,
): void => {
    visit({ [filterName(column)]: value, page: undefined });
};

const isSorted = (column: DataTableColumn<T>): boolean =>
    column.sort !== undefined && props.query.sort === column.sort;

const sortDirection = (
    column: DataTableColumn<T>,
): 'ascending' | 'descending' | undefined => {
    if (!isSorted(column)) {
        return undefined;
    }

    return props.query.direction === 'desc' ? 'descending' : 'ascending';
};

const toggleSort = (column: DataTableColumn<T>): void => {
    const direction = sortDirection(column) === 'ascending' ? 'desc' : 'asc';

    visit({ sort: column.sort, direction, page: undefined });
};

const slotName = (column: DataTableColumn<T>): `cell-${string}` =>
    `cell-${column.name.replaceAll('.', '_')}`;

const keyOf = (row: T): RowKey => row[props.rowKey] as RowKey;

const pageKeys = computed(() => props.rows?.data.map(keyOf) ?? []);

const selectedOnPage = computed(() =>
    pageKeys.value.filter((key) => selected.value.includes(key)),
);

const allSelected = computed<boolean | 'indeterminate'>(() => {
    if (selectedOnPage.value.length === 0) {
        return false;
    }

    return selectedOnPage.value.length === pageKeys.value.length
        ? true
        : 'indeterminate';
});

const toggleAll = (checked: boolean): void => {
    const others = selected.value.filter(
        (key) => !pageKeys.value.includes(key),
    );

    selected.value = checked ? [...others, ...pageKeys.value] : others;
};

const toggleRow = (row: T, checked: boolean): void => {
    const key = keyOf(row);
    const others = selected.value.filter((item) => item !== key);

    selected.value = checked ? [...others, key] : others;
};

const columnCount = computed(
    () => props.columns.length + (props.selectable ? 1 : 0),
);

const onRowClick = (row: T): void => {
    if (props.rowClickable) {
        emit('rowClick', row);
    }
};
</script>

<template>
    <div class="grid gap-4">
        <div
            v-if="$slots.toolbar || (selectable && selected.length > 0)"
            class="flex flex-wrap items-center justify-between gap-2"
        >
            <slot name="toolbar" />
            <p
                v-if="selectable && selected.length > 0"
                class="text-sm text-muted-foreground"
            >
                {{ selected.length }} selecionado(s)
            </p>
        </div>

        <div class="relative rounded-md border" :aria-busy="loading">
            <div
                v-if="loading"
                class="absolute inset-0 z-10 flex items-center justify-center rounded-md bg-background/60"
            >
                <Spinner class="size-6" />
            </div>

            <Table>
                <TableHeader>
                    <TableRow class="hover:bg-transparent">
                        <TableHead v-if="selectable" class="w-10">
                            <Checkbox
                                :model-value="allSelected"
                                aria-label="Selecionar todos desta página"
                                @update:model-value="toggleAll($event === true)"
                            />
                        </TableHead>

                        <TableHead
                            v-for="column in columns"
                            :key="column.name"
                            :class="column.headerClass"
                            :style="{ width: column.width }"
                            :aria-sort="sortDirection(column)"
                        >
                            <span class="inline-flex items-center gap-1">
                                {{ column.title }}

                                <Button
                                    v-if="column.sort"
                                    type="button"
                                    variant="ghost"
                                    size="icon-sm"
                                    class="size-6"
                                    :class="
                                        isSorted(column)
                                            ? 'text-primary'
                                            : 'text-muted-foreground'
                                    "
                                    :aria-label="`Ordenar por ${column.title}`"
                                    @click="toggleSort(column)"
                                >
                                    <ArrowUp
                                        v-if="
                                            sortDirection(column) ===
                                            'ascending'
                                        "
                                        class="size-3.5"
                                    />
                                    <ArrowDown
                                        v-else-if="
                                            sortDirection(column) ===
                                            'descending'
                                        "
                                        class="size-3.5"
                                    />
                                    <ArrowUpDown v-else class="size-3.5" />
                                </Button>

                                <DataTableFilter
                                    v-if="column.filter"
                                    :filter="column.filter"
                                    :title="column.title"
                                    :value="query[filterName(column)]"
                                    @apply="applyFilter(column, $event)"
                                />
                            </span>
                        </TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody v-if="!rows">
                    <TableRow v-for="index in skeletonRows" :key="index">
                        <TableCell
                            v-for="cell in columnCount"
                            :key="cell"
                            class="py-3"
                        >
                            <Skeleton class="h-4 w-full" />
                        </TableCell>
                    </TableRow>
                </TableBody>

                <TableBody v-else>
                    <TableRow
                        v-for="row in rows.data"
                        :key="keyOf(row)"
                        :class="{ 'cursor-pointer': rowClickable }"
                        :data-state="
                            selected.includes(keyOf(row))
                                ? 'selected'
                                : undefined
                        "
                        @click="onRowClick(row)"
                    >
                        <TableCell v-if="selectable" @click.stop>
                            <Checkbox
                                :model-value="selected.includes(keyOf(row))"
                                aria-label="Selecionar linha"
                                @update:model-value="
                                    toggleRow(row, $event === true)
                                "
                            />
                        </TableCell>

                        <TableCell
                            v-for="column in columns"
                            :key="column.name"
                            :class="[
                                column.cellClass,
                                { 'whitespace-normal': column.wrap },
                            ]"
                        >
                            <slot
                                :name="slotName(column)"
                                :row="row"
                                :value="rawValue(row, column.name)"
                            >
                                <template v-if="column.template === 'active'">
                                    <CircleCheck
                                        v-if="
                                            isTruthy(rawValue(row, column.name))
                                        "
                                        class="size-4 text-green-600 dark:text-green-500"
                                        aria-label="Sim"
                                    />
                                    <CircleX
                                        v-else
                                        class="size-4 text-muted-foreground"
                                        aria-label="Não"
                                    />
                                </template>

                                <Badge
                                    v-else-if="column.template === 'yesno'"
                                    :variant="
                                        isTruthy(rawValue(row, column.name))
                                            ? 'default'
                                            : 'secondary'
                                    "
                                >
                                    {{
                                        isTruthy(rawValue(row, column.name))
                                            ? 'Sim'
                                            : 'Não'
                                    }}
                                </Badge>

                                <!-- Always interpolated: cell text is never rendered as HTML. -->
                                <template v-else>
                                    {{ displayValue(row, column) }}
                                </template>
                            </slot>
                        </TableCell>
                    </TableRow>

                    <TableEmpty
                        v-if="rows.data.length === 0"
                        :colspan="columnCount"
                    >
                        <slot name="empty">
                            <span class="text-muted-foreground">
                                {{ emptyText }}
                            </span>
                        </slot>
                    </TableEmpty>
                </TableBody>
            </Table>
        </div>

        <DataTablePagination
            v-if="rows && rows.total > 0"
            :rows="rows"
            :per-page-options="perPageOptions"
            @page="visit({ page: $event })"
            @per-page="visit({ per_page: $event, page: undefined })"
        />
    </div>
</template>
