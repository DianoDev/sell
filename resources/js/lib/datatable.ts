import type {
    DataTableColumn,
    DataTableFilterValue,
    DataTableRow,
} from '@/types';

const locale = 'pt-BR';

const dateOnlyPattern = /^(\d{4})-(\d{2})-(\d{2})$/;

const relativeUnits: Array<[Intl.RelativeTimeFormatUnit, number]> = [
    ['year', 31_536_000],
    ['month', 2_592_000],
    ['day', 86_400],
    ['hour', 3_600],
    ['minute', 60],
];

/** Reads a row value by column name, following dots into nested objects. */
export function rawValue(row: DataTableRow, name: string): unknown {
    return name.split('.').reduce<unknown>((value, key) => {
        if (value !== null && typeof value === 'object') {
            return (value as Record<string, unknown>)[key];
        }

        return undefined;
    }, row);
}

export function isTruthy(value: unknown): boolean {
    return value === true || value === 1 || value === '1';
}

export function isEmptyFilter(value: DataTableFilterValue): boolean {
    if (value === null || value === undefined || value === '') {
        return true;
    }

    if (Array.isArray(value)) {
        return value.length === 0;
    }

    if (typeof value === 'object') {
        return !value.from && !value.to;
    }

    return false;
}

export function formatDate(value: unknown, withTime: boolean): string {
    if (typeof value !== 'string' || value === '') {
        return '';
    }

    // A bare date has no timezone: parsing it as a Date would shift the day.
    const dateOnly = dateOnlyPattern.exec(value);

    if (dateOnly) {
        return `${dateOnly[3]}/${dateOnly[2]}/${dateOnly[1]}`;
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return '';
    }

    return withTime
        ? date.toLocaleString(locale, {
              dateStyle: 'short',
              timeStyle: 'short',
          })
        : date.toLocaleDateString(locale);
}

export function formatAgo(value: unknown): string {
    if (typeof value !== 'string' || value === '') {
        return '';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return '';
    }

    const seconds = Math.round((date.getTime() - Date.now()) / 1000);
    const formatter = new Intl.RelativeTimeFormat(locale, { numeric: 'auto' });

    for (const [unit, unitSeconds] of relativeUnits) {
        if (Math.abs(seconds) >= unitSeconds) {
            return formatter.format(Math.round(seconds / unitSeconds), unit);
        }
    }

    return formatter.format(seconds, 'second');
}

/** Plain text shown in a cell. Never returns markup. */
export function displayValue<T extends DataTableRow>(
    row: T,
    column: DataTableColumn<T>,
): string {
    const value = rawValue(row, column.name);

    if (column.formatter) {
        return column.formatter(value, row);
    }

    switch (column.template) {
        case 'date':
            return formatDate(value, false);
        case 'datetime':
            return formatDate(value, true);
        case 'ago':
            return formatAgo(value);
        default:
            return typeof value === 'string' ||
                typeof value === 'number' ||
                typeof value === 'boolean'
                ? String(value)
                : '';
    }
}
