import type { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from '@lucide/vue';

/** Laravel length-aware paginator, as serialized into an Inertia prop. */
export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    from: number | null;
    to: number | null;
    total: number;
};

export type DataTableRow = Record<string, unknown>;

export type DataTableOption = {
    value: string | number;
    label: string;
};

export type DataTableDateRange = {
    from?: string;
    to?: string;
};

export type DataTableFilterValue =
    | string
    | number
    | Array<string | number>
    | DataTableDateRange
    | null
    | undefined;

/** Current server-side state of the table: page, sorting and filters. */
export type DataTableQuery = Record<string, DataTableFilterValue>;

export type DataTableFilter =
    | { type: 'text'; name?: string; placeholder?: string }
    | { type: 'date'; name?: string }
    | { type: 'date-range'; name?: string }
    | { type: 'select'; name?: string; options: DataTableOption[] }
    | { type: 'checkbox'; name?: string; options: DataTableOption[] };

export type DataTableTemplate =
    | 'date'
    | 'datetime'
    | 'ago'
    | 'yesno'
    | 'active';

export type DataTableColumn<T = DataTableRow> = {
    /** Row key; dots reach nested values (`campaign.name`). */
    name: string;
    title: string;
    /** Sort key sent to the server. Must be allow-listed there. */
    sort?: string;
    filter?: DataTableFilter;
    template?: DataTableTemplate;
    /** Must return plain text: the result is interpolated, never rendered as HTML. */
    formatter?: (value: unknown, row: T) => string;
    width?: string;
    headerClass?: string;
    cellClass?: string;
    /** Cells do not wrap by default; set to let long text break. */
    wrap?: boolean;
};

type RowActionBase = {
    text: string;
    icon?: LucideIcon;
};

type RowActionHref = NonNullable<InertiaLinkProps['href']>;

export type RowAction =
    | (RowActionBase & {
          type: 'link';
          href: RowActionHref;
          external?: boolean;
      })
    | (RowActionBase & {
          type: 'button';
          onClick: () => void;
          destructive?: boolean;
      })
    | (RowActionBase & {
          type: 'confirm';
          url: RowActionHref;
          method?: 'post' | 'put' | 'patch';
          data?: Record<string, string | number | boolean | null>;
          title?: string;
          message?: string;
          confirmText?: string;
      })
    | {
          type: 'delete';
          url: RowActionHref;
          text?: string;
          title?: string;
          message?: string;
      };
